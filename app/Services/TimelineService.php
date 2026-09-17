<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\AssessmentAttempt;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Builds the student's Unified Learning Timeline (US-505) and the teacher-facing
 * cross-student Learning Activity feed (US-606) from the SAME event-composition
 * mechanism — one beat vocabulary, no parallel sources.
 *
 * Sources are composed from existing authoritative records — no event table is
 * added (§16):
 *   - Activity (learning types only)     mission completions and wrong
 *     submissions. login/logout are session-boundary bookkeeping written by
 *     AuthController; they carry no learning state and are filtered out
 *     (§51.0).
 *   - XpTransaction (ledger-beat types)  hint spends, solution reveals, and
 *     the Boss Challenge award. mission_completed and wrong_submission are NOT
 *     re-emitted from the ledger: Activity already surfaces those beats, so
 *     emitting the rows again would display the same event twice.
 *   - AssessmentAttempt (passed/failed)  scored challenge results. Transient
 *     states (available/started/submitted/evaluated) are not beats.
 *   - Progress-derived section completions  a section that is fully complete
 *     today gains one beat timestamped at the moment its last mission was
 *     finished (a state derived from completion rows, not a stored event).
 *
 * Course completion is not derived as a separate beat: US-410 defines
 * course-complete as "has ever passed the assessment", and its moment IS the
 * assessment pass, so it is surfaced by the Boss Challenge result and award
 * beats instead of a duplicate synthetic row.
 *
 * The single-user path (events(), the student's own timeline) and the teacher
 * feed (feed()) share the four source builders. The feed parameterizes them
 * over a student set: each table source runs ONE query across the set
 * (whereIn on user_id) with the student, event-type, and date-window filters
 * pushed down, then the merged beats are sorted and paginated in memory. The
 * only per-user work is the section-completion derivation, which must reuse
 * LearningPathService::build() per student (the shared DONE definition);
 * feed() bounds that pass to students who have progress rows inside the
 * queried window instead of the whole roster.
 *
 * Every query is keyed by user_id ids — a client never names a user through
 * these services; the student filter is resolved server-side from the
 * validated request (§17).
 */
class TimelineService
{
    public const NON_LEARNING_ACTIVITY_TYPES = ['login', 'logout'];

    /**
     * Beat types the teacher feed's event-type filter accepts, in the same
     * vocabulary the timeline itself produces.
     */
    public const FILTERABLE_TYPES = [
        'mission_completed',
        'wrong_submission',
        KnowledgeCheckService::ACTIVITY_TYPE_COMPLETED,
        'hint_used',
        'solution_revealed',
        'assessment_completed',
        'assessment_passed',
        'assessment_failed',
        'section_completed',
    ];

    /**
     * The default date-window span for the teacher feed, ending today when no
     * bounds are supplied, so the unfiltered view is bounded instead of
     * computed over the whole history.
     */
    public const DEFAULT_WINDOW_DAYS = 14;

    /**
     * The widest window a teacher may request. The feed's in-memory slice is
     * bounded by its window (US-606 §44/§45), so the window itself must be
     * capped server-side: a client cannot widen it beyond this and turn the
     * bounded slice back into an unbounded scan. Enforced in
     * ActivityFeedRequest::withValidator before any query runs.
     */
    public const MAX_WINDOW_DAYS = 90;

    public const FEED_PER_PAGE = 30;

    /**
     * Ledger types surfaced directly as beats. mission_completed and
     * wrong_submission are deliberately absent (Activity owns those beats).
     */
    private const XP_BEAT_TYPES = [
        XpService::TYPE_HINT_USED,
        XpService::TYPE_SOLUTION_REVEALED,
        XpService::TYPE_ASSESSMENT_COMPLETED,
    ];

    /**
     * Build-order tiebreaker so events in the same second keep a stable,
     * source-consistent order.
     */
    private int $seq = 0;

    public function __construct(
        private readonly LearningPathService $path,
    ) {}

    /**
     * @return Collection<int, array{at: Carbon, label: string, type: string, pts: int|null, seq: int}>
     */
    public function events(User $user, int $limit = 50): Collection
    {
        $events = $this->compose(collect([$user]), null, null, null, null, collect([$user]));

        return $events
            ->sortByDesc(fn (array $event): array => [$event['at']->getTimestamp(), $event['seq']])
            ->take($limit)
            ->values()
            ->map(function (array $event): array {
                return [
                    'at' => $event['at'],
                    'label' => $event['label'],
                    'type' => $event['type'],
                    'pts' => $event['pts'],
                    'seq' => $event['seq'],
                ];
            });
    }

    /**
     * The teacher-facing activity feed (US-606): the same composed beats as
     * events(), parameterized over a set of students and filtered server-side.
     *
     * @param  Collection<int, int>|null  $studentIds  validated student-filter
     *                                                 ids, or null for the whole roster
     * @param  array<string, string>  $paginatorQuery  active filters preserved
     *                                                 across pagination
     * @return LengthAwarePaginator<int, array{at: Carbon, label: string, type: string, pts: int|null, seq: int, user: array{id: int, username: string}}>
     */
    public function feed(
        ?Collection $studentIds,
        ?int $courseId,
        ?string $type,
        Carbon $from,
        Carbon $to,
        array $paginatorQuery,
    ): LengthAwarePaginator {
        $this->seq = 0;

        $students = $this->students($studentIds);

        $sectionCandidates = $this->sectionCandidates($students, $from, $to);

        $events = $this->compose($students, $type, $from, $to, $courseId, $sectionCandidates)
            ->filter(fn (array $event): bool => $event['at']->between($from, $to))
            ->sortByDesc(fn (array $event): array => [$event['at']->getTimestamp(), $event['seq']]);

        $usersById = $students->keyBy('id');

        $rows = $events->map(function (array $event) use ($usersById): array {
            /** @var User|null $user */
            $user = $usersById->get($event['user_id']);

            return [
                'at' => $event['at'],
                'label' => $event['label'],
                'type' => $event['type'],
                'pts' => $event['pts'],
                'seq' => $event['seq'],
                'user' => [
                    'id' => $event['user_id'],
                    'username' => $user->username ?? 'UNKNOWN',
                ],
            ];
        });

        $page = Paginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $rows->forPage($page, self::FEED_PER_PAGE),
            $rows->count(),
            self::FEED_PER_PAGE,
            $page,
            [
                'path' => route('activity'),
                'query' => $paginatorQuery,
            ],
        );
    }

    /**
     * The roster of students the feed spans: the whole fleet, or the validated
     * student-filter set. Never a client-chosen id outside that bucket — the
     * 'teacher' gate is system-wide, so a filter only narrows the view.
     *
     * @param  Collection<int, int>|null  $ids
     * @return Collection<int, User>
     */
    private function students(?Collection $ids): Collection
    {
        return User::query()
            ->where('role', 'student')
            ->when($ids !== null, fn (Builder $query) => $query->whereIn('id', $ids))
            ->orderBy('username')
            ->get();
    }

    /**
     * Students who can contribute a section-completion beat inside the window:
     * a done section's beat is timed at its FINAL mission completion, so a beat
     * inside [from, to] requires the student to have at least one completion
     * row inside the window. Bounds the per-student path builds to students
     * with real activity instead of the whole roster.
     *
     * @param  Collection<int, User>  $students
     * @return Collection<int, User>
     */
    private function sectionCandidates(Collection $students, Carbon $from, Carbon $to): Collection
    {
        $idsWithWindowProgress = Progress::query()
            ->whereIn('user_id', $students->pluck('id'))
            ->whereBetween('completed_at', [$from, $to])
            ->distinct()
            ->pluck('user_id')
            ->all();

        return $students->filter(fn (User $user): bool => in_array($user->id, $idsWithWindowProgress, true));
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, User>|null  $sectionCandidates
     * @return Collection<int, array{at: Carbon, label: string, type: string, pts: int|null, seq: int, user_id: int}>
     */
    private function compose(
        Collection $users,
        ?string $type,
        ?Carbon $from,
        ?Carbon $to,
        ?int $courseId,
        ?Collection $sectionCandidates,
    ): Collection {
        $ids = $users->pluck('id');

        $events = new Collection;

        foreach ($this->sourcesFor($type, $courseId) as $source) {
            $events = $events->concat(match ($source) {
                'activity' => $this->activityEvents($ids, $from, $to, $type),
                'xp' => $this->xpEvents($ids, $from, $to, $type, $courseId),
                'assessment' => $this->assessmentEvents($ids, $type, $courseId),
                'section' => $this->sectionCompletionEvents($sectionCandidates ?? $users, $from, $to, $courseId),
                default => new Collection,
            });
        }

        return $events;
    }

    /**
     * Which sources can emit beats, given the event-type filter. A course
     * filter drops the Activity source entirely: activity rows carry no course
     * link (no mission/course columns), so attributing them to a course would
     * be guesswork — exclude rather than mis-attribute.
     *
     * @return list<string>
     */
    private function sourcesFor(?string $type, ?int $courseId = null): array
    {
        $sources = match ($type) {
            null => ['activity', 'xp', 'assessment', 'section'],
            'mission_completed', 'wrong_submission', KnowledgeCheckService::ACTIVITY_TYPE_COMPLETED => ['activity'],
            'hint_used', 'solution_revealed', 'assessment_completed' => ['xp'],
            'assessment_passed', 'assessment_failed' => ['assessment'],
            'section_completed' => ['section'],
            default => [],
        };

        if ($courseId !== null) {
            $sources = array_values(array_filter(
                $sources,
                fn (string $source): bool => $source !== 'activity',
            ));
        }

        return $sources;
    }

    /**
     * @param  Collection<int, int>  $ids
     * @return Collection<int, array{at: Carbon, label: string, type: string, pts: int<0, max>|null, seq: int, user_id: int<0, max>}>
     */
    private function activityEvents(
        Collection $ids,
        ?Carbon $from = null,
        ?Carbon $to = null,
        ?string $type = null,
    ): Collection {
        return Activity::query()
            ->whereIn('user_id', $ids)
            ->whereNotIn('type', self::NON_LEARNING_ACTIVITY_TYPES)
            ->when($from !== null && $to !== null, fn (Builder $query) => $query->whereBetween('created_at', [$from, $to]))
            ->when($type !== null, fn (Builder $query) => $query->where('type', $type))
            ->orderBy('created_at')
            ->get()
            ->map(function (Activity $activity): array {
                return [
                    'at' => Carbon::parse($activity->created_at),
                    'label' => $activity->message,
                    'type' => $activity->type,
                    'pts' => $activity->pts,
                    'seq' => ++$this->seq,
                    'user_id' => $activity->user_id,
                ];
            });
    }

    /**
     * @param  Collection<int, int>  $ids
     * @return Collection<int, array{at: Carbon, label: string, type: string, pts: int, seq: int, user_id: int<0, max>}>
     */
    private function xpEvents(
        Collection $ids,
        ?Carbon $from = null,
        ?Carbon $to = null,
        ?string $type = null,
        ?int $courseId = null,
    ): Collection {
        return XpTransaction::query()
            ->whereIn('user_id', $ids)
            ->whereIn('type', self::XP_BEAT_TYPES)
            ->when($from !== null && $to !== null, fn (Builder $query) => $query->whereBetween('created_at', [$from, $to]))
            ->when($type !== null, fn (Builder $query) => $query->where('type', $type))
            ->when($courseId !== null, fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->whereHas('mission', fn (Builder $query) => $query->where('course_id', $courseId))
                    ->orWhereHas('assessment', fn (Builder $query) => $query->where('course_id', $courseId)),
            ))
            ->orderBy('created_at')
            ->get()
            ->map(function (XpTransaction $txn): array {
                return [
                    'at' => Carbon::parse($txn->created_at),
                    'label' => $txn->description ?? $txn->type,
                    'type' => $txn->type,
                    'pts' => $txn->amount,
                    'seq' => ++$this->seq,
                    'user_id' => $txn->user_id,
                ];
            });
    }

    /**
     * @param  Collection<int, int>  $ids
     * @return Collection<int, array{at: Carbon, label: non-falsy-string, type: 'assessment_failed'|'assessment_passed', pts: null, seq: int, user_id: int<0, max>}>
     */
    private function assessmentEvents(Collection $ids, ?string $type = null, ?int $courseId = null): Collection
    {
        return AssessmentAttempt::query()
            ->with('assessment')
            ->whereIn('user_id', $ids)
            ->when(
                $type === null,
                fn (Builder $query) => $query->whereIn('status', ['passed', 'failed']),
                fn (Builder $query) => $query->where('status', $type === 'assessment_passed' ? 'passed' : 'failed'),
            )
            ->when($courseId !== null, fn (Builder $query) => $query->whereHas(
                'assessment',
                fn (Builder $query) => $query->where('course_id', $courseId),
            ))
            ->orderBy('created_at')
            ->get()
            ->map(function (AssessmentAttempt $attempt): array {
                $passed = $attempt->status === 'passed';
                $title = $attempt->assessment->title ?? 'Boss Challenge';
                $score = $attempt->score !== null ? " (score {$attempt->score})" : '';

                return [
                    'at' => Carbon::parse($passed
                        ? ($attempt->passed_at ?? $attempt->created_at)
                        : ($attempt->submitted_at ?? $attempt->created_at)),
                    'label' => "Boss Challenge {$this->verb($passed)}: {$title}{$score}",
                    'type' => $passed ? 'assessment_passed' : 'assessment_failed',
                    'pts' => null,
                    'seq' => ++$this->seq,
                    'user_id' => $attempt->user_id,
                ];
            });
    }

    /**
     * One beat per section that is fully complete today, timestamped when its
     * last mission was finished. Section enumeration and the DONE state come
     * from the shared build() traversal; only the missing timestamp is read
     * from the completion rows. Runs the shared traversal once per candidate
     * student (bounded in feed() to students with in-window progress).
     *
     * @param  Collection<int, User>  $users
     * @return Collection<int, array{at: Carbon, label: string, type: string, pts: null, seq: int, user_id: int}>
     */
    private function sectionCompletionEvents(
        Collection $users,
        ?Carbon $from,
        ?Carbon $to,
        ?int $courseId = null,
    ): Collection {
        $events = new Collection;

        foreach ($users as $user) {
            $doneSections = $this->doneSections($user, $courseId);

            if ($doneSections->isEmpty()) {
                continue;
            }

            $sectionIds = $doneSections->keys();

            $completions = Progress::query()
                ->where('user_id', $user->id)
                ->whereHas('mission', function (Builder $query) use ($sectionIds): void {
                    $query->whereIn('section_id', $sectionIds);
                })
                ->with('mission.section')
                ->orderBy('completed_at')
                ->get();

            $completions
                ->groupBy(fn (Progress $row): int => $row->mission?->section->id ?? -1)
                ->each(function (Collection $rows) use ($events, $user): void {
                    $latest = $rows->last();
                    $section = $latest?->mission?->section;

                    if ($latest === null || $section === null) {
                        return;
                    }

                    $events->push([
                        'at' => Carbon::parse($latest->completed_at),
                        'label' => 'Section complete: '.$section->title,
                        'type' => 'section_completed',
                        'pts' => null,
                        'seq' => ++$this->seq,
                        'user_id' => $user->id,
                    ]);
                });
        }

        return $events;
    }

    /**
     * Sections of a student's path that are fully complete today, optionally
     * scoped to one course. Same DONE definition as the path traversal.
     *
     * @return Collection<int, Section>
     */
    private function doneSections(User $user, ?int $courseId): Collection
    {
        return $this->path->build($user)
            ->flatMap(fn (array $courseRow): Collection => $courseRow['sections'])
            ->filter(fn (array $sectionRow): bool => $sectionRow['progress']['total'] > 0 && $sectionRow['progress']['percent'] === 100)
            ->map(fn (array $sectionRow): Section => $sectionRow['section'])
            ->when(
                $courseId !== null,
                fn (Collection $sections): Collection => $sections->filter(fn (Section $section): bool => $section->course_id === $courseId),
            )
            ->keyBy(fn (Section $section): int => $section->id);
    }

    private function verb(bool $passed): string
    {
        return $passed ? 'passed' : 'failed';
    }
}
