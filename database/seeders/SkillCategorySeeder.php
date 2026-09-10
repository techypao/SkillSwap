<?php

namespace Database\Seeders;

use App\Models\SkillCategory;
use Illuminate\Database\Seeder;

class SkillCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'technology-programming' => 'Technology & Programming',
            'design-creative' => 'Design & Creative',
            'business-finance' => 'Business & Finance',
            'engineering' => 'Engineering',
            'science-mathematics' => 'Science & Mathematics',
            'communication' => 'Communication',
            'languages' => 'Languages',
            'research-academic' => 'Research & Academic',
            'productivity-office-tools' => 'Productivity & Office Tools',
            'media-content-creation' => 'Media & Content Creation',
            'other-general-skills' => 'Other / General Skills',
        ];

        foreach ($categories as $slug => $name) {
            SkillCategory::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true]
            );
        }
    }
}
