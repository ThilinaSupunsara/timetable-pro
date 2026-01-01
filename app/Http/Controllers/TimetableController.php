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

class TimetableController extends Controller
{
    /**
     * 1. තනි පන්තියක් සඳහා කාලසටහන සැකසීම (Single Class Generation)
     */
    public function generate(Request $request, TimetableGenerator $generator)
    {
        $request->validate(['section_id' => 'required|exists:sections,id']);

        $section = Section::with('classCategory.periodTimings')->findOrFail($request->section_id);

        // --- VALIDATION 1: CAPACITY CHECK (ඉඩ ප්‍රමාණය පරීක්ෂා කිරීම) ---

        // A. සතියට තියෙන මුළු ඉඩ (Slots) ගණන (Intervals හැර)
        $slotsPerDay = $section->classCategory->periodTimings->where('is_break', false)->count();
        $totalAvailableSlots = $slotsPerDay * 5; // දවස් 5 ට

        if ($totalAvailableSlots == 0) {
            return back()->with('error', 'Settings Error: No teaching slots found in Settings for this class category.');
        }

        // B. මෙම පන්තියට Assign කර ඇති මුළු පීරියඩ් ගණන
        $totalAssignedPeriods = Allocation::where('section_id', $section->id)->sum('periods_per_week');

        // C. ඉඩ මදි නම් Error එකක්!
        if ($totalAssignedPeriods > $totalAvailableSlots) {
            $diff = $totalAssignedPeriods - $totalAvailableSlots;
            return back()->with('error', "Capacity Error: You have assigned {$totalAssignedPeriods} periods, but the week only has space for {$totalAvailableSlots}. Please remove {$diff} periods from Allocations.");
        }

        // --- GENERATION PROCESS ---

        try {
            // Service එක හරහා generate කරනවා (Returns unassigned subjects list)
            $unassignedList = $generator->generate($request->section_id);

            // --- VALIDATION 2: COMPLETION CHECK ---
            if (!empty($unassignedList)) {
                $msg = "Timetable generated with warnings! The following subjects could not be fully assigned (likely due to teacher clashes): " . implode(', ', $unassignedList);
                return back()->with('error', $msg);
            }

            return back()->with('success', 'Timetable Generated Successfully! All subjects assigned.');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * 2. මුළු පාසලම එකවර Generate කිරීම (Global Generation)
     * මෙය Dashboard එකෙන් Call කරනු ලැබේ.
     */
    public function generateAll(TimetableGenerator $generator)
    {
        try {
            // මුළු පාසලම Generate කරන Service Function එක Call කිරීම
            $unassignedList = $generator->generateAll();

            if (!empty($unassignedList)) {
                // දාගන්න බැරි වුන ලිස්ට් එක Session එකට දානවා (Dashboard එකේ Report එකක් විදියට පෙන්නන්න)
                return redirect()->route('dashboard')
                        ->with('warning_list', $unassignedList)
                        ->with('error', 'Timetable Generated with Conflicts! See the report below.');
            }

            return redirect()->route('dashboard')->with('success', 'Master Timetable Generated Successfully for ALL Classes!');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
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
                foreach ($entries as $entry) {
                    $timetable[$entry->day_of_week][$entry->period_number] = $entry;
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

        $timetable = [];
        foreach ($entries as $entry) {
            $timetable[$entry->day_of_week][$entry->period_number] = $entry;
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
