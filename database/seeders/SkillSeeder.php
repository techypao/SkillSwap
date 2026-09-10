<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SkillCategorySeeder::class,
            CanonicalSkillSeeder::class,
        ]);
    }
}
