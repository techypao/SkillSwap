<?php

namespace Tests\Unit;

use App\Models\SkillSession;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_session_has_ended_at_the_exact_end_moment(): void
    {
        $this->travelTo('2026-09-10 15:00:00');
        $session = $this->makeSession('2026-09-10 14:00:00', 60);

        $this->assertTrue($session->hasEnded());
        $this->assertFalse($session->isInProgress());
        $this->assertSame('2026-09-10 14:00:00', $session->scheduled_at->format('Y-m-d H:i:s'));
    }

    #[DataProvider('invalidSchedules')]
    public function test_missing_or_invalid_schedule_data_never_counts_as_ended(mixed $scheduledAt, mixed $durationMinutes): void
    {
        $this->travelTo('2026-09-10 15:00:00');
        $session = new SkillSession;
        $session->setRawAttributes(['scheduled_at' => $scheduledAt, 'duration_minutes' => $durationMinutes]);

        $this->assertNull($session->endsAt());
        $this->assertFalse($session->hasEnded());
        $this->assertFalse($session->isInProgress());
    }

    public static function invalidSchedules(): array
    {
        return [
            'missing start' => [null, 60],
            'empty start' => ['', 60],
            'malformed start' => ['invalid-date', 60],
            'impossible date' => ['2026-02-30 14:00:00', 60],
            'relative start' => ['yesterday', 60],
            'missing duration' => ['2026-09-10 14:00:00', null],
            'zero duration' => ['2026-09-10 14:00:00', 0],
            'negative duration' => ['2026-09-10 14:00:00', -60],
            'fractional duration' => ['2026-09-10 14:00:00', 30.5],
            'malformed duration' => ['2026-09-10 14:00:00', 'invalid'],
            'excessive duration' => ['2026-09-10 14:00:00', 481],
        ];
    }

    private function makeSession(string $scheduledAt, int $durationMinutes): SkillSession
    {
        return new SkillSession([
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $durationMinutes,
        ]);
    }
}
