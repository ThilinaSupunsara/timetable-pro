<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableEntry;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // System Summary Counts
        $counts = [
            'teachers' => Teacher::count(),
            'sections' => Section::count(),
            'subjects' => Subject::count(),
        ];

        return view('dashboard', compact('counts'));
    }
}
