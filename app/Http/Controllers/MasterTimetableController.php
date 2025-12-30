<?php

namespace App\Http\Controllers;

use App\Models\PeriodTiming;
use App\Models\Teacher;
use App\Models\TimetableEntry;
use Illuminate\Http\Request;

class MasterTimetableController extends Controller
{
public function index()
{
    // ගුරුවරුන් සහ කාලසටහන් දත්ත ගැනීම
    $teachers = Teacher::orderBy('name')->get();
    $entries = TimetableEntry::with(['section', 'subject'])->get();

    // Period Timings සියල්ල ලබා ගැනීම
    $periodTimings = PeriodTiming::all()->keyBy('period_number');

    // --- වෙනස් කළ කොටස ---
    // දෘඩ අගයක් (8) වෙනුවට, PeriodTiming Table එකේ තියෙන ලොකුම අංකය ගන්නවා.
    // Timings මුකුත් නැත්නම් Default 9 ගන්නවා.
    $maxPeriod = $periodTimings->keys()->max() ?? 9;

    // Master Table Array එක සකසා ගැනීම
    $masterTable = [];
    foreach ($entries as $entry) {
        $masterTable[$entry->teacher_id][$entry->day_of_week][$entry->period_number] = $entry;

        // Loop එක ඇතුලේ maxPeriod check කරන කෑල්ල අයින් කළා,
        // මොකද අපි දැන් කෙලින්ම DB එකෙන් Max එක ගන්න නිසා.
    }

    return view('timetable.master', compact('teachers', 'masterTable', 'maxPeriod', 'periodTimings'));
}
}
