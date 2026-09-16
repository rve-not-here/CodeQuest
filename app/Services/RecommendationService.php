<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Personalized recommendations (US-509). Deterministic and non-AI: every
 * slot is derived server-side from the student's own real state, in the
 * §31.0 priority order:
 *
 *   1. current incomplete learning   -> the ResumeService position when a
 *      mission is unfinished (type 'mission'); the next mission to work on.
 *   2. required next curriculum item -> the ResumeService position when every
 *      mission is done but the Boss Challenge is outstanding (type 'course').
 *      Unlike ResumeService's CTA policy (US-504), this recommendation MAY
 *      deep-link assessment.show because it presents the required next action,
 *      not a resume.
 *   3. review-worthy past struggles  -> completed missions with at least
 *      REVIEW_WRONG_SUBMISSION_THRESHOLD wrong submissions in the last
 *      REVIEW_WINDOW_DAYS days. Restriction C: only missions the student has
 *      actually completed are considered, so live in-progress work never
 *      appears here (it stays in slot 1).
 *   4. next course after completion  -> the ResumeService position when the
 *      current course is untouched but an earlier active course has had its
 *      Boss Challenge passed.
 *
 * Slots 1, 2 and 4 are the three states of the SAME resume resolution, so at
 * most one of them fires; slot 3 is independent. The page therefore renders
 * at most one position card (slot 1, 2 or 4) plus up to REVIEW_LIMIT review
 * cards (slot 3). Rendering order honours §31.0 strictly: a slot-1 or slot-2
 * position renders first followed by the review cards (1/2 before 3), but the
 * review cards render before a slot-4 position (3 sorts before 4). When
 * nothing is applicable the collection is empty and the page shows the empty
 * state.
 */
class RecommendationService
{
    public const REVIEW_WINDOW_DAYS = 14;

    public const REVIEW_WRONG_SUBMISSION_THRESHOLD = 2;

    public const REVIEW_LIMIT = 3;

    public function __construct(
        private readonly ResumeService $resume,
        private readonly AssessmentService $assessments,
    ) {}

    /**
     * @return SupportCollection<int, array{
     *     slot: 1|2|3|4,
     *     title: string,
     *     subtitle: string,
     *     href: string,
     *     cta: string,
     * }>
     */
    public function recommendations(User $user): SupportCollection
    {
        $position = $this->positionCard($user);
        $review = $this->reviewCards($user);

        if ($position === null) {
            return $review->values();
        }

        if ($position['slot'] === 4) {
            return $review->push($position)->values();
        }

        return $review->prepend($position)->values();
    }

    /**
     * @return null|array{
     *     slot: 1|2|3|4,
     *     title: string,
     *     subtitle: string,
     *     href: string,
     *     cta: string,
     * }
     */
    private function positionCard(User $user): ?array
    {
        $resume = $this->resume->resolve($user);

        if ($resume === null) {
            return null;
        }

        $course = $resume['course'];

        if ($resume['type'] === 'course') {
            return $this->challengeCard($course);
        }

        if ($this->nextCourseAfterCompletion($user, $course)) {
            return $this->nextCourseCard($course);
        }

        $mission = $resume['mission'] ?? null;

        if ($mission !== null) {
            return $this->continueLearningCard($mission);
        }

        return $this->courseCard($course);
    }

    /**
     * Slot 2: the required next curriculum item. This is a recommendation
     * pointing at the required next action, so it deep-links assessment.show;
     * it is deliberately NOT subject to ResumeService's no-assessment-link
     * rule (US-504). Under content drift where the course has no assessment
     * row, the card falls back to the learning surface rather than
     * mislinking a null.
     *
     * @return array{
     *     slot: 1|2|3|4,
     *     title: string,
     *     subtitle: string,
     *     href: string,
     *     cta: string,
     * }
     */
    private function challengeCard(Course $course): array
    {
        $assessment = $course->assessment;

        return [
            'slot' => 2,
            'title' => 'Boss Challenge',
            'subtitle' => $course->name.' · '.($assessment->title ?? 'Take the Boss Challenge'),
            'href' => $assessment !== null ? route('assessment.show', $assessment) : route('learning-path'),
            'cta' => 'START CHALLENGE',
        ];
    }

    /**
     * Slot 1: the current incomplete learning position (US-504 resolution).
     *
     * @return array{
     *     slot: 1|2|3|4,
     *     title: string,
     *     subtitle: string,
     *     href: string,
     *     cta: string,
     * }
     */
    private function continueLearningCard(Mission $mission): array
    {
        return [
            'slot' => 1,
            'title' => 'Continue Learning',
            'subtitle' => $mission->title.' · '.($mission->course->name ?? ''),
            'href' => route('mission.show', $mission),
            'cta' => 'CONTINUE',
        ];
    }

    /**
     * Slot 1, course-level fallback for the impossible-content-drift case
     * where ResumeService reports a mission but it is missing from the data.
     *
     * @return array{
     *     slot: 1|2|3|4,
     *     title: string,
     *     subtitle: string,
     *     href: string,
     *     cta: string,
     * }
     */
    private function courseCard(Course $course): array
    {
        return [
            'slot' => 1,
            'title' => 'Continue Learning',
            'subtitle' => $course->name,
            'href' => route('learning-path'),
            'cta' => 'CONTINUE',
        ];
    }

    /**
     * Slot 4: the next course, shown only right after a completion — the
     * current course is untouched AND an earlier active course has had its
     * Boss Challenge passed. Once the student starts the next course (any
     * Progress row), slot 1 takes over the position.
     *
     * @return array{
     *     slot: 1|2|3|4,
     *     title: string,
     *     subtitle: string,
     *     href: string,
     *     cta: string,
     * }
     */
    private function nextCourseCard(Course $course): array
    {
        return [
            'slot' => 4,
            'title' => 'Next Course',
            'subtitle' => $course->name,
            'href' => route('learning-path'),
            'cta' => 'OPEN COURSE',
        ];
    }

    private function nextCourseAfterCompletion(User $user, Course $course): bool
    {
        $untouched = ! Progress::query()
            ->where('user_id', $user->id)
            ->whereIn('mission_id', $course->missions()->select('id'))
            ->exists();

        if (! $untouched) {
            return false;
        }

        return Course::query()
            ->where('status', 'active')
            ->where('order_num', '<', $course->order_num)
            ->get()
            ->contains(fn (Course $previous): bool => $this->assessments->hasPassed($user, $previous));
    }

    /**
     * Slot 3: the review queue (Option B + the confirmed restriction C).
     *
     * A mission qualifies when its wrong_submission journal rows over the
     * last REVIEW_WINDOW_DAYS days number at least REVIEW_WRONG_SUBMISSION_THRESHOLD.
     * The window is inclusive of exactly REVIEW_WINDOW_DAYS days ago
     * (created_at >= now()->subDays(REVIEW_WINDOW_DAYS)). Restriction C keeps
     * in-progress missions out of here: the count only considers missions the
     * student has a Progress row for. Cards are ordered by the most recent
     * wrong submission and capped at REVIEW_LIMIT.
     *
     * @return SupportCollection<int, array{
     *     slot: 1|2|3|4,
     *     title: string,
     *     subtitle: string,
     *     href: string,
     *     cta: string,
     * }>
     */
    private function reviewCards(User $user): SupportCollection
    {
        $windowStart = now()->subDays(self::REVIEW_WINDOW_DAYS);

        $qualified = XpTransaction::query()
            ->select('mission_id')
            ->selectRaw('max(created_at) as last_wrong_at')
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_WRONG_SUBMISSION)
            ->where('created_at', '>=', $windowStart)
            ->whereIn('mission_id', $this->completedMissionIds($user))
            ->groupBy('mission_id')
            ->havingRaw('count(*) >= ?', [self::REVIEW_WRONG_SUBMISSION_THRESHOLD])
            ->orderByDesc('last_wrong_at')
            ->limit(self::REVIEW_LIMIT)
            ->get();

        $missions = Mission::query()
            ->whereIn('id', $qualified->pluck('mission_id'))
            ->with('course')
            ->get()
            ->keyBy('id');

        $cards = new SupportCollection;

        foreach ($qualified as $row) {
            $card = $this->reviewCard($missions->get($row->mission_id));

            if ($card !== null) {
                $cards->push($card);
            }
        }

        return $cards;
    }

    /**
     * @return null|array{
     *     slot: 1|2|3|4,
     *     title: string,
     *     subtitle: string,
     *     href: string,
     *     cta: string,
     * }
     */
    private function reviewCard(?Mission $mission): ?array
    {
        if ($mission === null) {
            return null;
        }

        return [
            'slot' => 3,
            'title' => 'Review',
            'subtitle' => $mission->title.' · '.($mission->course->name ?? ''),
            'href' => route('mission.show', $mission),
            'cta' => 'REVIEW',
        ];
    }

    /**
     * @return array<int, int>
     */
    private function completedMissionIds(User $user): array
    {
        return Progress::query()
            ->where('user_id', $user->id)
            ->pluck('mission_id')
            ->all();
    }
}
