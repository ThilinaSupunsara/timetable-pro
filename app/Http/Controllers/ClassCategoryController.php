<?php

namespace App\Http\Controllers;

use App\Models\ClassCategory;
use App\Models\PeriodTiming;
use Illuminate\Http\Request;

class ClassCategoryController extends Controller
{
    // 1. Settings පිටුව පෙන්නන්න
    public function index()
    {
        $categories = ClassCategory::all();
        return view('settings.index', compact('categories'));
    }

    // 2. අලුත් Category එකක් හදන්න (Ex: Primary)
    // 1. අලුත් Category එකක් හදනකොට දැන් හිස්වට හදන්නේ. (Auto 8ක් එපා)
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|unique:class_categories']);

        $category = ClassCategory::create([
            'name' => $request->name,
            'description' => $request->description
        ]);

        // දැන් අපි මෙතන පීරියඩ් 8ක් හදන්නේ නෑ. User ට කැමති ගානක් Add කරගන්න දෙනවා.

        return redirect()->route('settings.manage', $category->id)
                        ->with('success', 'Category Created! Now add your time slots.');
    }

    // 3. වෙලාවල් වෙනස් කරන පිටුවට යන්න (Manage Timings)
    public function manage($id)
    {
        $category = ClassCategory::with('periodTimings')->findOrFail($id);
        return view('settings.manage', compact('category'));
    }

    // 4. වෙලාවල් Save කරන්න
   // 2. වෙලාවල් Update කරන කොටස (Dynamic Slots Save කිරීම)
    public function updateTimings(Request $request, $id)
    {
        $category = ClassCategory::findOrFail($id);

        // පරණ වෙලාවල් ඔක්කොම මකලා, අලුත් ලිස්ට් එක මුල ඉඳන් Save කරනවා.
        // (මේක තමයි ලේසිම සහ නිවැරදිම ක්‍රමය Dynamic Form එකක් හන්දා)
        $category->periodTimings()->delete();

        if($request->has('slots')){
            foreach ($request->slots as $index => $slot) {
                PeriodTiming::create([
                    'class_category_id' => $category->id,
                    'period_number' => $index + 1, // 1 ඉඳන් පිළිවෙලට Number කරනවා
                    'label' => $slot['label'],     // User දෙන නම (Assembly, Period 1...)
                    'start_time' => $slot['start'],
                    'end_time' => $slot['end'],
                    'is_break' => isset($slot['is_break']) ? true : false,
                ]);
            }
        }

        return back()->with('success', 'Time Structure Updated Successfully!');
    }

    public function destroy($id)
{
    $category = ClassCategory::findOrFail($id);

    // Check if any sections are using this category
    if ($category->sections()->count() > 0) {
        return back()->with('error', 'Cannot delete this category because it is assigned to active Classes. Please reassign or delete those classes first.');
    }

    try {
        // Delete related timings first (if cascading not set in DB)
        $category->periodTimings()->delete();

        $category->delete();
        return back()->with('success', 'Category deleted successfully!');
    } catch (\Exception $e) {
        return back()->with('error', 'Something went wrong.');
    }
}
}
