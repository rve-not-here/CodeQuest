<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\Skill;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\AssessmentAnalyticsService;
use App\Services\AssessmentService;
use App\Services\ChallengeAnalyticsService;
use App\Services\CompetencyService;
use App\Services\MissionService;
use App\Services\RecommendationService;
use App\Services\StudentProgressReportService;
use App\Services\TimelineService;
use App\Services\XpService;
use App\Support\ReportFilters;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * US-1002 student progress report. Hand-computed fixtures for every owned
 * scalar, and direct equality against the authoritative services for every
 * composed subsection.
 */
class StudentProgressReportTest extends TestCase
{
    use RefreshDatabase;

    private static int $orderSequence = 7000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    private function course(string $slug, string $name = 'Course'): Course
    {
        self::$orderSequence++;

        $course = new Course([
            'slug' => $slug,
            'name' => $name.' '.self::$orderSequence,
            'type' => 'html',
            'status' => 'active',
            'order_num' => self::$orderSequence,
        ]);
        $course->save();

        return $course;
    }

    private function mission(Course $course, string $title, array $skillKeys = []): Mission
    {
        self::$orderSequence++;

        $section = new Section([
            'course_id' => $course->id,
            'order_num' => self::$orderSequence,
            'title' => 'Section '.self::$orderSequence,
        ]);
        $section->save();

        $mission = new Mission([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => self::$orderSequence,
            'title' => $title,
            'difficulty' => 'EASY',
            'points' => 50,
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'version' => 1,
        ]);
        $mission->save();

        if ($skillKeys !== []) {
            $ids = [];

            foreach ($skillKeys as $key) {
                $ids[] = Skill::query()->firstOrCreate(['key' => $key], ['label' => $key])->id;
            }

            $mission->skills()->sync($ids);
        }

        return $mission->fresh();
    }

    private function assessment(Course $course): Assessment
    {
        return Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'passing_score' => 70,
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'SIGNAL']]),
        ]);
    }

    private function complete(User $user, Mission $mission): void
    {
        $result = app(MissionService::class)->submit($user, $mission, '<h1>Title</h1>');
        $this->assertTrue($result['passed']);
    }

    private function wrongSubmission(User $user, Mission $mission): void
    {
        DB::table('the404_xp_transactions')->insert([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => -10,
            'type' => XpService::TYPE_WRONG_SUBMISSION,
            'description' => 'Wrong submission on mission: '.$mission->title,
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    private function attemptAssessment(User $user, Course $course, string $code): void
    {
        $service = app(AssessmentService::class);
        $attempt = $service->beginAttempt($user, $course);
        $service->submitAttempt($user, $attempt, $code);
        $service->evaluateAttempt($user, $attempt);
    }

    private function filters(array $input = []): ReportFilters
    {
        return ReportFilters::fromArray($input, []);
    }

    private function report(User $student, ?array $courseIds = null, array $input = []): array
    {
        return app(StudentProgressReportService::class)->forStudent(
            $student,
            $courseIds === null ? null : collect($courseIds),
            $this->filters($input),
        );
    }

    public function test_empty_student_reports_empty_sections_and_zero_balance(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $report = $this->report($student);

        $this->assertSame($student->id, $report['student_id']);
        $this->assertNull($report['progress']['current_course_id']);
        $this->assertNull($report['progress']['next_mission']);
        $this->assertSame([], $report['progress']['courses']);
        $this->assertSame([], $report['competency']['skills']);
        $this->assertSame([], $report['competency']['weak_skills']);
        $this->assertSame(0, $report['xp']['balance']);
        $this->assertSame(0, $report['achievements']['earned']);
        $this->assertSame(0, $report['achievements']['streak']);
        $this->assertSame([], $report['timeline']);
        $this->assertSame(0, $report['period']['completions']);
        $this->assertSame(0, $report['period']['wrong_submissions']);
        $this->assertSame(0, $report['period']['assessment_attempts']);
        $this->assertSame(0, $report['period']['assessment_passes']);
    }

    public function test_partial_progress_names_current_course_and_next_mission(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $first = $this->mission($course, 'Alpha One', ['html.headings']);
        $second = $this->mission($course, 'Alpha Two', ['html.headings']);
        $this->complete($student, $first);

        $report = $this->report($student);

        $this->assertSame($course->id, $report['progress']['current_course_id']);
        $this->assertSame($course->id, $report['progress']['next_mission']['course_id']);
        $this->assertSame($second->id, $report['progress']['next_mission']['mission_id']);
        $this->assertSame('Alpha Two', $report['progress']['next_mission']['title']);

        $this->assertCount(1, $report['progress']['courses']);
        $row = $report['progress']['courses'][0];
        $this->assertSame($course->id, $row['course_id']);
        $this->assertSame(1, $row['completed_missions']);
        $this->assertSame(2, $row['total_missions']);
        $this->assertSame(50, $row['percent']);
        $this->assertFalse($row['challenge_passed']);

        $this->assertSame(1, $report['period']['completions']);
        $this->assertGreaterThan(0, $report['xp']['balance']);
        $this->assertNotEmpty($report['timeline']);
    }

    public function test_completed_course_advances_current_course_and_marks_passed(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One');
        $missionB = $this->mission($courseB, 'Beta One');
        $this->assessment($courseA);
        $this->complete($student, $missionA);
        $this->attemptAssessment($student, $courseA, 'SIGNAL answer');

        $report = $this->report($student);

        // A passed Boss Challenge completes the course: current moves on.
        $this->assertSame($courseB->id, $report['progress']['current_course_id']);
        $this->assertSame($missionB->id, $report['progress']['next_mission']['mission_id']);

        $byCourse = collect($report['progress']['courses'])->keyBy('course_id');
        $this->assertTrue($byCourse[$courseA->id]['challenge_passed']);
        $this->assertSame('demonstrated', $byCourse[$courseA->id]['state']);
        $this->assertFalse($byCourse[$courseB->id]['challenge_passed']);

        $this->assertSame(1, $report['period']['assessment_attempts']);
        $this->assertSame(1, $report['period']['assessment_passes']);
    }

    public function test_challenge_and_assessment_sections_match_authoritative_services(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->assessment($course);
        $this->complete($student, $mission);
        $this->wrongSubmission($student, $mission);
        $this->attemptAssessment($student, $course, 'SIGNAL answer');

        $report = $this->report($student);
        $ids = collect([$student->id]);

        $this->assertSame(app(ChallengeAnalyticsService::class)->summarize($ids, null, $this->filters()), $report['challenges']);
        $this->assertSame(app(AssessmentAnalyticsService::class)->summarize($ids, null, $this->filters()), $report['assessments']);

        $this->assertSame(1, $report['challenges']['completions']);
        $this->assertSame(1, $report['challenges']['failures']);
        $this->assertSame(1, $report['assessments']['attempts']);
        $this->assertSame(1, $report['assessments']['passes']);
    }

    public function test_competency_matches_authoritative_service(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One', ['html.headings']);
        $this->complete($student, $mission);

        $report = $this->report($student);

        $expected = app(CompetencyService::class)->skills($student);
        $this->assertCount($expected->count(), $report['competency']['skills']);

        foreach ($report['competency']['skills'] as $index => $row) {
            $source = $expected[$index];
            $this->assertSame($source['key'], $row['key']);
            $this->assertSame($source['percentage'], $row['percentage']);
            $this->assertSame($source['state'], $row['state']);
            $this->assertSame($source['weak'], $row['weak']);
        }

        $weak = app(CompetencyService::class)->weakSkills($student);
        $this->assertSame($weak->pluck('key')->all(), $report['competency']['weak_skills']);
    }

    public function test_xp_achievements_timeline_recommendations_match_owners(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->complete($student, $mission);

        $report = $this->report($student);

        $this->assertSame(app(XpService::class)->balance($student), $report['xp']['balance']);

        $catalog = app(AchievementService::class)->catalog($student);
        $this->assertSame($catalog->where('awarded', true)->count(), $report['achievements']['earned']);
        $this->assertSame($catalog->count(), $report['achievements']['total']);
        $this->assertSame(app(AchievementService::class)->currentStreak($student), $report['achievements']['streak']);
        $this->assertEquals($catalog->all(), $report['achievements']['items']);

        $this->assertEquals(app(TimelineService::class)->events($student, 20, null)->all(), $report['timeline']);
        $this->assertEquals(app(RecommendationService::class)->recommendations($student, null)->all(), $report['recommendations']);
    }

    public function test_date_range_narrows_activity_without_touching_state(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $april = $this->mission($course, 'April Mission');
        $may = $this->mission($course, 'May Mission');
        $this->complete($student, $april);
        $this->complete($student, $may);

        DB::table('the404_progress')->where('mission_id', $april->id)->update(['completed_at' => '2026-04-10 09:00:00']);
        DB::table('the404_progress')->where('mission_id', $may->id)->update(['completed_at' => '2026-05-10 09:00:00']);

        $balance = app(XpService::class)->balance($student);
        $report = $this->report($student, null, ['from' => '2026-05-01', 'to' => '2026-05-31']);

        // State stays cumulative (2 of 2, same balance); only activity narrows.
        $this->assertSame(2, $report['progress']['courses'][0]['completed_missions']);
        $this->assertSame($balance, $report['xp']['balance']);
        $this->assertSame(1, $report['period']['completions']);
        $this->assertSame('2026-05-01 00:00:00', $report['period']['from']);
        $this->assertSame('2026-06-01 00:00:00', $report['period']['to_exclusive']);
    }

    public function test_excluding_range_leaves_timeline_and_recommendations_on_owner_semantics(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->complete($student, $mission);

        // Neither owner accepts a selected range: timeline is the recent
        // beats, recommendations are the current cards. Pin that a period
        // excluding all recent evidence narrows the period sections while
        // both subsections stay exactly equal to their authoritative
        // outputs — unchanged by design, not by accident.
        $report = $this->report($student, null, ['from' => '2026-04-01', 'to' => '2026-04-30']);

        $this->assertSame(0, $report['period']['completions']);
        $this->assertNotEmpty($report['timeline']);
        $this->assertEquals(app(TimelineService::class)->events($student, 20, null)->all(), $report['timeline']);
        $this->assertEquals(app(RecommendationService::class)->recommendations($student, null)->all(), $report['recommendations']);
    }

    public function test_course_filter_narrows_scoped_sections(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One', ['html.headings']);
        $missionB = $this->mission($courseB, 'Beta One', ['css.color']);
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);

        $report = $this->report($student, null, ['course_id' => $courseB->id]);

        $this->assertCount(1, $report['progress']['courses']);
        $this->assertSame($courseB->id, $report['progress']['courses'][0]['course_id']);
        $this->assertSame($courseB->id, $report['progress']['current_course_id']);
        $this->assertCount(1, $report['competency']['skills']);
        $this->assertSame(1, $report['period']['completions']);
    }

    public function test_mismatched_student_filter_fails_closed(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->complete($student, $mission);
        $this->wrongSubmission($student, $mission);

        $otherMission = $this->mission($course, 'Other Work');
        $this->complete($other, $otherMission);

        // The other id is a valid student, so the filter validates but the
        // report must expose no subject facts at all — neither student's.
        $report = $this->report($student, null, ['student_id' => $other->id]);

        $this->assertSame($student->id, $report['student_id']);
        $this->assertNull($report['progress']['current_course_id']);
        $this->assertNull($report['progress']['next_mission']);
        $this->assertSame([], $report['progress']['courses']);
        $this->assertSame([], $report['competency']['skills']);
        $this->assertSame([], $report['competency']['weak_skills']);
        $this->assertSame(0, $report['challenges']['completions']);
        $this->assertSame(0, $report['challenges']['attempts']);
        $this->assertSame(0, $report['assessments']['attempts']);
        $this->assertSame(0, $report['xp']['balance']);
        $this->assertSame(0, $report['achievements']['earned']);
        $this->assertSame(0, $report['achievements']['total']);
        $this->assertSame(0, $report['achievements']['streak']);
        $this->assertSame([], $report['achievements']['items']);
        $this->assertSame([], $report['timeline']);
        $this->assertSame([], $report['recommendations']);
        $this->assertSame(0, $report['period']['completions']);
        $this->assertSame(0, $report['period']['wrong_submissions']);
        $this->assertSame(0, $report['period']['assessment_attempts']);
        $this->assertSame(0, $report['period']['assessment_passes']);

        // The subject's own normal report is untouched and non-empty, so the
        // neutral shape above cannot be mistaken for it.
        $normal = $this->report($student);
        $this->assertNotEmpty($normal['progress']['courses']);
        $this->assertGreaterThan(0, $normal['xp']['balance']);
    }

    public function test_out_of_scope_course_filter_fails_closed(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One');
        $missionB = $this->mission($courseB, 'Beta One');
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);

        // Authorized scope holds A only; requesting B fails closed.
        $report = $this->report($student, [$courseA->id], ['course_id' => $courseB->id]);

        $this->assertSame([], $report['progress']['courses']);
        $this->assertNull($report['progress']['current_course_id']);
        $this->assertSame([], $report['competency']['skills']);
        $this->assertSame(0, $report['period']['completions']);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $teacher = User::factory()->teacher()->create();

        foreach ([
            ['status' => 'active'],
            ['student_id' => 999999],
            ['student_id' => $teacher->id],
            ['course_id' => 999999],
        ] as $input) {
            try {
                $this->filters($input);
                $this->fail('Expected ValidationException for '.json_encode($input));
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
    }

    public function test_live_metadata_edits_leave_stored_evidence_untouched(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One', ['html.headings']);
        $this->assessment($course);
        $this->complete($student, $mission);
        $this->wrongSubmission($student, $mission);
        $this->attemptAssessment($student, $course, 'SIGNAL answer');

        $before = $this->report($student);

        $course->update(['name' => 'Renamed Course']);
        $mission->update(['title' => 'Renamed Mission', 'difficulty' => 'HARD']);
        Skill::query()->where('key', 'html.headings')->update(['label' => 'Renamed Headings']);

        $after = $this->report($student);

        // Stored period evidence is historical fact: nothing moves.
        $this->assertSame($before['period'], $after['period']);

        // The live skill label follows current metadata while every figure
        // still equals its authoritative source after the edit.
        $this->assertSame('Renamed Headings', $after['competency']['skills'][0]['label']);

        $expected = app(CompetencyService::class)->skills($student);
        $this->assertSame($expected[0]['percentage'], $after['competency']['skills'][0]['percentage']);
        $this->assertSame($before['progress']['courses'], $after['progress']['courses']);
        $this->assertSame($before['xp'], $after['xp']);
    }

    public function test_query_count_stays_bounded_as_history_grows(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');

        foreach (range(1, 3) as $i) {
            $mission = $this->mission($course, "M{$i}");
            $this->complete($student, $mission);
        }

        $service = app(StudentProgressReportService::class);
        $queries = $this->countQueries(fn () => $service->forStudent($student));

        $this->assertLessThanOrEqual(60, $queries);

        $courseB = $this->course('beta', 'Beta');

        foreach (range(1, 6) as $i) {
            $mission = $this->mission($courseB, "B{$i}");
            $this->complete($student, $mission);
        }

        $grown = $this->countQueries(fn () => $service->forStudent($student));

        $this->assertLessThanOrEqual($queries + 8, $grown);
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
}
