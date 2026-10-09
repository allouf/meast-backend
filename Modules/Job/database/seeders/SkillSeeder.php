<?php

namespace Modules\Job\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Job\Models\Skill;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = [
            ['name' => 'PHP', 'sort_order' => 1],
            ['name' => 'Laravel', 'sort_order' => 2],
            ['name' => 'JavaScript', 'sort_order' => 3],
            ['name' => 'React', 'sort_order' => 4],
            ['name' => 'Vue.js', 'sort_order' => 5],
            ['name' => 'MySQL', 'sort_order' => 6],
            ['name' => 'PostgreSQL', 'sort_order' => 7],
            ['name' => 'Redis', 'sort_order' => 8],
            ['name' => 'Docker', 'sort_order' => 9],
            ['name' => 'Git', 'sort_order' => 10],
            ['name' => 'REST APIs', 'sort_order' => 11],
            ['name' => 'TypeScript', 'sort_order' => 12],
            ['name' => 'Node.js', 'sort_order' => 13],
            ['name' => 'HTML/CSS', 'sort_order' => 14],
            ['name' => 'Problem Solving', 'sort_order' => 15],
            ['name' => 'Communication', 'sort_order' => 16],
            ['name' => 'Project Management', 'sort_order' => 17],
            ['name' => 'Agile/Scrum', 'sort_order' => 18],
        ];

        foreach ($skills as $skill) {
            Skill::firstOrCreate(
                ['name' => $skill['name']],
                array_merge($skill, ['is_active' => true])
            );
        }
    }
}
