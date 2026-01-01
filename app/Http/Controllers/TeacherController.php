<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index()
    {
        // ගුරුවරුන්ගේ විෂයන් එක්කම Data ගන්න (with subjects)
        $teachers = Teacher::with('subjects')->orderBy('name')->get();

        // Form එකේ තෝරන්න ඔක්කොම විෂයන් යවන්න ඕන
        $subjects = Subject::orderBy('name')->get();

        return view('resources.teachers', compact('teachers', 'subjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'short_code' => 'required|unique:teachers,short_code',
            'subjects' => 'array' // Subjects ලිස්ට් එකක් එන්න ඕන
        ]);

        $teacher = Teacher::create([
            'name' => $request->name,
            'short_code' => $request->short_code
        ]);

        // ගුරුවරයාට අදාල විෂයන් ටික Link කරනවා
        if($request->has('subjects')) {
            $teacher->subjects()->attach($request->subjects);
        }

        return back()->with('success', 'Teacher Added with Subjects!');
    }

    public function destroy($id)
{
    $teacher = Teacher::findOrFail($id);

    try {
        // Teacher Subject සම්බන්ධතා ස්වයංක්‍රීයව මැකෙයි (Pivot Table එකේ Cascade දාලා නම්).
        // නැත්නම්: $teacher->subjects()->detach();

        $teacher->delete();
        return back()->with('success', 'Teacher removed successfully!');
    } catch (\Exception $e) {
        return back()->with('error', 'Cannot delete this teacher. They might be assigned to a timetable.');
    }
}
}
