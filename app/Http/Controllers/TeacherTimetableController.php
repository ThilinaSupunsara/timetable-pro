<?php

namespace App\Http\Controllers;

use App\Models\ClassCategory;
use App\Models\PeriodTiming;
use App\Models\Teacher;
use App\Models\TimetableEntry;
use Illuminate\Http\Request;

class TeacherTimetableController extends Controller
{
    public function index(Request $request)
    {
        // 1. ගුරුවරුන්ගේ ලිස්ට් එක ගන්න
        $teachers = Teacher::orderBy('name')->get();

        $timetable = [];
        $selectedTeacher = null;
        $maxPeriod = 8;
        $periodTimings = []; // වෙලාවල් තියාගන්න Array එකක්

        // 2. වෙලාවල් (Times) ලබා ගැනීම (Reference සඳහා පළමු Category එක පාවිච්චි කරමු)
        // සාමාන්‍යයෙන් ලොකු පන්තිවල (Senior) වෙලාවල් තමයි ගුරුවරුන්ට වැදගත් වෙන්නේ.
        $defaultCategory = ClassCategory::first();
        if ($defaultCategory) {
            $periodTimings = PeriodTiming::where('class_category_id', $defaultCategory->id)
                             ->pluck('end_time', 'start_time');
                             // අපි සම්පූර්ණ Object එකම ගනිමු ලේසියට:
            $periodTimings = PeriodTiming::where('class_category_id', $defaultCategory->id)
                             ->get()
                             ->keyBy('period_number'); // Period Number එක Key එක විදියට ගන්නවා
        }

        // 3. ගුරුවරයෙක් තෝරාගෙන ඇත්නම් Data ගන්න
        if ($request->has('teacher_id') && $request->teacher_id != "") {

            $selectedTeacher = Teacher::find($request->teacher_id);

            if ($selectedTeacher) {
                $entries = TimetableEntry::where('teacher_id', $selectedTeacher->id)
                            ->with(['subject', 'section'])
                            ->get();

                foreach ($entries as $entry) {
                    $timetable[$entry->day_of_week][$entry->period_number] = $entry;

                    if ($entry->period_number > $maxPeriod) {
                        $maxPeriod = $entry->period_number;
                    }
                }
            }
        }

        return view('timetable.teacher', compact('teachers', 'selectedTeacher', 'timetable', 'maxPeriod', 'periodTimings'));
    }
}
