<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Services\SectionProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    private SectionProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SectionProgressService::class);
    }

    public function test_overview_groups_sections_under_their_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['order_num' => 1]);
        $section = $this->section($course, 1);

        $tree = $this->service->overview($user);

        $this->assertCount(1, $tree);
        $this->assertSame($course->id, $tree->first()['course']->id);
        $this->assertSame($section->id, $tree->first()['sections']->first()['section']->id);
    }

    public function test_overview_derives_section_states_from_that_sections_own_missions(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $empty = $this->section($course, 1);
        $notStarted = $this->section($course, 2, 2);
        $done = $this->section($course, 3, 1);
        $inProgress = $this->section($course, 4, 2);

        $this->complete($user, $done->missions()->first());
        $this->complete($user, $inProgress->missions()->orderBy('order_num')->first());

        $states = $this->service->overview($user)
            ->first()['sections']
            ->map(fn (array $row): string => $row['state'])
            ->values()
            ->all();

        $this->assertSame(['EMPTY', 'NOT STARTED', 'DONE', 'IN PROGRESS'], $states);
    }

    private function section(Course $course, int $orderNum, int $missionCount = 0): Section
    {
        $section = Section::factory()->create(['course_id' => $course->id, 'order_num' => $orderNum]);

        for ($order = 1; $order <= $missionCount; $order++) {
            Mission::factory()->create([
                'course_id' => $course->id,
                'section_id' => $section->id,
                'order_num' => $order,
            ]);
        }

        $section->load('missions');

        return $section;
    }

    private function complete(User $user, Mission $mission): void
    {
        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 10,
            'completed_at' => now(),
        ]);
    }
}
