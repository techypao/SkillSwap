<?php

namespace Database\Seeders;

use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Database\Seeder;

class CanonicalSkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catalog = [
            'technology-programming' => [
                'Programming Fundamentals', 'C', 'C++', 'C#', 'Java', 'Python', 'PHP',
                'JavaScript', 'TypeScript', 'SQL', 'HTML', 'CSS', 'Web Development',
                'Frontend Development', 'Backend Development', 'Laravel', 'React', 'Vue.js',
                'Node.js', 'Mobile App Development', 'Flutter', 'Android Development', 'Git',
                'GitHub', 'Database Design', 'MySQL', 'SQLite', 'API Development',
            ],
            'design-creative' => [
                'Graphic Design', 'UI/UX Design', 'Figma', 'Canva', 'Adobe Photoshop',
                'Adobe Illustrator', 'Digital Illustration',
            ],
            'business-finance' => [
                'Accounting', 'Financial Accounting', 'Managerial Accounting', 'Marketing',
                'Digital Marketing', 'Entrepreneurship', 'Financial Management', 'Business Analytics',
            ],
            'engineering' => [
                'AutoCAD', 'Engineering Drawing', 'Circuit Analysis', 'Electronics', 'Arduino',
                'CAD', 'Engineering Mathematics',
            ],
            'science-mathematics' => [
                'Calculus', 'Statistics', 'Probability', 'Physics', 'Chemistry', 'Biology',
                'Discrete Mathematics', 'Linear Algebra',
            ],
            'communication' => [
                'Public Speaking', 'Presentation Skills', 'Technical Writing', 'Academic Writing',
                'Communication Skills',
            ],
            'languages' => ['English', 'Filipino', 'Japanese', 'Korean', 'Spanish'],
            'research-academic' => [
                'Research Methods', 'Thesis Writing', 'Literature Review',
                'Citation and Referencing', 'Data Analysis',
            ],
            'productivity-office-tools' => [
                'Microsoft Excel', 'Microsoft Word', 'Microsoft PowerPoint', 'Google Sheets',
                'Google Docs', 'Power BI',
            ],
            'media-content-creation' => [
                'Photography', 'Video Editing', 'Content Creation', 'Adobe Premiere Pro', 'CapCut',
            ],
            'other-general-skills' => [
                'Time Management', 'Leadership', 'Project Management', 'Teamwork',
            ],
        ];

        foreach ($catalog as $categorySlug => $skillNames) {
            $categoryId = SkillCategory::query()
                ->where('slug', $categorySlug)
                ->value('id');

            foreach ($skillNames as $skillName) {
                $skill = Skill::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($skillName)])
                    ->first();

                if ($skill) {
                    $skill->update([
                        'skill_category_id' => $categoryId,
                        'is_approved' => true,
                    ]);

                    continue;
                }

                Skill::create([
                    'name' => $skillName,
                    'skill_category_id' => $categoryId,
                    'is_approved' => true,
                    'created_by' => null,
                ]);
            }
        }
    }
}
