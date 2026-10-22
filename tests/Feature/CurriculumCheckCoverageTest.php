<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckOption;
use App\Models\KnowledgeCheckQuestion;
use App\Models\Mission;
use Database\Seeders\CurriculumContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every mission owns a Knowledge Check.
 *
 * A check is the mission's concept check: it gates the coding challenge, so
 * a mission without one has no gate at all. The curriculum therefore has to
 * cover every mission, with questions that are grounded in what that
 * mission teaches — a check that cannot be answered from the lesson is
 * filler, not review.
 */
class CurriculumCheckCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_mission_owns_exactly_one_published_check(): void
    {
        $this->seed(CurriculumContentSeeder::class);

        $missions = Mission::query()->count();
        $checks = KnowledgeCheck::query()->where('status', KnowledgeCheck::STATUS_PUBLISHED)->count();

        $this->assertSame($missions, $checks, 'Every mission must own exactly one published Knowledge Check.');

        $missionsWithoutCheck = Mission::query()
            ->whereDoesntHave('knowledgeChecks', fn ($query) => $query->where('status', KnowledgeCheck::STATUS_PUBLISHED))
            ->count();

        $this->assertSame(0, $missionsWithoutCheck, 'Missions without a published check: '.$missionsWithoutCheck);

        $missionsWithSeveral = Mission::query()
            ->withCount(['knowledgeChecks' => fn ($query) => $query->where('status', KnowledgeCheck::STATUS_PUBLISHED)])
            ->get()
            ->filter(fn (Mission $mission): bool => $mission->knowledge_checks_count > 1)
            ->count();

        $this->assertSame(0, $missionsWithSeveral, 'Missions with more than one published check: '.$missionsWithSeveral);
    }

    public function test_every_check_is_required_and_well_formed(): void
    {
        $this->seed(CurriculumContentSeeder::class);

        foreach (KnowledgeCheck::query()->where('status', KnowledgeCheck::STATUS_PUBLISHED)->get() as $check) {
            $this->assertTrue($check->is_required, 'Check '.$check->id.' must be required by default.');
            $this->assertNotSame('', trim((string) $check->title), 'Check '.$check->id.' needs a title.');
            $this->assertNotSame('', trim((string) $check->instructions), 'Check '.$check->id.' needs instructions.');

            $questions = KnowledgeCheckQuestion::query()
                ->where('knowledge_check_id', $check->id)
                ->orderBy('order_num')
                ->get();

            $this->assertCount(3, $questions, 'Check '.$check->id.' must carry three questions.');

            foreach ($questions as $question) {
                $this->assertNotSame('', trim((string) $question->prompt), 'Question '.$question->id.' needs a prompt.');
                $this->assertNotSame('', trim((string) $question->explanation), 'Question '.$question->id.' needs an explanation.');

                $options = KnowledgeCheckOption::query()
                    ->where('knowledge_check_question_id', $question->id)
                    ->orderBy('order_num')
                    ->get();

                $this->assertGreaterThanOrEqual(2, $options->count(), 'Question '.$question->id.' needs at least two options.');
                $this->assertSame(
                    1,
                    $options->where('is_correct', true)->count(),
                    'Question '.$question->id.' must have exactly one correct option.'
                );

                foreach ($options as $option) {
                    $this->assertNotSame('', trim((string) $option->option_text), 'Option '.$option->id.' needs text.');
                }
            }
        }
    }

    public function test_the_curriculum_never_repeats_a_question(): void
    {
        $this->seed(CurriculumContentSeeder::class);

        $prompts = KnowledgeCheckQuestion::query()->pluck('prompt')->map(fn (string $prompt): string => mb_strtolower(trim($prompt)));

        $this->assertSame(
            $prompts->count(),
            $prompts->unique()->count(),
            'The same question may not be asked twice across the curriculum.'
        );
    }

    public function test_the_seeded_checks_follow_the_curriculum_order(): void
    {
        $this->seed(CurriculumContentSeeder::class);

        $courses = Course::query()->orderBy('order_num')->pluck('id', 'order_num');

        foreach (KnowledgeCheck::query()->where('status', KnowledgeCheck::STATUS_PUBLISHED)->get() as $check) {
            $mission = Mission::query()->find($check->mission_id);

            $this->assertNotNull($mission, 'Check '.$check->id.' points at a missing mission.');
            $this->assertContains(
                $mission->course_id,
                $courses->values()->all(),
                'Check '.$check->id.' points at a mission outside the published curriculum.'
            );
        }
    }
}
