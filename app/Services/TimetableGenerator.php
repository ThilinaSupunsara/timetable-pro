<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Allocation;
use App\Models\TimetableEntry;
use App\Models\PeriodTiming;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

class TimetableGenerator
{
    /**
     * 1. Single Class Generation (With 10 Retries)
     */
    public function generate($section_id)
    {
        $section = Section::with('classCategory')->findOrFail($section_id);

        // පන්තියේ නම හදාගැනීම (Error Messages සඳහා)
        $sectionName = "{$section->grade}-{$section->class_name}";

        // --- STEP 0: VALIDATION ---
        $preErrors = $this->validateSystemData($section_id);
        if (!empty($preErrors)) {
            return $preErrors;
        }

        // Optimization: Loop එකට කලින් Data ටික ගන්න (එක පාරයි DB Call වෙන්නේ)
        $allTimings = PeriodTiming::where('class_category_id', $section->class_category_id)
                                ->orderBy('period_number')
                                ->get();
        $teachingPeriods = $allTimings->where('is_break', false)->pluck('period_number')->toArray();
        $breakPeriods = $allTimings->where('is_break', true)->pluck('period_number')->toArray();

        $allocations = Allocation::where('section_id', $section_id)
                        ->with(['subject', 'teacher'])
                        ->get();

        if (empty($teachingPeriods)) return ["No teaching periods found."];

        $finalUnassignedSubjects = [];

        // Transaction එක ඇතුලේ තමයි ඔක්කොම වෙන්නේ
        DB::transaction(function () use ($section_id, $sectionName, $teachingPeriods, $breakPeriods, $allocations, &$finalUnassignedSubjects) {

            // --- RETRY LOOP (10 Attempts) ---
            for ($attempt = 1; $attempt <= 10; $attempt++) {

                $unassignedSubjects = []; // මේ වටයේ වැරදි ලිස්ට් එක (Reset වෙනවා)

                // 1. Clear Old Entries (හැම වටයකදීම පරණ ඒවා මකනවා)
                TimetableEntry::where('section_id', $section_id)->delete();

                // 2. Run Algorithm (Pass loaded data)
                $this->processAllocations($allocations, $teachingPeriods, $breakPeriods, $section_id, $sectionName, $unassignedSubjects);

                // --- CHECK SUCCESS ---
                if (empty($unassignedSubjects)) {
                    // කිසිම අවුලක් නෑ! (Success)
                    // ලූප් එක නවත්වන්න (Break), මොකද වැඩේ හරි.
                    $finalUnassignedSubjects = [];
                    break;
                } else {
                    // අවුල් තියෙනවා.
                    // මේක අන්තිම (10 වෙනි) උත්සාහය නම්, වැරදි ටික එළියට යවන්න.
                    if ($attempt == 10) {
                        $finalUnassignedSubjects = $unassignedSubjects;
                    }
                    // නැත්නම් Loop එක ඊළඟ වටේට යයි (DB එක Clear වෙලා ආයේ හැදෙයි)
                }
            }
        });

        return $finalUnassignedSubjects;
    }

    /**
     * 2. Global Generation
     */
    public function generateAll()
    {
        $validationErrors = $this->validateSystemData();

        if (!empty($validationErrors)) {
            return $validationErrors;
        }

        $allUnassigned = [];
        $startTime = microtime(true);
        $timeLimit = 300;

        TimetableEntry::truncate();

        $sections = Section::orderByDesc('grade')->get();

        foreach ($sections as $section) {
            if ((microtime(true) - $startTime) >= $timeLimit) {
                $allUnassigned[] = "TIMEOUT: Stopped after generating {$section->grade}-{$section->class_name}.";
                break;
            }

            try {
                // මෙතනින් generate() කතා කරනකොට ඒක ඇතුලේ 10 පාරක් retry වෙනවා
                $errors = $this->generate($section->id);
                if (!empty($errors)) {
                    $allUnassigned = array_merge($allUnassigned, $errors);
                }
            } catch (\Exception $e) {
                $allUnassigned[] = "Error in Class {$section->grade}-{$section->class_name}: " . $e->getMessage();
            }
        }

        return $allUnassigned;
    }

    /**
     * Common Logic: Process Allocations (ඔබේ පරණ කෝඩ් එකමයි)
     */
    private function processAllocations($allocations, $teachingPeriods, $breakPeriods, $sectionId, $sectionName, &$unassignedSubjects)
    {
        $days = [1, 2, 3, 4, 5];

        $groupedAllocations = $allocations->groupBy(function ($item) {
            return !empty($item->bucket_name) ? 'BUCKET_' . $item->bucket_name : 'SINGLE_' . $item->id;
        });

        // Smart Sorting
        $sortedGroups = $groupedAllocations->sortByDesc(function ($group, $key) {
            $alloc = $group->first();
            $score = 0;
            if ($alloc->is_fixed_slot) $score += 10000;
            $score += ($alloc->consecutive_periods * 2000);
            if (str_starts_with($key, 'BUCKET_')) $score += 1000;
            return $score;
        });

        foreach ($sortedGroups as $groupKey => $group) {
            if (str_starts_with($groupKey, 'BUCKET_')) {
                $this->assignBucketGroup($group, $teachingPeriods, $breakPeriods, $sectionId, $sectionName, $unassignedSubjects, $days);
            } else {
                $this->assignSingleAllocation($group->first(), $teachingPeriods, $breakPeriods, $sectionId, $sectionName, $unassignedSubjects, $days);
            }
        }
    }

    /**
     * Bucket Group Logic (ඔබේ පරණ කෝඩ් එකමයි)
     */
    private function assignBucketGroup($group, $teachingPeriods, $breakPeriods, $sectionId, $sectionName, &$unassignedSubjects, $days)
    {
        $firstAlloc = $group->first();
        $periodsToAssign = $firstAlloc->periods_per_week;
        $blockSize = $firstAlloc->consecutive_periods;

        $attempts = 0;
        $maxAttempts = 1000;

        while ($periodsToAssign > 0 && $attempts < $maxAttempts) {
            $day = $days[array_rand($days)];
            $period = $teachingPeriods[array_rand($teachingPeriods)];

            // --- STRICT SPREAD RULE FOR BUCKETS ---
            $dailyLimit = ($firstAlloc->periods_per_week <= 5) ? 1 : 2;
            $skipDay = false;

            foreach ($group as $alloc) {
                $existingCount = TimetableEntry::where('section_id', $sectionId)
                                    ->where('subject_id', $alloc->subject_id)
                                    ->where('day_of_week', $day)
                                    ->count();

                if ($existingCount >= $dailyLimit) { $skipDay = true; break; }
                if ($blockSize > 1 && $existingCount > 0) { $skipDay = true; break; }
            }
            if ($skipDay) { $attempts++; continue; }
            // --------------------------------------

            // Class Busy Check (Block aware)
            if ($blockSize > 1) {
                $isSlotFree = true;
                for($k=0; $k<$blockSize; $k++) {
                     if($this->isSlotBusy($sectionId, $day, $period+$k) || in_array($period+$k, $breakPeriods)) {
                         $isSlotFree = false; break;
                     }
                }
                if(!$isSlotFree) { $attempts++; continue; }
            } else {
                if ($this->isSlotBusy($sectionId, $day, $period)) { $attempts++; continue; }
            }

            // Teachers Busy Check
            $allTeachersFree = true;
            for($k=0; $k<$blockSize; $k++) {
                $currentP = $period + $k;
                foreach ($group as $alloc) {
                    if ($alloc->teacher_id && $this->isTeacherBusy($alloc->teacher_id, $day, $currentP)) {
                        $allTeachersFree = false; break 2;
                    }
                }
            }

            if ($allTeachersFree) {
                for($k=0; $k<$blockSize; $k++) {
                    foreach ($group as $alloc) {
                        $this->createEntry($alloc, $day, $period + $k);
                    }
                }
                $periodsToAssign -= $blockSize;
            }
            $attempts++;
        }

        if ($periodsToAssign > 0) {
            $names = $group->pluck('subject.name')->implode(', ');
            $unassignedSubjects[] = "Bucket [{$names}] missed {$periodsToAssign} slots in Class [{$sectionName}]";
        }
    }

    /**
     * Single Allocation Logic (ඔබේ පරණ කෝඩ් එකමයි)
     */
    private function assignSingleAllocation($alloc, $teachingPeriods, $breakPeriods, $sectionId, $sectionName, &$unassignedSubjects, $days)
    {
        $periodsToAssign = $alloc->periods_per_week;

        // --- FIXED SLOT ---
        if ($alloc->is_fixed_slot && $alloc->fixed_day && $alloc->fixed_period) {
            $startPeriod = $alloc->fixed_period;
            $blockSize = $alloc->consecutive_periods;
            $canFix = true;

            for ($i = 0; $i < $blockSize; $i++) {
                $currentPeriod = $startPeriod + $i;
                if (in_array($currentPeriod, $breakPeriods)) { $canFix = false; break; }
                if (!in_array($currentPeriod, $teachingPeriods) ||
                    $this->isSlotBusy($sectionId, $alloc->fixed_day, $currentPeriod) ||
                    ($alloc->teacher_id && $this->isTeacherBusy($alloc->teacher_id, $alloc->fixed_day, $currentPeriod))
                ) { $canFix = false; break; }
            }

            if ($canFix) {
                for ($i = 0; $i < $blockSize; $i++) {
                    $this->createEntry($alloc, $alloc->fixed_day, $startPeriod + $i);
                }
                $periodsToAssign -= $blockSize;
            } else {
                 $unassignedSubjects[] = "{$alloc->subject->name}: Fixed Slot Blocked in Class [{$sectionName}].";
            }
        }

        // --- DYNAMIC FILLING ---
        $attempts = 0;
        $maxAttempts = 2000;
        $dailyLimit = ($alloc->periods_per_week <= 5) ? 1 : 2;

        while ($periodsToAssign > 0 && $attempts < $maxAttempts) {
            $day = $days[array_rand($days)];
            $period = $teachingPeriods[array_rand($teachingPeriods)];

            // --- STRICT SPREAD RULE ---
            $existingCount = TimetableEntry::where('section_id', $sectionId)
                                ->where('subject_id', $alloc->subject_id)
                                ->where('day_of_week', $day)
                                ->count();

            if ($existingCount >= $dailyLimit) { $attempts++; continue; }
            if ($alloc->consecutive_periods > 1 && $existingCount > 0) { $attempts++; continue; }

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
            $unassignedSubjects[] = "{$alloc->subject->name} ({$periodsToAssign} missed) in Class [{$sectionName}]";
        }
    }

    // --- HELPER FUNCTIONS ---

    private function createEntry($alloc, $day, $period) {
        TimetableEntry::create([
            'section_id' => $alloc->section_id,
            'day_of_week' => $day,
            'period_number' => $period,
            'subject_id' => $alloc->subject_id,
            'teacher_id' => $alloc->teacher_id
        ]);
    }

    private function isSlotBusy($sectionId, $day, $period) {
        return TimetableEntry::where('section_id', $sectionId)->where('day_of_week', $day)->where('period_number', $period)->exists();
    }

    private function isTeacherBusy($teacherId, $day, $period) {
        return TimetableEntry::where('teacher_id', $teacherId)->where('day_of_week', $day)->where('period_number', $period)->exists();
    }

    private function canPlace($sectionId, $teacherId, $day, $period) {
        if ($this->isSlotBusy($sectionId, $day, $period)) return false;
        if ($teacherId && $this->isTeacherBusy($teacherId, $day, $period)) return false;
        return true;
    }

    private function canPlaceBlock($sectionId, $teacherId, $day, $startPeriod, $length, $validPeriods) {
        for ($i = 0; $i < $length; $i++) {
            $currentPeriod = $startPeriod + $i;
            if (!in_array($currentPeriod, $validPeriods)) return false;
            if (!$this->canPlace($sectionId, $teacherId, $day, $currentPeriod)) return false;
        }
        return true;
    }

    // --- VALIDATION (Gap Analysis) ---
    public function validateSystemData($specificSectionId = null)
    {
        TimetableEntry::truncate();
        $errors = [];
        $maxPeriodsPerDayConfig = PeriodTiming::where('is_break', false)
                            ->selectRaw('count(*) as count, class_category_id')
                            ->groupBy('class_category_id')->orderByDesc('count')->first();
        $dailySlots = $maxPeriodsPerDayConfig ? $maxPeriodsPerDayConfig->count : 8;
        $weeklySlots = $dailySlots * 5;
        $breakPeriods = PeriodTiming::where('is_break', true)->pluck('period_number')->toArray();

        $query = Allocation::query();
        if ($specificSectionId) {
            $query->where('section_id', $specificSectionId);
        }
        $allocationsToCheck = $query->whereNotNull('teacher_id')->with(['teacher', 'section'])->get();

        foreach ($allocationsToCheck as $alloc) {
            $teacher = $alloc->teacher;
            $sectionName = $alloc->section->grade . '-' . $alloc->section->class_name;
            $blockSize = $alloc->consecutive_periods;

            $totalAssigned = Allocation::where('teacher_id', $teacher->id)->sum('periods_per_week');
            if ($totalAssigned > $weeklySlots) {
                $errors[] = "🛑 CRITICAL: Teacher '{$teacher->name}' needs {$totalAssigned} periods total. Max is {$weeklySlots}.";
                continue;
            }

            $busySlots = TimetableEntry::where('teacher_id', $teacher->id)
                            ->when($specificSectionId, function($q) use ($specificSectionId) {
                                $q->where('section_id', '!=', $specificSectionId);
                            })
                            ->get()
                            ->groupBy('day_of_week');

            $sessionsNeeded = floor($alloc->periods_per_week / $blockSize);
            $possibleSlotsFound = 0;

            for ($day = 1; $day <= 5; $day++) {
                $bookedPeriods = isset($busySlots[$day]) ? $busySlots[$day]->pluck('period_number')->toArray() : [];
                for ($p = 1; $p <= ($dailySlots - $blockSize + 1); $p++) {
                    $canFit = true;
                    for ($k = 0; $k < $blockSize; $k++) {
                        $currentP = $p + $k;
                        if (in_array($currentP, $bookedPeriods) || in_array($currentP, $breakPeriods)) {
                            $canFit = false; break;
                        }
                    }
                    if ($canFit) { $possibleSlotsFound++; break; }
                }
            }

            if ($possibleSlotsFound < $sessionsNeeded) {
                $type = ($blockSize == 2) ? "Double" : "Block-{$blockSize}";
                $errors[] = "🧩 IMPOSSIBLE FIT: Teacher '{$teacher->name}' needs a {$type} Period in [{$sectionName}]. But due to other classes, they don't have enough CONTINUOUS free slots.";
            }
        }
        return array_unique($errors);
    }
}
