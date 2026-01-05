<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Section;
use App\Models\TimetableEntry;
use App\Models\Allocation;
use App\Models\PeriodTiming;
use App\Models\Teacher;
use App\Services\TimetableGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Process;

class TimetableController extends Controller
{
/**
     * 1. SINGLE CLASS GENERATION
     */
    public function generateSingleWithPython(Request $request, TimetableGenerator $validatorService, $sectionId)
    {
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $section = Section::with('classCategory.periodTimings')->findOrFail($sectionId);

        // --- STEP 1: Basic PHP Validations ---
        $slotsPerDay = $section->classCategory->periodTimings->where('is_break', false)->count();
        $totalTeachingSlots = $slotsPerDay * 5;
        $totalAssignedPeriods = Allocation::where('section_id', $sectionId)->sum('periods_per_week');

        if ($totalTeachingSlots == 0) return back()->with('error', 'Settings Error: No teaching slots found.');

        if ($totalAssignedPeriods > $totalTeachingSlots) {
            $diff = $totalAssignedPeriods - $totalTeachingSlots;
            return back()->with('error', "Capacity Error: Assigned {$totalAssignedPeriods} periods, but only {$totalTeachingSlots} teaching slots available. Remove {$diff} periods.");
        }

        // --- STEP 2: Gap Analysis ---
        $preErrors = $validatorService->validateSystemData($sectionId);
        if (!empty($preErrors)) return back()->with('error', 'Validation Failed: ' . implode(', ', $preErrors));

        // --- STEP 3: PREPARE DATA ---

        // A. Max Period (Intervals ඇතුලත්ව මුළු පීරියඩ් ගණන)
        $maxPeriod = PeriodTiming::where('class_category_id', $section->class_category_id)
                        ->max('period_number');

        // B. Break Map (Intervals)
        $breakPeriods = $section->classCategory->periodTimings
                            ->where('is_break', true)
                            ->pluck('period_number')
                            ->toArray();
        $breakMap = [$sectionId => array_values($breakPeriods)];

        // C. Allocations with Relations
        $allocationsQuery = Allocation::where('section_id', $sectionId)
                        ->with(['teacher', 'subject', 'section']); // Section එකත් Load කරනවා

        $allocations = $this->formatAllocationsForPython($allocationsQuery->get());

        // D. Busy Slots
        $teacherIds = array_filter(array_column($allocations, 'teacher_id'));
        $busySlots = TimetableEntry::whereIn('teacher_id', $teacherIds)
                        ->where('section_id', '!=', $sectionId)
                        ->select('teacher_id', 'day_of_week as day', 'period_number as period')
                        ->get()->toArray();

        $inputData = [
            'num_periods' => (int)$maxPeriod, // පේළි අඩුවීම නවත්වන විසඳුම
            'allocations' => $allocations,
            'blocked' => $busySlots,
            'break_map' => $breakMap // Intervals
        ];

        return $this->runPythonScript($inputData, false, $sectionId);
    }

    /**
     * 2. GLOBAL GENERATION
     */
    public function generateWithPython(TimetableGenerator $validatorService)
    {
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $validationErrors = $validatorService->validateSystemData();
        if (!empty($validationErrors)) {
            return redirect()->route('dashboard')
                    ->with('warning_list', $validationErrors)
                    ->with('error', 'Pre-Check Failed! Please fix issues below.');
        }

        // Global Max Period
        $maxPeriod = PeriodTiming::max('period_number');

        // Global Allocations
        $allocationsQuery = Allocation::with(['teacher', 'subject', 'section']);
        $allocations = $this->formatAllocationsForPython($allocationsQuery->get());

        // Global Break Map
        $sections = Section::with('classCategory.periodTimings')->get();
        $breakMap = [];
        foreach($sections as $sec) {
            $breaks = $sec->classCategory->periodTimings
                        ->where('is_break', true)
                        ->pluck('period_number')
                        ->toArray();
            if (!empty($breaks)) {
                $breakMap[$sec->id] = array_values($breaks);
            }
        }

        $inputData = [
            'num_periods' => (int)$maxPeriod,
            'allocations' => $allocations,
            'blocked' => [],
            'break_map' => $breakMap
        ];

        return $this->runPythonScript($inputData, true);
    }

    // --- Helper: Format Data ---
    private function formatAllocationsForPython($collection)
    {
        return $collection->map(function ($item) {
            $secName = $item->section ? ($item->section->grade . '-' . $item->section->class_name) : 'Unknown';

            return [
                'id' => $item->id,
                'teacher_id' => $item->teacher_id,
                'teacher_name' => $item->teacher ? $item->teacher->name : 'No Teacher',
                'subject_id' => $item->subject_id,
                'subject_name' => $item->subject ? $item->subject->name : 'Unknown',
                'section_id' => $item->section_id,
                'section_name' => $secName,
                'total_periods' => $item->periods_per_week,
                'duration' => $item->consecutive_periods,
                'is_fixed' => $item->is_fixed_slot,
                'fixed_day' => $item->fixed_day,
                'fixed_period' => $item->fixed_period,
                'bucket_name' => $item->bucket_name // <--- NEW: Bucket Name යවනවා
            ];
        })->toArray();
    }

    // --- Helper: Run Python ---
    private function runPythonScript($inputData, $isGlobal, $sectionId = null)
    {
        // ⚠️ ඔබේ Python Path එක (මේක වෙනස් කරන්න එපා ඔයාගේ මැෂින් එකේ හරි නම්)
        /*$pythonPath = "C:\\Users\\thili\\AppData\\Local\\Programs\\Python\\Python312\\python.exe";
        $scriptPath = base_path('scheduler.py');
        $command = "\"$pythonPath\" \"$scriptPath\"";*/

         $pythonPath = "python";

        $scriptPath = base_path('scheduler.py');
        $command = "\"$pythonPath\" \"$scriptPath\"";

        try {
            $process = Process::input(json_encode($inputData))
                        ->timeout(120)
                        ->run($command);

            if ($process->failed()) return back()->with('error', 'System Error: ' . $process->errorOutput());

            $output = $process->output();
            if (empty($output)) return back()->with('error', 'Python returned no data.');

            $result = json_decode($output, true);

            if (!$result || !isset($result['status'])) {
                return back()->with('error', 'Invalid Output: ' . $output);
            }

            if ($result['status'] === 'success') {
                if ($isGlobal) TimetableEntry::truncate();
                else TimetableEntry::where('section_id', $sectionId)->delete();

                $entries = [];
                $allocMap = [];
                foreach($inputData['allocations'] as $a) $allocMap[$a['id']] = $a;

                foreach ($result['data'] as $row) {
                    $allocData = $allocMap[$row['allocation_id']] ?? null;
                    if ($allocData) {
                        $entries[] = [
                            'section_id' => $allocData['section_id'],
                            'subject_id' => $allocData['subject_id'],
                            'teacher_id' => $allocData['teacher_id'],
                            'day_of_week' => $row['day'],
                            'period_number' => $row['period'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                if (!empty($entries)) TimetableEntry::insert($entries);

                $msg = $isGlobal ? 'Master Timetable Generated Successfully!' : 'Class Timetable Updated Successfully!';
                return back()->with('success', $msg);

            } else {
                // Error එකක් ආවොත්, Python වලින් එවන පැහැදිලි මැසේජ් එක පෙන්වනවා
                return back()->with('error', $result['message']);
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Controller Error: ' . $e->getMessage());
        }
    }

    /**
     * 3. පන්ති කාලසටහන පෙන්වීමේ පිටුව (Class View)
     */
    public function view(Request $request)
    {
        // Dropdown එකට අවශ්‍ය පන්ති සියල්ල ගන්නවා
        $sections = Section::with('classCategory')->orderBy('grade')->orderBy('class_name')->get();

        $timetable = [];
        $timings = [];
        $selectedSection = null;

        // User පන්තියක් තෝරාගෙන ඇත්නම් පමණක් Data ගන්නවා
        if ($request->has('section_id') && $request->section_id != "") {

            $selectedSection = Section::with('classCategory.periodTimings')->find($request->section_id);

            if($selectedSection) {
                // A. එම පන්තියේ Category එකට අදාල වෙලාවල් (Structure) ගන්නවා
                $timings = $selectedSection->classCategory->periodTimings;

                // B. Database එකෙන් කාලසටහන ගන්නවා
                $entries = TimetableEntry::where('section_id', $selectedSection->id)
                            ->with(['subject', 'teacher'])
                            ->get();

                // C. Data ටික Grid එකකට දාගන්නවා
                $timetable = [];
                foreach ($entries as $entry) {
                    // Array එකක් විදියට දත්ත දාන්න ඕන '[]' ලකුණ පාවිච්චි කරලා
                    $timetable[$entry->day_of_week][$entry->period_number][] = $entry;
                }
            }
        }

        return view('timetable.view', compact('sections', 'selectedSection', 'timetable', 'timings'));
    }

    public function downloadPdf(Request $request)
    {
        $request->validate(['section_id' => 'required|exists:sections,id']);

        $selectedSection = Section::with('classCategory.periodTimings')->findOrFail($request->section_id);
        $timings = $selectedSection->classCategory->periodTimings;

        // Data Query (View එකේ තිබ්බ Logic එකමයි)
        $entries = TimetableEntry::where('section_id', $selectedSection->id)
                    ->with(['subject', 'teacher'])
                    ->get();

        // C. Data ටික Grid එකකට දාගන්නවා
                $timetable = [];
                foreach ($entries as $entry) {
                    // Array එකක් විදියට දත්ත දාන්න ඕන '[]' ලකුණ පාවිච්චි කරලා
                    $timetable[$entry->day_of_week][$entry->period_number][] = $entry;
                }

        // Data Array එක
        $data = [
            'selectedSection' => $selectedSection,
            'timings' => $timings,
            'timetable' => $timetable
        ];

        // PDF Generate කිරීම (A4 Landscape)
        $pdf = Pdf::loadView('timetable.pdf_export', $data)
                ->setPaper('a4', 'landscape');

        // Download කිරීම (Filename එකට පන්තියේ නම දානවා)
        return $pdf->download('Timetable_'.$selectedSection->grade.'-'.$selectedSection->class_name.'.pdf');
    }

public function downloadTeacherPdf(Request $request)
{
    $teacherId = $request->input('teacher_id');
    $teacher = Teacher::findOrFail($teacherId);

    // 1. Data ලබා ගැනීම
    $entries = TimetableEntry::where('teacher_id', $teacher->id)
                ->with(['section', 'subject'])
                ->get();

    $periodTimings = PeriodTiming::all()->keyBy('period_number');
    $maxPeriod = $periodTimings->keys()->max() ?? 9;

    // 2. Array එක සකසා ගැනීම
    $timetable = [];
    foreach ($entries as $entry) {
        $timetable[$entry->day_of_week][$entry->period_number] = $entry;
    }

    // 3. PDF Generate කිරීම
    $pdf = Pdf::loadView('timetable.teacher_pdf_export', compact('teacher', 'timetable', 'periodTimings', 'maxPeriod'))
            ->setPaper('a4', 'portrait'); // ගුරුවරයෙක්ට Portrait ඇති

    return $pdf->download('Timetable_' . $teacher->short_code . '.pdf');
}

public function downloadMasterPdf()
{
    // 1. අවශ්‍ය දත්ත ලබා ගැනීම
    $teachers = Teacher::orderBy('name')->get();
    $entries = TimetableEntry::with(['section', 'subject'])->get();
    $periodTimings = \App\Models\PeriodTiming::all()->keyBy('period_number');

    // උපරිම Period ගණන සොයා ගැනීම
    $maxPeriod = $periodTimings->keys()->max() ?? 9;

    // 2. Master Table Array එක සැකසීම
    $masterTable = [];
    foreach ($entries as $entry) {
        $masterTable[$entry->teacher_id][$entry->day_of_week][$entry->period_number] = $entry;
    }

    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    // 3. PDF එක සකසා Download කිරීම (A4 Landscape)
    $pdf = Pdf::loadView('timetable.master_pdf_export', compact('teachers', 'masterTable', 'maxPeriod', 'periodTimings', 'days'))
            ->setPaper('a4', 'landscape'); // අවශ්‍ය නම් 'a3' ලෙස වෙනස් කළ හැක

    return $pdf->download('Master_Timetable.pdf');
}
}
