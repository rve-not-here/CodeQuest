<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\AssessmentAttempt;
use App\Models\Progress;
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
 * pushed down, then the merged beats are sorted and paginated in memory.
 * Section-completion evidence is batched through LearningPathService's
 * shared all-missions-complete rule; feed() limits that work to students
 * with progress rows inside the queried window.
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
     * The student's Unified Learning Timeline (US-505). When a monitorable
     * course set is supplied it also serves scoped teacher surfaces (the
     * roster's Last activity column): beats are confined to those courses and
     * the un-attributable Activity source is dropped, exactly as feed() does
     * for a classroom-scoped teacher. Null keeps the student's own full view.
     *
     * @param  Collection<int, int>|null  $allowedCourseIds
     * @return Collection<int, array{at: Carbon, label: string, type: string, pts: int|null, seq: int}>
     */
    public function events(User $user, int $limit = 50, ?Collection $allowedCourseIds = null): Collection
    {
        $studentCourseScopes = $allowedCourseIds !== null
            ? [$user->id => $allowedCourseIds]
            : null;

        $events = $this->compose(collect([$user]), null, null, null, null, collect([$user]), $studentCourseScopes);

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
     * The newest beat for each supplied account. Uses the same source
     * composition and ordering as events(), but reads shared sources once.
     *
     * @param  Collection<int, User>  $users
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes
     * @return Collection<int, array{at: Carbon, label: string, type: string, pts: int|null, seq: int, user_id: int}>
     */
    public function latestForUsers(Collection $users, ?array $studentCourseScopes = null): Collection
    {
        if ($users->isEmpty()) {
            return collect();
        }

        $this->seq = 0;

        return $this->compose($users, null, null, null, null, $users, $studentCourseScopes)
            ->groupBy('user_id')
            ->map(fn (Collection $events): array => $events
                ->sortByDesc(fn (array $event): array => [$event['at']->getTimestamp(), $event['seq']])
                ->first() ?? throw new \LogicException('A grouped timeline user has no events.'));
    }

    /**
     * The teacher-facing activity feed (US-606): the same composed beats as
     * events(), parameterized over a set of students and filtered server-side.
     *
     * @param  Collection<int, int>|null  $studentIds  validated student-filter
     *                                                 ids, or null for the whole roster
     * @param  array<string, string>  $paginatorQuery  active filters preserved
     *                                                 across pagination
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes  per-student allowed-course
     *                                                                      ids delivered by ClassroomAccessService::scopesFor.
     *                                                                      When provided the feed is confined to the exact
     *                                                                      Student/Course pairs it encodes — a teacher sees a
     *                                                                      student's beat only when that beat is attributable to
     *                                                                      a course the teacher shares with the student.
     * @return LengthAwarePaginator<int, array{at: Carbon, label: string, type: string, pts: int|null, seq: int, user: array{id: int, username: string}}>
     */
    public function feed(
        ?Collection $studentIds,
        ?int $courseId,
        ?string $type,
        Carbon $from,
        Carbon $to,
        array $paginatorQuery,
        ?array $studentCourseScopes = null,
    ): LengthAwarePaginator {
        $this->seq = 0;

        if ($studentCourseScopes !== null && $studentCourseScopes === []) {
            $empty = new LengthAwarePaginator(
                [],
                0,
                self::FEED_PER_PAGE,
                Paginator::resolveCurrentPage(),
                ['path' => route('activity'), 'query' => $paginatorQuery],
            );

            return $empty;
        }

        $students = $this->students($studentIds);

        $sectionCandidates = $this->sectionCandidates($students, $from, $to);

        $events = $this->compose($students, $type, $from, $to, $courseId, $sectionCandidates, $studentCourseScopes)
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
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes
     * @return Collection<int, array{at: Carbon, label: string, type: string, pts: int|null, seq: int, user_id: int}>
     */
    private function compose(
        Collection $users,
        ?string $type,
        ?Carbon $from,
        ?Carbon $to,
        ?int $courseId,
        ?Collection $sectionCandidates,
        ?array $studentCourseScopes = null,
    ): Collection {
        $ids = $users->pluck('id');

        $events = new Collection;

        foreach ($this->sourcesFor($type, $courseId, $studentCourseScopes) as $source) {
            $events = $events->concat(match ($source) {
                'activity' => $this->activityEvents($ids, $from, $to, $type),
                'xp' => $this->xpEvents($ids, $from, $to, $type, $courseId, $studentCourseScopes),
                'assessment' => $this->assessmentEvents($ids, $type, $courseId, $studentCourseScopes),
                'section' => $this->sectionCompletionEvents($sectionCandidates ?? $users, $from, $to, $courseId, $studentCourseScopes),
                default => new Collection,
            });
        }

        return $events;
    }

    /**
     * Which sources can emit beats, given the event-type filter. A course
     * filter drops the Activity source entirely: activity rows carry no course
     * link (no mission/course columns), so attributing them to a course would
     * be guesswork — exclude rather than mis-attribute. A per-student course
     * scope (a classroom teacher) drops it for the same reason: the batched
     * teacher feed can attribute a beat to a Student/Course pair only where
     * the underlying record ties to a course.
     *
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes
     * @return list<string>
     */
    private function sourcesFor(?string $type, ?int $courseId = null, ?array $studentCourseScopes = null): array
    {
        $sources = match ($type) {
            null => ['activity', 'xp', 'assessment', 'section'],
            'mission_completed', 'wrong_submission', KnowledgeCheckService::ACTIVITY_TYPE_COMPLETED => ['activity'],
            'hint_used', 'solution_revealed', 'assessment_completed' => ['xp'],
            'assessment_passed', 'assessment_failed' => ['assessment'],
            'section_completed' => ['section'],
            default => [],
        };

        if ($courseId !== null || $studentCourseScopes !== null) {
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
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes
     * @return Collection<int, array{at: Carbon, label: string, type: string, pts: int, seq: int, user_id: int<0, max>}>
     */
    private function xpEvents(
        Collection $ids,
        ?Carbon $from = null,
        ?Carbon $to = null,
        ?string $type = null,
        ?int $courseId = null,
        ?array $studentCourseScopes = null,
    ): Collection {
        $query = XpTransaction::query()
            ->whereIn('user_id', $ids)
            ->whereIn('type', self::XP_BEAT_TYPES)
            ->when($from !== null && $to !== null, fn (Builder $query) => $query->whereBetween('created_at', [$from, $to]))
            ->when($type !== null, fn (Builder $query) => $query->where('type', $type))
            ->when($courseId !== null, fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->whereHas('mission', fn (Builder $query) => $query->where('course_id', $courseId))
                    ->orWhereHas('assessment', fn (Builder $query) => $query->where('course_id', $courseId)),
            ));

        if ($studentCourseScopes !== null) {
            $query = $this->whereScopedByCourseReference(
                $query,
                $studentCourseScopes,
                function (Builder $query, Collection $courseIds): void {
                    $query->where(
                        fn (Builder $query) => $query
                            ->whereHas('mission', fn (Builder $query) => $query->whereIn('course_id', $courseIds))
                            ->orWhereHas('assessment', fn (Builder $query) => $query->whereIn('course_id', $courseIds)),
                    );
                },
            );
        }

        return $query
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
     * Confine an Eloquent query to exact Student/Course pairs: each row must
     * belong to a student in the scope map and satisfy that student's course
     * constraint. The constraint callback receives the query and the student's
     * allowed course ids, and narrows the row to one of them.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, Collection<int, int>>  $studentCourseScopes
     * @param  callable(Builder<TModel>, Collection<int, int>): void  $withAllowedCourses
     * @return Builder<TModel>
     */
    private function whereScopedByCourseReference(Builder $query, array $studentCourseScopes, callable $withAllowedCourses): Builder
    {
        return $query->where(function (Builder $query) use ($studentCourseScopes, $withAllowedCourses): void {
            foreach ($studentCourseScopes as $userId => $courseIds) {
                $query->orWhere(function (Builder $query) use ($userId, $courseIds, $withAllowedCourses): void {
                    $query->where('user_id', $userId);
                    $withAllowedCourses($query, $courseIds);
                });
            }
        });
    }

    /**
     * @param  Collection<int, int>  $ids
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes
     * @return Collection<int, array{at: Carbon, label: non-falsy-string, type: 'assessment_failed'|'assessment_passed', pts: null, seq: int, user_id: int<0, max>}>
     */
    private function assessmentEvents(Collection $ids, ?string $type = null, ?int $courseId = null, ?array $studentCourseScopes = null): Collection
    {
        $query = AssessmentAttempt::query()
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
            ));

        if ($studentCourseScopes !== null) {
            $query = $this->whereScopedByCourseReference(
                $query,
                $studentCourseScopes,
                function (Builder $query, Collection $courseIds): void {
                    $query->whereHas(
                        'assessment',
                        fn (Builder $query) => $query->whereIn('course_id', $courseIds),
                    );
                },
            );
        }

        return $query
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
     * last mission was finished. LearningPathService batches section/mission
     * membership and completion rows across the candidate students, using
     * the same all-missions-complete rule as build().
     *
     * @param  Collection<int, User>  $users
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes
     * @return Collection<int, array{at: Carbon, label: string, type: string, pts: null, seq: int, user_id: int}>
     */
    private function sectionCompletionEvents(
        Collection $users,
        ?Carbon $from,
        ?Carbon $to,
        ?int $courseId = null,
        ?array $studentCourseScopes = null,
    ): Collection {
        return $this->path->completedSectionBeatsForUsers($users, $studentCourseScopes)
            ->filter(fn (array $beat): bool => $courseId === null || $beat['section']->course_id === $courseId)
            ->map(fn (array $beat): array => [
                'at' => $beat['at'],
                'label' => 'Section complete: '.$beat['section']->title,
                'type' => 'section_completed',
                'pts' => null,
                'seq' => ++$this->seq,
                'user_id' => $beat['user_id'],
            ]);
    }

    private function verb(bool $passed): string
    {
        return $passed ? 'passed' : 'failed';
    }
}
