<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\CompetencyService;
use App\Services\RecommendationService;
use App\Services\XpService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * US-912 End-to-End Personalized Learning. One coherent student-plus-teacher
 * journey across the systems Phase 9 integrated: real submissions drive
 * recommendations, competency, progression, and teacher monitoring together.
 *
 * No Knowledge Check gates are configured in this fixture, so none constrain
 * the path. Weak-skill identification (US-905/906) remains blocked on the
 * unresolved skill-mapping schema and is explicitly out of scope here.
 */
class PersonalizedLearningJourneyTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    /**
     * @return array{
     *     teacher: User,
     *     student: User,
     *     courseA: Course,
     *     courseB: Course,
     *     missionA1: Mission,
     *     missionA2: Mission,
     *     assessmentA: Assessment,
     * }
     */
    private function journey(): array
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);

        $courseA = Course::factory()->create(['name' => 'Course Alpha', 'status' => 'active', 'order_num' => 1]);
        $sectionA = Section::factory()->create(['course_id' => $courseA->id, 'order_num' => 1]);
        $missionA1 = Mission::factory()->create([
            'course_id' => $courseA->id,
            'section_id' => $sectionA->id,
            'order_num' => 1,
            'title' => 'Alpha One',
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'points' => 30,
        ]);
        $missionA2 = Mission::factory()->create([
            'course_id' => $courseA->id,
            'section_id' => $sectionA->id,
            'order_num' => 2,
            'title' => 'Alpha Two',
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<nav>']]),
            'points' => 40,
        ]);
        $assessmentA = Assessment::factory()->create([
            'course_id' => $courseA->id,
            'status' => 'active',
            'passing_score' => 70,
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'SYSTEM_ONLINE']]),
        ]);

        $courseB = Course::factory()->create(['name' => 'Course Beta', 'status' => 'active', 'order_num' => 2]);
        $sectionB = Section::factory()->create(['course_id' => $courseB->id, 'order_num' => 1]);
        Mission::factory()->create([
            'course_id' => $courseB->id,
            'section_id' => $sectionB->id,
            'order_num' => 1,
            'title' => 'Beta One',
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<p>']]),
            'points' => 50,
        ]);
        Assessment::factory()->create(['course_id' => $courseB->id, 'status' => 'active']);

        $this->classroomFor($teacher, [$student], [$courseA, $courseB]);

        return compact('teacher', 'student', 'courseA', 'courseB', 'missionA1', 'missionA2', 'assessmentA');
    }

    private function competencyState(User $student, Course $course): string
    {
        $row = app(CompetencyService::class)->overview($student)
            ->firstWhere(fn (array $r): bool => $r['course']->id === $course->id);

        $this->assertNotNull($row);

        return $row['state'];
    }

    private function recommendationsPanel(string $content): string
    {
        $start = strpos($content, 'RECOMMENDATIONS');

        $this->assertNotFalse($start);

        $rest = substr($content, (int) $start);
        $end = strpos($rest, '</section>');

        return $end === false ? $rest : substr($rest, 0, $end);
    }

    public function test_struggle_produces_matching_review_everywhere(): void
    {
        ['teacher' => $teacher, 'student' => $student, 'missionA1' => $mission] = $this->journey();

        // Two genuine wrong submissions, then the pass, all through HTTP.
        $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => 'no heading']);
        $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => 'still wrong']);
        $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => '<h1>Done</h1>'])
            ->assertRedirect()
            ->assertSessionHas('mission_success');

        $this->assertSame(2, XpTransaction::query()
            ->where('user_id', $student->id)
            ->where('mission_id', $mission->id)
            ->where('type', XpService::TYPE_WRONG_SUBMISSION)
            ->count());

        $expected = app(RecommendationService::class)->recommendations($student)
            ->firstWhere(fn (array $card): bool => $card['slot'] === 3);

        $this->assertNotNull($expected);

        foreach ([route('recommendations'), route('learning-path')] as $page) {
            $this->actingAs($student)->get($page)
                ->assertOk()
                ->assertSee('Review')
                ->assertSee('Alpha One')
                ->assertSee($expected['subtitle']);
        }

        $panel = $this->recommendationsPanel(
            $this->actingAs($teacher)
                ->get(route('student-progress', ['student' => $student->id]))
                ->assertOk()
                ->assertSee('Review')
                ->assertSee('Alpha One')
                ->getContent()
        );

        $this->assertStringNotContainsString('<a ', $panel);
        $this->assertStringNotContainsString('href=', $panel);
    }

    public function test_full_course_pass_moves_competency_recommendations_and_handoff(): void
    {
        ['teacher' => $teacher, 'student' => $student] = $this->journey();
        $courseA = Course::where('name', 'Course Alpha')->firstOrFail();
        $courseB = Course::where('name', 'Course Beta')->firstOrFail();
        $assessmentA = Assessment::where('course_id', $courseA->id)->firstOrFail();

        // Sealed before eligibility: redirect with the sealed flash, never access.
        $this->actingAs($student)->get(route('assessment.show', $assessmentA))
            ->assertRedirect(route('assessments'))
            ->assertSessionHas('assessment_locked');

        foreach (Mission::where('course_id', $courseA->id)->orderBy('order_num')->get() as $mission) {
            $code = $mission->order_num === 1 ? '<h1>Done</h1>' : '<nav>Done</nav>';
            $this->actingAs($student)->post(route('mission.submit', $mission), ['code' => $code])
                ->assertRedirect()
                ->assertSessionHas('mission_success');
        }

        $this->assertSame('practicing', $this->competencyState($student, $courseA));

        $this->actingAs($student)->post(route('assessment.start', $assessmentA))
            ->assertRedirect(route('assessment.show', $assessmentA));
        $this->actingAs($student)->post(route('assessment.submit', $assessmentA), ['code' => 'SYSTEM_ONLINE'])
            ->assertRedirect(route('assessment.show', $assessmentA))
            ->assertSessionHas('assessment_success');

        $this->assertSame('demonstrated', $this->competencyState($student, $courseA));

        $this->actingAs($student)->get(route('competency'))
            ->assertOk()
            ->assertSee('DEMONSTRATED');

        // Recommendations shift to the cleared state on every student surface.
        foreach ([route('recommendations'), route('learning-path')] as $page) {
            $this->actingAs($student)->get($page)
                ->assertOk()
                ->assertSee('Next Course')
                ->assertSee('Course Beta');
        }

        // Dashboard hands over to Course Beta.
        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Course Beta');

        // Teacher sees the same cleared state, read-only.
        $teacherPage = $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertOk()
            ->assertSee('DEMONSTRATED')
            ->assertSee('Next Course')
            ->assertSee('Course Beta')
            ->getContent();

        $panel = $this->recommendationsPanel($teacherPage);
        $this->assertStringNotContainsString('<a ', $panel);
    }

    public function test_journey_authorization_and_protected_data_hold(): void
    {
        ['teacher' => $teacher, 'student' => $student] = $this->journey();
        $outsider = User::factory()->teacher()->create();
        $other = User::factory()->create(['role' => 'student']);

        $this->actingAs($outsider)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('student-progress', ['student' => $other->id]))
            ->assertForbidden();

        $this->actingAs($student)
            ->get(route('student-progress', ['student' => $student->id]))
            ->assertForbidden();

        $pages = [
            $this->actingAs($student)->get(route('recommendations'))->assertOk()->getContent(),
            $this->actingAs($student)->get(route('learning-path'))->assertOk()->getContent(),
            $this->actingAs($teacher)
                ->get(route('student-progress', ['student' => $student->id]))
                ->assertOk()->getContent(),
        ];

        foreach ($pages as $content) {
            foreach (['validate_rule', 'solution_code', 'grading_rule', 'is_correct'] as $internal) {
                $this->assertStringNotContainsString($internal, $content);
            }
        }

        $this->assertStringNotContainsString($other->username, $pages[2]);
    }
}
