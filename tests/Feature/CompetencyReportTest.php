<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\KnowledgeCheckOption;
use App\Models\KnowledgeCheckQuestion;
use App\Models\Mission;
use App\Models\Section;
use App\Models\Skill;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\ClassroomAccessService;
use App\Services\CompetencyReportService;
use App\Services\CompetencyService;
use App\Services\MissionService;
use App\Services\XpService;
use App\Support\ReportFilters;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * US-1007 competency report. Every expectation is hand-computed from the
 * fixture rows, and every row is cross-checked against the authoritative
 * CompetencyService output it must reproduce verbatim.
 */
class CompetencyReportTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    private static int $orderSequence = 2000;

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

    /**
     * @return array{check: KnowledgeCheck, question: KnowledgeCheckQuestion, correct: KnowledgeCheckOption}
     */
    private function checkWithQuestion(Mission $mission, array $skillKeys = []): array
    {
        $check = KnowledgeCheck::factory()->create([
            'mission_id' => $mission->id,
            'status' => 'published',
        ]);
        $question = KnowledgeCheckQuestion::factory()->create([
            'knowledge_check_id' => $check->id,
        ]);
        KnowledgeCheckOption::factory()->create([
            'knowledge_check_question_id' => $question->id,
            'order_num' => 1,
            'is_correct' => false,
        ]);
        $correct = KnowledgeCheckOption::factory()->create([
            'knowledge_check_question_id' => $question->id,
            'order_num' => 2,
            'is_correct' => true,
        ]);

        if ($skillKeys !== []) {
            $ids = [];

            foreach ($skillKeys as $key) {
                $ids[] = Skill::query()->firstOrCreate(['key' => $key], ['label' => $key])->id;
            }

            $question->skills()->sync($ids);
        }

        return compact('check', 'question', 'correct');
    }

    private function answer(User $user, Mission $mission, KnowledgeCheck $check, KnowledgeCheckQuestion $question, KnowledgeCheckOption $option): void
    {
        $this->actingAs($user)->post(route('knowledge-check.start', [$mission, $check]))->assertRedirect();

        $attempt = KnowledgeCheckAttempt::query()
            ->where('user_id', $user->id)
            ->where('knowledge_check_id', $check->id)
            ->sole();

        $this->actingAs($user)->post(
            route('knowledge-check.submit', [$mission, $check, $attempt]),
            ['answers' => [$question->id => $option->id]],
        )->assertRedirect();
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

    private function passAssessment(User $user, Course $course, string $code = 'SIGNAL'): void
    {
        $assessment = Assessment::query()->where('course_id', $course->id)->firstOrFail();
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
        return app(CompetencyReportService::class)->forStudent(
            $student,
            $courseIds === null ? null : collect($courseIds),
            $this->filters($input),
        );
    }

    public function test_empty_scope_reports_empty_lists_and_zero_period(): void
    {
        $student = User::factory()->create();

        $report = $this->report($student);

        $this->assertSame($student->id, $report['student_id']);
        $this->assertSame([], $report['courses']);
        $this->assertSame([], $report['skills']);
        $this->assertSame([], $report['weak_skills']);
        $this->assertSame(0, $report['period']['completions']);
        $this->assertSame(0, $report['period']['wrong_submissions']);
        $this->assertSame(0, $report['period']['assessment_attempts']);
        $this->assertSame(0, $report['period']['assessment_passes']);
        $this->assertNull($report['period']['from']);
        $this->assertNull($report['period']['to_exclusive']);
    }

    public function test_full_journey_report_matches_authoritative_services(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One', ['html.headings']);
        ['check' => $check, 'question' => $question, 'correct' => $correct] = $this->checkWithQuestion($mission, ['html.headings']);

        $this->complete($student, $mission);
        $this->answer($student, $mission, $check, $question, $correct);
        $this->wrongSubmission($student, $mission);
        $this->wrongSubmission($student, $mission);

        Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'passing_score' => 0,
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'SIGNAL']]),
        ]);
        $this->passAssessment($student, $course);

        $report = $this->report($student);

        // Course rows reproduce CompetencyService::overview verbatim.
        $expectedCourses = app(CompetencyService::class)->overview($student);
        $this->assertCount($expectedCourses->count(), $report['courses']);

        foreach ($report['courses'] as $index => $row) {
            $source = $expectedCourses[$index];
            $this->assertSame($source['course']->id, $row['course_id']);
            $this->assertSame($source['name'], $row['name']);
            $this->assertSame($source['state'], $row['state']);
            $this->assertSame($source['percent'], $row['percent']);
            $this->assertSame($source['completedMissions'], $row['completed_missions']);
            $this->assertSame($source['totalMissions'], $row['total_missions']);
            $this->assertSame($source['wrongSubmissions'], $row['wrong_submissions']);
            $this->assertSame($source['attempts'], $row['attempts']);
            $this->assertSame($source['challengePassed'], $row['challenge_passed']);
        }

        $this->assertSame('demonstrated', $report['courses'][0]['state']);

        // Skill rows reproduce CompetencyService::skills verbatim.
        $expectedSkills = app(CompetencyService::class)->skills($student);
        $this->assertCount($expectedSkills->count(), $report['skills']);

        foreach ($report['skills'] as $index => $row) {
            $source = $expectedSkills[$index];
            $this->assertSame($source['key'], $row['key']);
            $this->assertSame($source['label'], $row['label']);
            $this->assertSame($source['percentage'], $row['percentage']);
            $this->assertSame($source['state'], $row['state']);
            $this->assertSame($source['weak'], $row['weak']);
            $this->assertSame($source['kcCorrect'], $row['kc_correct']);
            $this->assertSame($source['kcTotal'], $row['kc_total']);
            $this->assertSame($source['challengesCompleted'], $row['challenges_completed']);
            $this->assertSame($source['challengesApplicable'], $row['challenges_applicable']);
        }

        // One mission plus one correct answer: 100% proficient, not weak.
        $this->assertSame([], $report['weak_skills']);

        // Period counts describe the same fixture: 1 completion, 2 wrong
        // submissions, 1 assessment attempt with 1 pass.
        $this->assertSame(1, $report['period']['completions']);
        $this->assertSame(2, $report['period']['wrong_submissions']);
        $this->assertSame(1, $report['period']['assessment_attempts']);
        $this->assertSame(1, $report['period']['assessment_passes']);
    }

    public function test_weak_skill_appears_in_weak_list(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');

        foreach (['W1', 'W2', 'W3'] as $title) {
            $this->mission($course, $title, ['html.headings']);
        }

        $missions = Mission::query()->where('course_id', $course->id)->orderBy('id')->get();
        $this->complete($student, $missions[0]);

        $report = $this->report($student);

        // One of three challenges complete: 33% weak via the single path.
        $this->assertSame(['html.headings'], $report['weak_skills']);
        $weak = app(CompetencyService::class)->weakSkills($student);
        $this->assertSame($weak->pluck('key')->all(), $report['weak_skills']);
    }

    public function test_teacher_scope_restricts_report_to_shared_courses(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One', ['html.headings']);
        $missionB = $this->mission($courseB, 'Beta One', ['css.color']);

        $this->classroomFor($teacher, [$student], [$courseA]);
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);

        $scope = app(ClassroomAccessService::class)->courseIdsForStudent($teacher, $student);
        $report = $this->report($student, $scope->all());

        $this->assertCount(1, $report['courses']);
        $this->assertSame($courseA->id, $report['courses'][0]['course_id']);
        $this->assertSame(['html.headings'], collect($report['skills'])->pluck('key')->all());
        $this->assertSame(1, $report['period']['completions']);
    }

    public function test_empty_scope_yields_empty_report(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('ca', 'Course A');
        $mission = $this->mission($course, 'Alpha One', ['html.headings']);

        $this->classroomFor($teacher, [], [$course]);
        $this->complete($student, $mission);

        // Teacher shares no course with this student: empty, never fleet.
        $scope = app(ClassroomAccessService::class)->courseIdsForStudent($teacher, $student);
        $this->assertTrue($scope->isEmpty());

        $report = $this->report($student, $scope->all());

        $this->assertSame([], $report['courses']);
        $this->assertSame([], $report['skills']);
        $this->assertSame([], $report['weak_skills']);
        $this->assertSame(0, $report['period']['completions']);
    }

    public function test_date_range_scopes_period_without_touching_state(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $april = $this->mission($course, 'April Mission', ['html.headings']);
        $may = $this->mission($course, 'May Mission', ['html.headings']);
        $this->complete($student, $april);
        $this->complete($student, $may);

        DB::table('the404_progress')->where('mission_id', $april->id)->update(['completed_at' => '2026-04-10 09:00:00']);
        DB::table('the404_progress')->where('mission_id', $may->id)->update(['completed_at' => '2026-05-10 09:00:00']);

        $report = $this->report($student, null, ['from' => '2026-05-01', 'to' => '2026-05-31']);

        // State stays cumulative (2 of 2 complete); only the period narrows.
        $this->assertSame(2, $report['courses'][0]['completed_missions']);
        $this->assertSame(1, $report['period']['completions']);
        $this->assertSame('2026-05-01 00:00:00', $report['period']['from']);
        $this->assertSame('2026-06-01 00:00:00', $report['period']['to_exclusive']);
    }

    public function test_live_metadata_edits_leave_stored_period_evidence_untouched(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One', ['html.headings']);
        ['check' => $check, 'question' => $question, 'correct' => $correct] = $this->checkWithQuestion($mission, ['html.headings']);

        $this->complete($student, $mission);
        $this->answer($student, $mission, $check, $question, $correct);
        $this->wrongSubmission($student, $mission);

        Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'passing_score' => 0,
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'SIGNAL']]),
        ]);
        $this->passAssessment($student, $course);

        $before = $this->report($student);

        // Mutate live descriptive metadata after the evidence exists.
        Skill::query()->where('key', 'html.headings')->update(['label' => 'Renamed Headings']);
        $course->update(['name' => 'Renamed Course']);
        $mission->update(['title' => 'Renamed Mission', 'difficulty' => 'HARD']);

        $after = $this->report($student);

        // Stored period evidence is historical fact: nothing moves.
        $this->assertSame($before['period'], $after['period']);
        $this->assertSame(1, $after['period']['completions']);
        $this->assertSame(1, $after['period']['wrong_submissions']);
        $this->assertSame(1, $after['period']['assessment_attempts']);
        $this->assertSame(1, $after['period']['assessment_passes']);

        // The live label follows current metadata; the report still equals
        // the authoritative service output field-for-field after the edit.
        $this->assertSame('Renamed Headings', $after['skills'][0]['label']);

        $expectedCourses = app(CompetencyService::class)->overview($student);
        $this->assertSame($expectedCourses[0]['percent'], $after['courses'][0]['percent']);
        $this->assertSame($expectedCourses[0]['state'], $after['courses'][0]['state']);

        $expectedSkills = app(CompetencyService::class)->skills($student);
        $this->assertSame($expectedSkills[0]['percentage'], $after['skills'][0]['percentage']);
        $this->assertSame($expectedSkills[0]['state'], $after['skills'][0]['state']);
    }

    public function test_matching_student_filter_keeps_report(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One', ['html.headings']);
        $this->complete($student, $mission);

        $plain = $this->report($student);
        $filtered = $this->report($student, null, ['student_id' => $student->id]);

        $this->assertSame($plain['courses'], $filtered['courses']);
        $this->assertSame($plain['skills'], $filtered['skills']);
        $this->assertSame($plain['weak_skills'], $filtered['weak_skills']);
        $this->assertSame($plain['period'], $filtered['period']);
    }

    public function test_mismatched_student_filter_fails_closed(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One', ['html.headings']);
        $this->complete($student, $mission);

        // The other id is a valid student, so the filter validates but the
        // report must still refuse to leak the student's rows.
        $report = $this->report($student, null, ['student_id' => $other->id]);

        $this->assertSame([], $report['courses']);
        $this->assertSame([], $report['skills']);
        $this->assertSame([], $report['weak_skills']);
        $this->assertSame(0, $report['period']['completions']);
    }

    public function test_course_filter_narrows_to_authorized_course(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One', ['html.headings']);
        $missionB = $this->mission($courseB, 'Beta One', ['css.color']);
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);

        $report = $this->report($student, null, ['course_id' => $courseA->id]);

        $this->assertCount(1, $report['courses']);
        $this->assertSame($courseA->id, $report['courses'][0]['course_id']);
        $this->assertSame(['html.headings'], collect($report['skills'])->pluck('key')->all());
        $this->assertSame(1, $report['period']['completions']);
    }

    public function test_course_filter_outside_scope_yields_empty(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One', ['html.headings']);
        $missionB = $this->mission($courseB, 'Beta One', ['css.color']);
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);

        // Authorized scope holds A only; requesting B fails closed.
        $report = $this->report($student, [$courseA->id], ['course_id' => $courseB->id]);

        $this->assertSame([], $report['courses']);
        $this->assertSame([], $report['skills']);
        $this->assertSame([], $report['weak_skills']);
        $this->assertSame(0, $report['period']['completions']);
    }

    public function test_date_range_combines_with_course_filter(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One', ['html.headings']);
        $missionB = $this->mission($courseB, 'Beta One', ['css.color']);
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);

        DB::table('the404_progress')->where('mission_id', $missionA->id)->update(['completed_at' => '2026-05-10 09:00:00']);
        DB::table('the404_progress')->where('mission_id', $missionB->id)->update(['completed_at' => '2026-04-10 09:00:00']);

        $report = $this->report($student, null, [
            'course_id' => $courseB->id,
            'from' => '2026-05-01',
            'to' => '2026-05-31',
        ]);

        // Course narrows to B with cumulative state; the May range finds no
        // B activity, so the period reads zero.
        $this->assertCount(1, $report['courses']);
        $this->assertSame($courseB->id, $report['courses'][0]['course_id']);
        $this->assertSame(1, $report['courses'][0]['completed_missions']);
        $this->assertSame(0, $report['period']['completions']);
    }

    public function test_invalid_student_course_status_filters_are_rejected(): void
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

    public function test_query_count_stays_bounded_as_history_grows(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');

        foreach (range(1, 3) as $i) {
            $mission = $this->mission($course, "M{$i}", ['html.headings']);
            $this->complete($student, $mission);
        }

        $service = app(CompetencyReportService::class);
        $queries = $this->countQueries(fn () => $service->forStudent($student));

        $this->assertLessThanOrEqual(30, $queries);

        $courseB = $this->course('beta', 'Beta');

        foreach (range(1, 6) as $i) {
            $mission = $this->mission($courseB, "B{$i}", ['css.color']);
            $this->complete($student, $mission);
        }

        $grown = $this->countQueries(fn () => $service->forStudent($student));

        $this->assertLessThanOrEqual($queries + 4, $grown);
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
