<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            'Web Development',
            'Mobile App Development',
            'UI/UX Design',
            'Graphic Design',
            'Python',
            'Java',
            'PHP',
            'Laravel',
            'JavaScript',
            'React',
            'Photography',
            'Video Editing',
            'Public Speaking',
            'Microsoft Excel',
            'Data Analysis',
            'Guitar',
            'English Conversation',
            'Mathematics',
            'Project Management',
            'Digital Marketing',
        ];

        foreach ($skills as $skill) {
            Skill::updateOrCreate(
                ['name' => $skill],
                [
                    'is_approved' => true,
                    'created_by' => null,
                ]
            );
        }
    }
}