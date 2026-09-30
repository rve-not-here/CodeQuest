<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Services\CompetencyService;
use App\Services\RecommendationService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * US-911 Recommendation Performance. Query-count and growth bounds over the
 * recommendation and competency read paths, plus output-equivalence anchors
 * proving the batched reads return exactly what the per-course reads did.
 *
 * Fixtures avoid faker entirely (definition-time unique() pools exhaust on
 * large curricula) and use explicit slugs, names, and orderings so both
 * database engines build identical data.
 */
class RecommendationPerformanceTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    /**
     * @return array{user: User, courses: array<int, Course>}
     */
    private function fixture(int $courses, int $missionsPerCourse, string $tag = ''): array
    {
        $user = User::factory()->create(['role' => 'student']);
        $built = [];

        for ($c = 1; $c <= $courses; $c++) {
            $course = Course::query()->create([
                'slug' => "perf-course-{$tag}{$courses}x{$missionsPerCourse}-{$c}",
                'name' => "Perf Course {$c}",
                'type' => 'html',
                'status' => 'active',
                'order_num' => $c,
                'version' => 1,
            ]);
            $assessment = Assessment::query()->create([
                'course_id' => $course->id,
                'title' => "Boss {$c}",
                'grading_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
                'passing_score' => 70,
                'status' => 'active',
                'version' => 1,
            ]);

            $perSection = (int) ceil($missionsPerCourse / 2);

            for ($m = 1; $m <= $missionsPerCourse; $m++) {
                $sectionIndex = (int) (($m - 1) / $perSection);
                $section = Section::query()->create([
                    'course_id' => $course->id,
                    'order_num' => $sectionIndex + 1,
                    'title' => "Section {$sectionIndex}",
                    'version' => 1,
                ]);
                $mission = Mission::query()->create([
                    'course_id' => $course->id,
                    'section_id' => $section->id,
                    'order_num' => $m,
                    'title' => "Mission {$c}-{$m}",
                    'difficulty' => 'EASY',
                    'points' => 50,
                    'version' => 1,
                ]);

                if ($m <= $missionsPerCourse / 2) {
                    Progress::query()->create([
                        'user_id' => $user->id,
                        'mission_id' => $mission->id,
                        'pts_earned' => 10,
                        'completed_at' => now(),
                    ]);
                }

                // Exactly three missions qualify for the review queue on every
                // fixture size (two in the final course, one in the first),
                // so queue membership never depends on timestamp ties.
                if (($c === $courses && $m <= 2) || ($c === 1 && $m === 2)) {
                    for ($w = 0; $w < 3; $w++) {
                        DB::table('the404_xp_transactions')->insert([
                            'user_id' => $user->id,
                            'mission_id' => $mission->id,
                            'amount' => -10,
                            'type' => XpService::TYPE_WRONG_SUBMISSION,
                            'description' => 'probe',
                            'created_at' => now()->toDateTimeString(),
                        ]);
                    }
                }
            }

            $built[] = ['course' => $course, 'assessment' => $assessment];
        }

        // All courses but the last are fully cleared, so the student's
        // position sits at the final course and every pass-history scan
        // walks the whole catalog: the shape that exposes per-course fan-out.
        foreach ($built as $index => $row) {
            if ($index === count($built) - 1) {
                continue;
            }

            foreach ($row['course']->missions as $mission) {
                Progress::query()->firstOrCreate(
                    ['user_id' => $user->id, 'mission_id' => $mission->id],
                    ['pts_earned' => 10, 'completed_at' => now()]
                );
            }

            DB::table('the404_assessment_attempts')->insert([
                'assessment_id' => $row['assessment']->id,
                'user_id' => $user->id,
                'score' => 100,
                'status' => 'passed',
                'passed_at' => now()->toDateTimeString(),
                'submitted_at' => now()->toDateTimeString(),
                'created_at' => now()->toDateTimeString(),
            ]);
        }

        return ['user' => $user, 'courses' => array_column($built, 'course')];
    }

    private function countQueries(callable $fn): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $fn();
        $count = count(DB::getQueryLog());
        DB::flushQueryLog();
        DB::disableQueryLog();

        return $count;
    }

    public function test_recommendation_and_competency_query_counts_are_bounded(): void
    {
        ['user' => $user, 'courses' => $courses] = $this->fixture(2, 4);

        $recommendations = app(RecommendationService::class);
        $competency = app(CompetencyService::class);
        $ids = collect($courses)->pluck('id');

        // Review cards now enrich reasons through one grouped weak-skills
        // read (US-906), so the review path costs a constant few queries
        // more than the position-only path. Bounds stay tight: a per-course
        // regression would still add ~10 here. Growth tests below are the
        // real fan-out guard.
        $this->assertLessThanOrEqual(20, $this->countQueries(fn () => $recommendations->recommendations($user)));
        $this->assertLessThanOrEqual(22, $this->countQueries(fn () => $recommendations->recommendations($user, $ids)));
        $this->assertLessThanOrEqual(14, $this->countQueries(fn () => $competency->overview($user)));
        $this->assertLessThanOrEqual(14, $this->countQueries(fn () => $competency->overview($user, $ids)));
    }

    public function test_query_counts_do_not_grow_with_curriculum_size(): void
    {
        ['user' => $smallUser, 'courses' => $smallCourses] = $this->fixture(2, 4, 'small-');
        ['user' => $largeUser, 'courses' => $largeCourses] = $this->fixture(6, 12, 'large-');

        $recommendations = app(RecommendationService::class);
        $competency = app(CompetencyService::class);

        // 3.6x curriculum growth (8 to 72 missions, 2 to 6 courses) must not
        // add anywhere near one query per course or mission. The +4 tolerance
        // absorbs engine-level counting noise, not fan-out.
        $this->assertLessThanOrEqual(
            $this->countQueries(fn () => $recommendations->recommendations($smallUser)) + 4,
            $this->countQueries(fn () => $recommendations->recommendations($largeUser))
        );
        $this->assertLessThanOrEqual(
            $this->countQueries(fn () => $competency->overview($smallUser)) + 4,
            $this->countQueries(fn () => $competency->overview($largeUser))
        );

        $smallIds = collect($smallCourses)->pluck('id');
        $largeIds = collect($largeCourses)->pluck('id');

        $this->assertLessThanOrEqual(
            $this->countQueries(fn () => $recommendations->recommendations($smallUser, $smallIds)) + 4,
            $this->countQueries(fn () => $recommendations->recommendations($largeUser, $largeIds))
        );
        $this->assertLessThanOrEqual(
            $this->countQueries(fn () => $competency->overview($smallUser, $smallIds)) + 4,
            $this->countQueries(fn () => $competency->overview($largeUser, $largeIds))
        );
    }

    public function test_teacher_scoped_reads_stay_bounded_and_isolated(): void
    {
        ['user' => $user, 'courses' => $courses] = $this->fixture(2, 4);
        $teacher = User::factory()->teacher()->create();
        $this->classroomFor($teacher, [$user], [$courses[0]]);

        $recommendations = app(RecommendationService::class);
        $scope = collect([$courses[0]->id]);

        $this->assertLessThanOrEqual(18, $this->countQueries(fn () => $recommendations->recommendations($user, $scope)));

        $cards = $recommendations->recommendations($user, $scope);

        foreach ($cards as $card) {
            $this->assertStringNotContainsString('Mission 2-', $card['subtitle']);
        }

        $content = $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $user->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Mission 2-', $content);
    }

    public function test_recommendation_output_matches_the_spec_defined_expectation(): void
    {
        ['user' => $user] = $this->fixture(2, 4);

        $cards = app(RecommendationService::class)->recommendations($user);

        // Position card: first unfinished mission of the final course.
        $this->assertSame(1, $cards[0]['slot']);
        $this->assertSame('Continue Learning', $cards[0]['title']);
        $this->assertSame('Mission 2-3 · Perf Course 2', $cards[0]['subtitle']);
        $this->assertSame('CONTINUE', $cards[0]['cta']);

        // Review queue: completed missions with 3 recent wrong submissions,
        // capped at 3, order aside (same-second timestamps tie).
        $this->assertSame('Review', $cards[1]['title']);
        $this->assertEqualsCanonicalizing(
            ['Mission 2-2 · Perf Course 2', 'Mission 2-1 · Perf Course 2', 'Mission 1-2 · Perf Course 1'],
            $cards->slice(1)->map(fn (array $card): string => $card['subtitle'])->values()->all()
        );
        $this->assertCount(4, $cards);
    }

    public function test_competency_output_matches_the_spec_defined_expectation(): void
    {
        ['user' => $user] = $this->fixture(2, 4);

        $rows = app(CompetencyService::class)->overview($user)->map(fn (array $row): array => [
            'name' => $row['name'],
            'state' => $row['state'],
            'completed' => $row['completedMissions'],
            'total' => $row['totalMissions'],
            'percent' => $row['percent'],
            'wrong' => $row['wrongSubmissions'],
            'attempts' => $row['attempts'],
            'passed' => $row['challengePassed'],
        ])->all();

        $this->assertSame([
            ['name' => 'HTML', 'state' => 'demonstrated', 'completed' => 4, 'total' => 4, 'percent' => 100, 'wrong' => 3, 'attempts' => 1, 'passed' => true],
            ['name' => 'HTML', 'state' => 'practicing', 'completed' => 2, 'total' => 4, 'percent' => 50, 'wrong' => 6, 'attempts' => 0, 'passed' => false],
        ], $rows);
    }

    public function test_page_query_counts_do_not_grow_with_curriculum_size(): void
    {
        ['user' => $smallUser, 'courses' => $smallCourses] = $this->fixture(2, 4, 'page-small-');
        ['user' => $largeUser, 'courses' => $largeCourses] = $this->fixture(6, 12, 'page-large-');

        $teacher = User::factory()->teacher()->create();
        $this->classroomFor($teacher, [$smallUser], $smallCourses);

        $otherTeacher = User::factory()->teacher()->create();
        $this->classroomFor($otherTeacher, [$largeUser], $largeCourses);

        $this->assertLessThanOrEqual(
            $this->countQueries(fn () => $this->actingAs($smallUser)->get(route('recommendations'))->assertOk()) + 4,
            $this->countQueries(fn () => $this->actingAs($largeUser)->get(route('recommendations'))->assertOk())
        );
        $this->assertLessThanOrEqual(
            $this->countQueries(fn () => $this->actingAs($smallUser)->get(route('learning-path'))->assertOk()) + 6,
            $this->countQueries(fn () => $this->actingAs($largeUser)->get(route('learning-path'))->assertOk())
        );
        $this->assertLessThanOrEqual(
            $this->countQueries(fn () => $this->actingAs($teacher)->get(route('student-progress', ['student' => $smallUser->id]))->assertOk()) + 6,
            $this->countQueries(fn () => $this->actingAs($otherTeacher)->get(route('student-progress', ['student' => $largeUser->id]))->assertOk())
        );
    }
}
