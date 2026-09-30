<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\KnowledgeCheckOption;
use App\Models\KnowledgeCheckQuestion;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\Skill;
use App\Models\User;
use App\Services\CompetencyService;
use App\Services\MissionService;
use App\Services\RecommendationService;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Database\Seeders\SkillSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * US-905/US-906 skill-level competency and weak-skill identification.
 *
 * Schema, snapshots, formula, weak derivation, UI surfaces, isolation,
 * and recommendation integration — all against recorded evidence only.
 */
class SkillCompetencyTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    private function skill(string $key, string $label): Skill
    {
        return Skill::query()->firstOrCreate(['key' => $key], ['label' => $label]);
    }

    private function ensureSkills(array $skillKeys): array
    {
        $ids = [];
        foreach ($skillKeys as $key) {
            $ids[] = $this->skill($key, $key)->id;
        }

        return $ids;
    }

    private static int $orderSequence = 1000;

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
            $mission->skills()->sync($this->ensureSkills($skillKeys));
        }

        return $mission;
    }

    /**
     * @return array{check: KnowledgeCheck, question: KnowledgeCheckQuestion, correct: KnowledgeCheckOption, wrong: KnowledgeCheckOption}
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
        $wrong = KnowledgeCheckOption::factory()->create([
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
            $question->skills()->sync($this->ensureSkills($skillKeys));
        }

        return compact('check', 'question', 'correct', 'wrong');
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

    private function skillRow(User $user, string $key, ?array $courseIds = null): ?array
    {
        return app(CompetencyService::class)
            ->skills($user, $courseIds === null ? null : collect($courseIds))
            ->firstWhere(fn (array $row): bool => $row['key'] === $key);
    }

    public function test_skill_table_rejects_duplicate_keys(): void
    {
        Skill::query()->create(['key' => 'html.headings', 'label' => 'HTML Headings']);

        $this->expectException(QueryException::class);

        Skill::query()->create(['key' => 'html.headings', 'label' => 'HTML Headings Again']);
    }

    public function test_pivot_rejects_duplicate_mission_skill_pairs(): void
    {
        $skill = $this->skill('html.headings', 'HTML Headings');
        $mission = $this->mission($this->course('c1'), 'M1');

        $mission->skills()->sync([$skill->id]);
        $this->assertSame(1, $mission->skills()->count());

        $mission->skills()->sync([$skill->id]);
        $this->assertSame(1, $mission->skills()->count());
    }

    public function test_deleting_mapped_skill_or_mission_is_restricted(): void
    {
        $skill = $this->skill('html.headings', 'HTML Headings');
        $mission = $this->mission($this->course('c1'), 'M1', ['html.headings']);

        try {
            $skill->delete();
            $this->fail('Skill delete must be restricted while mappings exist.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        try {
            $mission->delete();
            $this->fail('Mission delete must be restricted while mappings exist.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }
    }

    public function test_seed_taxonomy_maps_every_listed_mission_and_question(): void
    {
        $course = Course::factory()->create(['slug' => 'html-fundamentals', 'name' => 'HTML', 'status' => 'active']);
        $mission = $this->mission($course, 'Header_Reconstruction');
        ['check' => $check, 'question' => $question] = $this->checkWithQuestion($mission);

        $this->seed(SkillSeeder::class);

        $this->assertSame(['html.headings'], $mission->fresh()->skills()->pluck('key')->all());
        $this->assertSame(['html.headings'], $question->fresh()->skills()->pluck('key')->all());
        $this->assertSame(27, Skill::query()->count());
    }

    public function test_seed_is_idempotent_and_bumps_versions_only_on_change(): void
    {
        $course = Course::factory()->create(['slug' => 'html-fundamentals', 'name' => 'HTML', 'status' => 'active']);
        $mission = $this->mission($course, 'Header_Reconstruction');
        ['check' => $check] = $this->checkWithQuestion($mission);

        $this->seed(SkillSeeder::class);
        $this->assertSame(2, $mission->fresh()->version);
        $this->assertSame(2, $check->fresh()->version);

        $this->seed(SkillSeeder::class);
        $this->assertSame(2, $mission->fresh()->version);
        $this->assertSame(2, $check->fresh()->version);
        $this->assertSame(1, DB::table('the404_mission_skill')->where('mission_id', $mission->id)->count());
    }

    public function test_multi_skill_mission_snapshot_holds_both_keys(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course('c1'), 'M1', ['html.headings', 'html.structure']);

        $this->complete($user, $mission);

        $progress = Progress::query()
            ->where('user_id', $user->id)
            ->where('mission_id', $mission->id)
            ->firstOrFail();

        $this->assertEqualsCanonicalizing(['html.headings', 'html.structure'], $progress->skill_keys);
    }

    public function test_kc_response_snapshot_holds_question_skill_keys(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course('c1'), 'M1');
        ['check' => $check, 'question' => $question, 'correct' => $correct] = $this->checkWithQuestion($mission, ['html.headings']);

        $this->answer($user, $mission, $check, $question, $correct);

        $keys = DB::table('the404_knowledge_check_responses')
            ->where('knowledge_check_question_id', $question->id)
            ->value('skill_keys');

        $this->assertSame(['html.headings'], json_decode($keys, true));
    }

    public function test_remapping_does_not_reinterpret_historical_completion(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course('c1'), 'M1', ['html.headings']);

        $this->complete($user, $mission);

        $this->skill('html.structure', 'HTML Structure');
        $mission->skills()->sync(Skill::query()->where('key', 'html.structure')->pluck('id')->all());

        $row = app(CompetencyService::class)->skills($user)->firstWhere(fn (array $r): bool => $r['key'] === 'html.headings');
        $this->assertNotNull($row);
        $this->assertSame(100.0, $row['percentage']);

        // The remapped skill has a live requirement but the student has no
        // evidence under it: NOT ASSESSED, never auto-weak. History itself
        // is untouched — headings still scores 100 above.
        $remapped = app(CompetencyService::class)->skills($user)->firstWhere(fn (array $r): bool => $r['key'] === 'html.structure');
        $this->assertNotNull($remapped);
        $this->assertNull($remapped['percentage']);
        $this->assertSame('not_assessed', $remapped['state']);
        $this->assertFalse($remapped['weak']);
        $this->assertSame(1, $remapped['challengesApplicable']);
        $this->assertSame(0, $remapped['challengesCompleted']);
    }

    public function test_remapping_question_does_not_reinterpret_recorded_response(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course('c1'), 'M1');
        ['check' => $check, 'question' => $question, 'correct' => $correct] = $this->checkWithQuestion($mission, ['html.headings']);

        $this->answer($user, $mission, $check, $question, $correct);

        $this->skill('html.structure', 'HTML Structure');
        $question->skills()->sync(Skill::query()->where('key', 'html.structure')->pluck('id')->all());

        $row = app(CompetencyService::class)->skills($user)->firstWhere(fn (array $r): bool => $r['key'] === 'html.headings');
        $this->assertNotNull($row);
        $this->assertSame(100.0, $row['percentage']);
        $this->assertSame(1, $row['kcCorrect']);
        $this->assertSame(1, $row['kcTotal']);

        $remapped = app(CompetencyService::class)->skills($user)->firstWhere(fn (array $r): bool => $r['key'] === 'html.structure');
        $this->assertNotNull($remapped);
        $this->assertNull($remapped['percentage']);
        $this->assertSame('not_assessed', $remapped['state']);
        $this->assertFalse($remapped['weak']);
    }

    public function test_kc_only_evidence_uses_kc_percentage_at_full_weight(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course('c1'), 'M1');
        ['check' => $check, 'question' => $question, 'correct' => $correct] = $this->checkWithQuestion($mission, ['html.headings']);

        $this->answer($user, $mission, $check, $question, $correct);

        $row = $this->skillRow($user, 'html.headings');
        $this->assertNotNull($row);
        $this->assertSame(100.0, $row['percentage']);
        $this->assertSame(1, $row['kcCorrect']);
        $this->assertSame(1, $row['kcTotal']);
        $this->assertSame(0, $row['challengesApplicable']);
        $this->assertFalse($row['weak']);
    }

    public function test_challenge_only_evidence_uses_completion_at_full_weight(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course('c1'), 'M1', ['html.headings']);

        $this->complete($user, $mission);

        $row = $this->skillRow($user, 'html.headings');
        $this->assertNotNull($row);
        $this->assertSame(100.0, $row['percentage']);
        $this->assertSame(1, $row['challengesCompleted']);
        $this->assertSame(1, $row['challengesApplicable']);
        $this->assertFalse($row['weak']);
    }

    public function test_combined_evidence_averages_fifty_fifty(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course('c1'), 'M1', ['html.headings']);
        ['check' => $check, 'question' => $question, 'wrong' => $wrong] = $this->checkWithQuestion($mission, ['html.headings']);

        $this->complete($user, $mission);
        $this->answer($user, $mission, $check, $question, $wrong);

        $row = $this->skillRow($user, 'html.headings');
        $this->assertNotNull($row);
        $this->assertSame(50.0, $row['percentage']);
        $this->assertTrue($row['weak']);
    }

    public function test_mapped_skill_without_any_evidence_is_not_assessed(): void
    {
        $user = User::factory()->create();
        $course = $this->course('c1');
        $this->mission($course, 'M1', ['html.headings']);
        $this->mission($course, 'M2', ['html.headings']);

        $row = $this->skillRow($user, 'html.headings');
        $this->assertNotNull($row);
        $this->assertNull($row['percentage']);
        $this->assertSame('not_assessed', $row['state']);
        $this->assertFalse($row['weak']);
        $this->assertSame(2, $row['challengesApplicable']);
    }

    public function test_sixty_nine_point_twenty_three_is_weak(): void
    {
        $user = User::factory()->create();
        $course = $this->course('c1');

        for ($i = 1; $i <= 13; $i++) {
            $mission = $this->mission($course, "M{$i}", ['html.headings']);

            if ($i <= 9) {
                $this->complete($user, $mission);
            }
        }

        $row = $this->skillRow($user, 'html.headings');
        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(69.23, $row['percentage'], 0.01);
        $this->assertTrue($row['weak']);
        $this->assertSame('weak', $row['state']);
    }

    public function test_seventy_is_not_weak(): void
    {
        $user = User::factory()->create();
        $course = $this->course('c1');

        for ($i = 1; $i <= 10; $i++) {
            $mission = $this->mission($course, "M{$i}", ['html.headings']);

            if ($i <= 7) {
                $this->complete($user, $mission);
            }
        }

        $row = $this->skillRow($user, 'html.headings');
        $this->assertNotNull($row);
        $this->assertSame(70.0, $row['percentage']);
        $this->assertFalse($row['weak']);
        $this->assertSame('proficient', $row['state']);
    }

    public function test_weak_skills_derive_from_competency_output(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course('c1'), 'M1', ['html.headings']);

        $this->complete($user, $mission);

        $service = app(CompetencyService::class);
        $weak = $service->weakSkills($user);
        $expected = $service->skills($user)->filter(fn (array $row): bool => $row['weak'])->values();

        $this->assertSame(
            $expected->pluck('key')->all(),
            $weak->pluck('key')->all()
        );
        $this->assertSame([], $weak->pluck('key')->all());
    }

    public function test_student_skill_display_shows_states(): void
    {
        $user = User::factory()->create();
        $this->skill('html.headings', 'HTML Headings');
        $this->skill('html.links', 'HTML Links');
        $course = $this->course('c1');
        $mission = $this->mission($course, 'M1', ['html.headings']);
        $this->mission($course, 'M2', ['html.links']);

        $this->complete($user, $mission);

        $this->actingAs($user)->get(route('competency'))
            ->assertOk()
            ->assertSee('HTML Headings')
            ->assertSee('ON TRACK')
            ->assertSee('HTML Links')
            ->assertSee('NOT ASSESSED');
    }

    public function test_authorized_teacher_sees_student_skills(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('c1');
        $this->skill('html.headings', 'HTML Headings');
        $mission = $this->mission($course, 'M1', ['html.headings']);

        $this->classroomFor($teacher, [$student], [$course]);
        $this->complete($student, $mission);

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->assertSee('Skill Competency')
            ->assertSee('HTML Headings')
            ->assertSee('ON TRACK');
    }

    public function test_teacher_scoped_out_skill_is_hidden(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('cA');
        $courseB = $this->course('cB');
        $missionB = $this->mission($courseB, 'MB', ['css.color']);
        $this->skill('css.color', 'CSS Color');

        $this->classroomFor($teacher, [$student], [$courseA]);
        $this->complete($student, $missionB);

        $content = $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('CSS Color', $content);
        $this->assertStringNotContainsString('css.color', $content);
    }

    public function test_unauthorized_teacher_is_refused_skill_page(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('c1');
        $this->mission($course, 'M1', ['html.headings']);

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertForbidden();
    }

    public function test_review_reason_includes_weak_skill_context(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $course = $this->course('c1');
        $this->skill('html.headings', 'HTML Headings');
        $mission = $this->mission($course, 'Struggle Bus', ['html.headings']);
        $this->mission($course, 'Untouched One', ['html.headings']);
        $this->mission($course, 'Untouched Two', ['html.headings']);

        $this->complete($user, $mission);
        $this->wrongSubmission($user, $mission);
        $this->wrongSubmission($user, $mission);

        $cards = app(RecommendationService::class)->recommendations($user);
        $review = $cards->firstWhere(fn (array $card): bool => $card['slot'] === 3);

        $this->assertNotNull($review);
        $this->assertStringContainsString('Below target: HTML Headings', $review['subtitle']);
    }

    public function test_review_reason_unchanged_without_weak_skills(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $course = $this->course('c1');
        $mission = $this->mission($course, 'Struggle Bus');

        $this->complete($user, $mission);
        $this->wrongSubmission($user, $mission);
        $this->wrongSubmission($user, $mission);

        $cards = app(RecommendationService::class)->recommendations($user);
        $review = $cards->firstWhere(fn (array $card): bool => $card['slot'] === 3);

        $this->assertNotNull($review);
        $this->assertStringNotContainsString('Below target', $review['subtitle']);
    }

    public function test_existing_course_competency_untouched_by_skills(): void
    {
        $user = User::factory()->create();
        $course = $this->course('c1');
        $mission = $this->mission($course, 'M1', ['html.headings']);

        $this->complete($user, $mission);

        $rows = app(CompetencyService::class)->overview($user);
        $this->assertSame('practicing', $rows->firstWhere(fn (array $row) => $row['course']->id === $course->id)['state']);
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
}
