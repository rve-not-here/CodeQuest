<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The student Performance Summary: one register that shows the three result
 * types together — Coding Challenges, Knowledge Checks, and Boss Challenges.
 *
 * Every figure is borrowed from the service that owns it. This class only
 * joins them per course, so the page can never invent a fourth definition of
 * "completed", "passed", or "correct":
 *
 *   - state / challenge completion  CourseProgressService::overview()
 *   - Boss record                   AssessmentService::attemptHistoriesForCourses()
 *   - Knowledge Check evidence      KnowledgeCheckService::courseRegister()
 *
 * Scope is the student's own record; no classroom or reporting scope is
 * applied here. Minimum documented view only: no trends, exports, or
 * derived scores beyond the recorded attempt values.
 */
class PerformanceSummaryService
{
    public function __construct(
        private readonly CourseProgressService $progress,
        private readonly AssessmentService $assessments,
        private readonly KnowledgeCheckService $checks,
    ) {}

    /**
     * @return Collection<int, array{
     *     course: Course,
     *     state: string,
     *     challenges: array{completed: int, total: int, percent: int},
     *     knowledge_checks: array{published: int, submitted: int, correct: int, questions: int},
     *     boss: array{attempts: int, passed: bool, best: int|null, latest: string|null, latest_score: int|null},
     * }>
     */
    public function forStudent(User $user): Collection
    {
        $rows = $this->progress->overview($user);

        if ($rows->isEmpty()) {
            return collect();
        }

        $courses = $rows->pluck('course');
        $histories = $this->assessments->attemptHistoriesForCourses($user, $courses);
        $checks = $this->checks->courseRegister($user, $courses->pluck('id')->values());

        return $rows->map(function (array $row) use ($histories, $checks): array {
            $history = $histories->get($row['course']->id, collect());
            $latest = $history->first();
            $scores = $history
                ->pluck('score')
                ->filter(fn (mixed $score): bool => $score !== null);

            return [
                'course' => $row['course'],
                'state' => $row['state'],
                'challenges' => $row['progress'],
                'knowledge_checks' => $checks->get($row['course']->id) ?? [
                    'published' => 0,
                    'submitted' => 0,
                    'correct' => 0,
                    'questions' => 0,
                ],
                'boss' => [
                    'attempts' => $history->count(),
                    // Ever passed, exactly as AssessmentService::hasPassed():
                    // a failed retry never erases a completed course.
                    'passed' => $history->contains(fn (array $attempt): bool => $attempt['status'] === 'passed'),
                    'best' => $scores->isEmpty() ? null : (int) $scores->max(),
                    'latest' => $latest['status'] ?? null,
                    'latest_score' => $latest['score'] ?? null,
                ],
            ];
        })->values();
    }
}
