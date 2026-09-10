<?php

namespace Tests\Unit;

use App\Models\SkillSession;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SkillSessionTimingTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_ends_at_adds_the_duration_to_the_start(): void
    {
        $session = $this->makeSession('2026-09-10 14:00:00', 47);

        $this->assertSame('2026-09-10 14:47:00', $session->endsAt()->format('Y-m-d H:i:s'));
    }

    public function test_session_is_not_started_before_its_scheduled_time(): void
    {
        Carbon::setTestNow('2026-09-10 13:59:59');
        $session = $this->makeSession('2026-09-10 14:00:00', 60);

        $this->assertFalse($session->hasStarted());
        $this->assertFalse($session->isInProgress());
        $this->assertFalse($session->hasEnded());
    }

    public function test_session_is_in_progress_at_the_exact_start_moment(): void
    {
        Carbon::setTestNow('2026-09-10 14:00:00');
        $session = $this->makeSession('2026-09-10 14:00:00', 60);

        $this->assertTrue($session->hasStarted());
        $this->assertTrue($session->isInProgress());
        $this->assertFalse($session->hasEnded());
    }

    public function test_session_is_still_in_progress_one_second_before_the_end(): void
    {
        Carbon::setTestNow('2026-09-10 14:59:59');
        $session = $this->makeSession('2026-09-10 14:00:00', 60);

        $this->assertTrue($session->isInProgress());
        $this->assertFalse($session->hasEnded());
    }

    public function test_session_has_ended_once_the_duration_elapses(): void
    {
        Carbon::setTestNow('2026-09-10 15:00:01');
        $session = $this->makeSession('2026-09-10 14:00:00', 60);

        $this->assertTrue($session->hasEnded());
        $this->assertFalse($session->isInProgress());
    }

    private function makeSession(string $scheduledAt, int $durationMinutes): SkillSession
    {
        return new SkillSession([
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $durationMinutes,
        ]);
    }
}
