<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('name')->get();
        return view('resources.subjects', compact('subjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'code' => 'required|unique:subjects,code'
        ]);

        Subject::create($request->all());


        return back()->with('success', 'Subject Added Successfully!');
    }

        public function destroy($id)
    {
        $subject = Subject::findOrFail($id);

        // අවශ්‍ය නම්: මේ Subject එක දැනට Timetable එකක පාවිච්චි වෙනවාද කියලා බලන්න පුළුවන්.
        // දැනට අපි කෙලින්ම Delete කරමු.

        try {
            $subject->delete();
            return back()->with('success', 'Subject deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Cannot delete this subject because it is assigned to teachers or timetables.');
        }
    }
}
