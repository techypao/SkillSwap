<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\School;
use App\Models\User;
use Database\Seeders\SchoolSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SchoolSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_school_seeder_is_idempotent(): void
    {
        $this->seed(SchoolSeeder::class);
        $firstRunCount = School::count();

        $this->seed(SchoolSeeder::class);

        $this->assertGreaterThan(50, $firstRunCount);
        $this->assertSame($firstRunCount, School::count());
        $this->assertSame($firstRunCount, School::where('is_active', true)->count());
        $this->assertDatabaseHas('schools', ['name' => 'Far Eastern University', 'abbreviation' => 'FEU']);
        $this->assertDatabaseHas('schools', ['name' => 'FEU Institute of Technology', 'city' => 'Manila']);
        $this->assertDatabaseHas('schools', ['name' => 'University of the Philippines Diliman', 'abbreviation' => 'UPD']);
    }

    public function test_existing_school_with_different_casing_is_reused(): void
    {
        $existingSchool = School::create(['name' => 'far eastern university', 'is_active' => false]);
        $user = User::factory()->create(['school_id' => $existingSchool->id]);

        $this->seed(SchoolSeeder::class);

        $existingSchool->refresh();
        $this->assertSame(1, School::query()->whereRaw('LOWER(name) = ?', ['far eastern university'])->count());
        $this->assertSame('FEU', $existingSchool->abbreviation);
        $this->assertFalse($existingSchool->is_active);
        $this->assertSame($existingSchool->id, $user->fresh()->school_id);
    }
}
