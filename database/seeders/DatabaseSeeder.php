<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ClassCategory;
use App\Models\PeriodTiming;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Section;
use App\Models\Allocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // 1. CLEAR OLD DATA
        Schema::disableForeignKeyConstraints();

        Allocation::truncate();
        Section::truncate();
        Teacher::truncate();
        Subject::truncate();
        PeriodTiming::truncate();
        ClassCategory::truncate();
        DB::table('subject_teacher')->truncate();
        DB::table('timetable_entries')->truncate();

        Schema::enableForeignKeyConstraints();

        $this->command->info('Old data cleared!');

        // 2. CREATE CATEGORIES & TIMINGS
        $categories = [
            ['name' => 'Primary (Gr 1-5)', 'desc' => 'Primary Section'],
            ['name' => 'Secondary (Gr 6-11)', 'desc' => 'Middle School'],
            ['name' => 'Collegiate (Gr 12-13)', 'desc' => 'A/L Section']
        ];

        $catIds = [];
        foreach ($categories as $cat) {
            $c = ClassCategory::create(['name' => $cat['name'], 'description' => $cat['desc']]);
            $catIds[$cat['name']] = $c->id;

            $start = strtotime('07:50:00');
            for ($i = 1; $i <= 9; $i++) {
                $isBreak = ($i == 5);
                $end = $start + ($isBreak ? 20 * 60 : 40 * 60);

                PeriodTiming::create([
                    'class_category_id' => $c->id,
                    'period_number' => $i,
                    'start_time' => date('H:i:s', $start),
                    'end_time' => date('H:i:s', $end),
                    'is_break' => $isBreak,
                    'label' => $isBreak ? 'Interval' : "Period " . ($i > 5 ? $i - 1 : $i)
                ]);
                $start = $end;
            }
        }
        $this->command->info('Time slots created!');

        // 3. CREATE SUBJECTS
        $coreSubjects = [
            'Mathematics' => 'MAT', 'Science' => 'SCI', 'English' => 'ENG',
            'Sinhala' => 'SIN', 'Religion' => 'REL', 'History' => 'HIS'
        ];
        $bucketSubjects = [
            'Art' => 'ART', 'Music' => 'MUS', 'Dancing' => 'DNC'
        ];
        $alSubjects = [
            'Combined Maths' => 'CM', 'Physics' => 'PHY', 'Chemistry' => 'CHE',
            'Biology' => 'BIO', 'Econ' => 'ECO', 'Accounting' => 'ACC'
        ];

        $subIds = [];
        foreach (array_merge($coreSubjects, $bucketSubjects, $alSubjects) as $name => $code) {
            $s = Subject::create(['name' => $name, 'code' => $code]);
            $subIds[$name] = $s->id;
        }
        $this->command->info('Subjects created!');

        // 4. CREATE TEACHERS
        $teachers = [];
        for ($i = 0; $i < 50; $i++) {
            $t = Teacher::create([
                'name' => $faker->name,
                'short_code' => strtoupper($faker->bothify('??#'))
            ]);
            $teachers[] = $t;

            $randomSubs = Subject::inRandomOrder()->limit(rand(2, 4))->pluck('id');
            $t->subjects()->attach($randomSubs);
        }
        $this->command->info('Teachers created!');

        // 5. CREATE SECTIONS & ALLOCATIONS
        for ($grade = 1; $grade <= 13; $grade++) {

            $catId = $catIds['Secondary (Gr 6-11)'];
            if ($grade <= 5) $catId = $catIds['Primary (Gr 1-5)'];
            if ($grade >= 12) $catId = $catIds['Collegiate (Gr 12-13)'];

            $classes = ['A', 'B', 'C', 'D'];

            foreach ($classes as $className) {
                $section = Section::create([
                    'class_category_id' => $catId,
                    'grade' => $grade,
                    'class_name' => $className
                ]);

                // --- ALLOCATION LOGIC ---

                if ($grade >= 6 && $grade <= 11) {
                    // O/L Classes

                    // A. Core Subjects
                    foreach ($coreSubjects as $subName => $code) {
                        // FIX: 'subjects.id' ලෙස වෙනස් කරන ලදී
                        $teacher = Teacher::whereHas('subjects', function($q) use ($subIds, $subName){
                            $q->where('subjects.id', $subIds[$subName]);
                        })->inRandomOrder()->first();

                        Allocation::create([
                            'section_id' => $section->id,
                            'subject_id' => $subIds[$subName],
                            'teacher_id' => $teacher ? $teacher->id : null,
                            'periods_per_week' => 5,
                            'consecutive_periods' => 1
                        ]);
                    }

                    // B. BUCKET SUBJECTS
                    foreach ($bucketSubjects as $subName => $code) {
                        // FIX: 'subjects.id' ලෙස වෙනස් කරන ලදී
                        $teacher = Teacher::whereHas('subjects', function($q) use ($subIds, $subName){
                            $q->where('subjects.id', $subIds[$subName]);
                        })->inRandomOrder()->first();

                        Allocation::create([
                            'section_id' => $section->id,
                            'subject_id' => $subIds[$subName],
                            'teacher_id' => $teacher ? $teacher->id : null,
                            'periods_per_week' => 2,
                            'consecutive_periods' => 1,
                            'bucket_name' => 'AES_GRP'
                        ]);
                    }

                } elseif ($grade >= 12) {
                    // A/L Classes
                    $subs = ['Combined Maths', 'Physics', 'Chemistry'];
                    foreach ($subs as $subName) {
                        // FIX: 'subjects.id' ලෙස වෙනස් කරන ලදී
                        $teacher = Teacher::whereHas('subjects', function($q) use ($subIds, $subName){
                            $q->where('subjects.id', $subIds[$subName]);
                        })->inRandomOrder()->first();

                        Allocation::create([
                            'section_id' => $section->id,
                            'subject_id' => $subIds[$subName],
                            'teacher_id' => $teacher ? $teacher->id : null,
                            'periods_per_week' => 8,
                            'consecutive_periods' => 2
                        ]);
                    }
                } else {
                    // Primary Classes
                    foreach (['Mathematics', 'English', 'Sinhala', 'Religion'] as $subName) {
                        // FIX: 'subjects.id' ලෙස වෙනස් කරන ලදී
                         $teacher = Teacher::whereHas('subjects', function($q) use ($subIds, $subName){
                            $q->where('subjects.id', $subIds[$subName]);
                        })->inRandomOrder()->first();

                        Allocation::create([
                            'section_id' => $section->id,
                            'subject_id' => $subIds[$subName],
                            'teacher_id' => $teacher ? $teacher->id : null,
                            'periods_per_week' => 5,
                            'consecutive_periods' => 1
                        ]);
                    }
                }
            }
        }
        $this->command->info('Sections & Allocations created successfully!');
    }
}
