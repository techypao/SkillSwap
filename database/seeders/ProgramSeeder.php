<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = [
            ['name' => 'Bachelor of Science in Information Technology', 'abbreviation' => 'BSIT'],
            ['name' => 'Bachelor of Science in Computer Science', 'abbreviation' => 'BSCS'],
            ['name' => 'Bachelor of Science in Information Systems', 'abbreviation' => 'BSIS'],
            ['name' => 'Bachelor of Science in Accountancy', 'abbreviation' => 'BSA'],
            ['name' => 'Bachelor of Science in Business Administration', 'abbreviation' => 'BSBA'],
            ['name' => 'Bachelor of Science in Nursing', 'abbreviation' => 'BSN'],
            ['name' => 'Bachelor of Science in Civil Engineering', 'abbreviation' => 'BSCE'],
            ['name' => 'Bachelor of Science in Computer Engineering', 'abbreviation' => 'BSCpE'],
            ['name' => 'Bachelor of Science in Electronics Engineering', 'abbreviation' => 'BSECE'],
            ['name' => 'Bachelor of Arts in Communication', 'abbreviation' => 'BA Communication'],
            ['name' => 'Bachelor of Multimedia Arts', 'abbreviation' => 'BMMA'],
        ];

        foreach ($programs as $program) {
            Program::updateOrCreate(
                ['name' => $program['name']],
                [
                    'abbreviation' => $program['abbreviation'],
                    'is_active' => true,
                ]
            );
        }
    }
}
