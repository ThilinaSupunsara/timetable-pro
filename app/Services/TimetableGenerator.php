<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Allocation;
use App\Models\TimetableEntry;
use App\Models\PeriodTiming;
use Illuminate\Support\Facades\DB;

class TimetableGenerator
{
    /**
     * 1. තනි පන්තියක් සඳහා කාලසටහන සැකසීම (Single Generation)
     * Returns: දාගන්න බැරි වුන විෂයන් ලිස්ට් එකක්
     */
    public function generate($section_id)
    {
        $section = Section::with('classCategory')->findOrFail($section_id);
        $unassignedSubjects = [];

        DB::transaction(function () use ($section, $section_id, &$unassignedSubjects) {

            // 1. පරණ Timetable Entries මකන්න (Fresh Start for this section)
            TimetableEntry::where('section_id', $section_id)->delete();

            // 2. Settings වලින් වෙලාවල් ලබා ගැනීම
            $allTimings = PeriodTiming::where('class_category_id', $section->class_category_id)
                            ->orderBy('period_number')
                            ->get();

            $teachingPeriods = $allTimings->where('is_break', false)->pluck('period_number')->toArray();
            $breakPeriods = $allTimings->where('is_break', true)->pluck('period_number')->toArray();

            if (empty($teachingPeriods) && empty($breakPeriods)) {
                throw new \Exception("Settings Error: No time slots found! Please go to Settings > Configure Timings.");
            }

            // 3. Allocations ලබා ගැනීම (Priority Order)
            $allocations = Allocation::where('section_id', $section_id)
                            ->with(['subject', 'teacher'])
                            ->orderByDesc('is_fixed_slot')
                            ->orderByDesc('consecutive_periods')
                            ->get();

            // 4. Algorithm එක Run කිරීම (Common Helper Function භාවිතා කරයි)
            $this->processAllocations($allocations, $teachingPeriods, $breakPeriods, $section_id, $unassignedSubjects);
        });

        return $unassignedSubjects;
    }

    /**
     * 2. මුළු පාසලේම කාලසටහන එකවර සැකසීම (Global Generation)
     */
    public function generateAll()
    {
        $unassignedSubjects = [];

        DB::transaction(function () use (&$unassignedSubjects) {

            // 1. මුළු පාසලේම කාලසටහන මකන්න (Fresh Start)
            TimetableEntry::truncate();

            // 2. සියලුම Allocations ගන්න (Priority Order: Fixed > Double > Single)
            $allocations = Allocation::with(['section.classCategory.periodTimings', 'subject', 'teacher'])
                            ->orderByDesc('is_fixed_slot')
                            ->orderByDesc('consecutive_periods')
                            ->orderByDesc('periods_per_week')
                            ->get();

            // මෙහිදී අපි ලූප් එකක් හරහා යන්නේ නැතුව, processAllocations පාවිච්චි කරන්න අමාරුයි
            // මොකද හැම පන්තියටම Times වෙනස් නිසා. ඒ නිසා අපි Manual Loop එකක් යමු.

            $days = [1, 2, 3, 4, 5];

            foreach ($allocations as $alloc) {

                // මේ පන්තියට අදාල Valid Slots සොයා ගැනීම
                $timings = $alloc->section->classCategory->periodTimings;
                $teachingPeriods = $timings->where('is_break', false)->pluck('period_number')->toArray();
                $breakPeriods = $timings->where('is_break', true)->pluck('period_number')->toArray();

                if (empty($teachingPeriods)) {
                    $unassignedSubjects[] = "{$alloc->subject->name} ({$alloc->section->grade}-{$alloc->section->class_name}): No timings configured.";
                    continue;
                }

                // තනි Allocation එකක් Process කරන Logic එක
                $this->assignSingleAllocation($alloc, $teachingPeriods, $breakPeriods, $alloc->section_id, $unassignedSubjects, $days);
            }
        });

        return $unassignedSubjects;
    }

    /**
     * Common Logic: Allocations සමූහයක් Process කිරීම (Single Generate සඳහා)
     */
    private function processAllocations($allocations, $teachingPeriods, $breakPeriods, $sectionId, &$unassignedSubjects)
    {
        $days = [1, 2, 3, 4, 5];

        foreach ($allocations as $alloc) {
            $this->assignSingleAllocation($alloc, $teachingPeriods, $breakPeriods, $sectionId, $unassignedSubjects, $days);
        }
    }

    /**
     * Core Logic: එක් විෂයක් (Allocation) කාලසටහනට ඇතුලත් කිරීම
     */
    private function assignSingleAllocation($alloc, $teachingPeriods, $breakPeriods, $sectionId, &$unassignedSubjects, $days)
    {
        $periodsToAssign = $alloc->periods_per_week;

        // --- CASE 1: FIXED SLOT ---
        if ($alloc->is_fixed_slot && $alloc->fixed_day && $alloc->fixed_period) {
            $startPeriod = $alloc->fixed_period;
            $blockSize = $alloc->consecutive_periods;
            $canFix = true;

            // Block Validation
            for ($i = 0; $i < $blockSize; $i++) {
                $currentPeriod = $startPeriod + $i;

                // Interval Check
                if (in_array($currentPeriod, $breakPeriods)) {
                    // Single generate එකේදි විතරක් Error එක විසි කරන්න (Global එකේදි Skip කරන්න)
                    // නමුත් මෙතන අපි සරලව Fail කරමු.
                    $canFix = false; break;
                }

                // Existence & Collision Check
                if (!in_array($currentPeriod, $teachingPeriods) || $this->isSlotBusy($sectionId, $alloc->fixed_day, $currentPeriod)) {
                    $canFix = false; break;
                }
            }

            if ($canFix) {
                for ($i = 0; $i < $blockSize; $i++) {
                    $this->createEntry($alloc, $alloc->fixed_day, $startPeriod + $i);
                }
                $periodsToAssign -= $blockSize;
            } else {
                // දාගන්න බැරි වුනා
                // Global generate එකේදි Error throw කරොත් ඔක්කොම නවතිනවා. ඒ නිසා අපි List එකට දාමු.
                // Single generate එකේදි Exception එකක් ඕන නම් Controller එකෙන් handle වෙයි.
            }
        }

        // --- CASE 2: DYNAMIC FILLING ---
        $attempts = 0;
        $maxAttempts = 500;

        while ($periodsToAssign > 0 && $attempts < $maxAttempts) {

            $day = $days[array_rand($days)];
            $period = $teachingPeriods[array_rand($teachingPeriods)];

            // Spread Rule
            $shouldSpread = $alloc->periods_per_week <= 5;
            if ($shouldSpread && $this->hasSubjectOnDay($sectionId, $alloc->subject_id, $day)) {
                $attempts++; continue;
            }

            // Place Block
            if ($alloc->consecutive_periods > 1) {
                if ($this->canPlaceBlock($sectionId, $alloc->teacher_id, $day, $period, $alloc->consecutive_periods, $teachingPeriods)) {
                    for ($i = 0; $i < $alloc->consecutive_periods; $i++) {
                        $this->createEntry($alloc, $day, $period + $i);
                    }
                    $periodsToAssign -= $alloc->consecutive_periods;
                }
            }
            // Place Single
            else {
                if ($this->canPlace($sectionId, $alloc->teacher_id, $day, $period)) {
                    $this->createEntry($alloc, $day, $period);
                    $periodsToAssign--;
                }
            }
            $attempts++;
        }

        if ($periodsToAssign > 0) {
            $teacherName = $alloc->teacher ? $alloc->teacher->name : 'No Teacher';
            // Duplicate message නොවීම සඳහා check එකක්
            $msg = "{$alloc->subject->name} ({$periodsToAssign} missed) - Teacher: {$teacherName}";
            if (!in_array($msg, $unassignedSubjects)) {
                $unassignedSubjects[] = $msg;
            }
        }
    }

    // --- HELPER FUNCTIONS ---

    private function createEntry($alloc, $day, $period)
    {
        TimetableEntry::updateOrCreate(
            ['section_id' => $alloc->section_id, 'day_of_week' => $day, 'period_number' => $period],
            ['subject_id' => $alloc->subject_id, 'teacher_id' => $alloc->teacher_id]
        );
    }

    private function isSlotBusy($sectionId, $day, $period) {
        return TimetableEntry::where('section_id', $sectionId)
                    ->where('day_of_week', $day)
                    ->where('period_number', $period)
                    ->exists();
    }

    private function canPlace($sectionId, $teacherId, $day, $period)
    {
        // 1. Class Busy?
        if ($this->isSlotBusy($sectionId, $day, $period)) return false;

        // 2. Teacher Busy?
        if ($teacherId) {
            $teacherBusy = TimetableEntry::where('teacher_id', $teacherId)
                            ->where('day_of_week', $day)->where('period_number', $period)->exists();
            if ($teacherBusy) return false;
        }
        return true;
    }

    private function canPlaceBlock($sectionId, $teacherId, $day, $startPeriod, $length, $validPeriods)
    {
        for ($i = 0; $i < $length; $i++) {
            $currentPeriod = $startPeriod + $i;
            if (!in_array($currentPeriod, $validPeriods)) return false;
            if (!$this->canPlace($sectionId, $teacherId, $day, $currentPeriod)) return false;
        }
        return true;
    }

    private function hasSubjectOnDay($sectionId, $subjectId, $day)
    {
        return TimetableEntry::where('section_id', $sectionId)
                             ->where('subject_id', $subjectId)
                             ->where('day_of_week', $day)
                             ->exists();
    }
}
