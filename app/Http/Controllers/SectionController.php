<?php

namespace App\Http\Controllers;

use App\Models\ClassCategory;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
   /**
     * පන්ති ලැයිස්තුව සහ Form එක පෙන්වන Function එක
     */
    public function index()
    {
        // Category එකත් එක්කම Sections ගන්නවා (Eager Loading)
        $sections = Section::with('classCategory')
                    ->orderBy('grade')
                    ->orderBy('class_name')
                    ->get();

        // Dropdown එකට Categories යවන්න ඕන
        $categories = ClassCategory::all();

        return view('resources.sections', compact('sections', 'categories'));
    }

    /**
     * පන්තියක් Save කරන Function එක (Duplicate Check සහිතයි)
     */
    public function store(Request $request)
    {
        $request->validate([
            'class_category_id' => 'required|exists:class_categories,id',
            'grade' => 'required|integer|min:1|max:13',

            // DUPLICATE CHECK: එකම Grade එකේ, එකම නම තියෙන පන්ති තියෙන්න බෑ
            // උදා: 6 - A තියෙද්දි ආයේ 6 - A හදන්න බෑ
            'class_name' => [
                'required',
                'string',
                'max:10',
                Rule::unique('sections')->where(function ($query) use ($request) {
                    return $query->where('grade', $request->grade);
                }),
            ]
        ], [
            // Custom Error Message
            'class_name.unique' => "The class 'Grade {$request->grade} - {$request->class_name}' already exists!"
        ]);

        Section::create($request->all());

        return back()->with('success', 'Class Section Created Successfully!');
    }

    public function destroy($id)
{
    $section = Section::findOrFail($id);

    try {
        // මේ පන්තියට අදාල Allocations සහ Timetable Entries මැකීමට
        // ඔබේ Database එකේ Cascade Delete දාලා නැත්නම්, පහත කේතය භාවිතා කරන්න:
        // $section->allocations()->delete();
        // $section->timetableEntries()->delete();

        $section->delete();
        return back()->with('success', 'Class deleted successfully!');
    } catch (\Exception $e) {
        return back()->with('error', 'Cannot delete this class. It might have linked data.');
    }
}
}
