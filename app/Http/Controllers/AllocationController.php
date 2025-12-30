<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Allocation;
use App\Models\ClassCategory;

class AllocationController extends Controller
{
    /**
     * පන්තිය තෝරාගැනීමේ පිටුව
     */
    public function index()
    {
        $categories = ClassCategory::with('sections')->get();
        return view('allocations.index', compact('categories'));
    }

    /**
     * Workload සකසන පිටුව
     */
    public function manage($section_id)
    {
        // 1. Section එක ගන්නකොට Timings (Settings Data) එක්කම ගන්න
        $section = Section::with('classCategory.periodTimings')->findOrFail($section_id);

        $subjects = Subject::orderBy('name')->get();

        $teachers = Teacher::with('subjects:id')->get()->map(function($teacher) {
            return [
                'id' => $teacher->id,
                'name' => $teacher->name . ' (' . $teacher->short_code . ')',
                'subject_ids' => $teacher->subjects->pluck('id')->toArray()
            ];
        });

        $existingAllocations = Allocation::where('section_id', $section_id)->get();

        return view('allocations.manage', compact('section', 'subjects', 'teachers', 'existingAllocations'));
    }

    /**
     * දත්ත යාවත්කාලීන කිරීම (Update Logic with ALL Validations)
     */
   public function update(Request $request, $section_id)
    {
        $section = Section::findOrFail($section_id);
        $maxTeacherLoad = 40; // ගුරුවරයෙකුට සතියට දිය හැකි උපරිම පීරියඩ් ගණන

        // --- VALIDATION PHASE (දත්ත පරීක්ෂා කිරීම) ---
        if ($request->has('allocations')) {
            foreach ($request->allocations as $index => $data) {

                // Subject එකක් තෝරා ඇත්නම් සහ Periods 0 ට වැඩි නම් පමණක් බලන්න
                if (!empty($data['subject_id']) && isset($data['periods']) && $data['periods'] > 0) {

                    $periods = (int)$data['periods'];
                    $consecutive = (int)$data['consecutive']; // 1=Single, 2=Double...

                    // ERROR 1: පීරියඩ් ගණන Block එකට වඩා අඩු නම්
                    if ($periods < $consecutive) {
                        return back()->with('error', "Validation Error (Row " . ($index + 1) . "): Periods ({$periods}) cannot be less than Type '{$consecutive}'.");
                    }

                    // ERROR 2: පීරියඩ් ගණන Type එකෙන් බෙදෙන්නේ නැත්නම් (Modulo Check)
                    if ($periods % $consecutive !== 0) {
                        return back()->with('error', "Validation Error (Row " . ($index + 1) . "): You selected Type '{$consecutive}'. Periods ({$periods}) must be a multiple of {$consecutive} (Ex: {$consecutive}, " . ($consecutive * 2) . ").");
                    }

                    // ERROR 3: Fixed Slot Logic & Range Check
                    if (isset($data['is_fixed'])) {
                        if (empty($data['fixed_day']) || empty($data['fixed_period'])) {
                            return back()->with('error', "Validation Error (Row " . ($index + 1) . "): You checked 'Fix?' but did not select Day or Slot.");
                        }

                        // Range Check: Block එක දවසෙන් එලියට පනිනවද?
                        $startSlot = (int)$data['fixed_period'];
                        $endSlot = $startSlot + $consecutive - 1;

                        // උපරිම Slot 12 ලෙස සලකමු (නැත්නම් Settings වලින් ගන්න පුළුවන්)
                        if ($endSlot > 12) {
                             return back()->with('error', "Validation Error (Row " . ($index + 1) . "): Fixed Slot Error. Type '{$consecutive}' starting at {$startSlot} ends at {$endSlot}, which is out of range.");
                        }
                    }

                    // ERROR 4: Teacher Overload Check (ගුරුවරයාගේ සීමාව පරීක්ෂා කිරීම)
                    if (!empty($data['teacher_id'])) {
                        // 1. මේ ගුරුවරයාට අනිත් පන්ති වලින් දැනටමත් දීලා තියෙන වැඩ ප්‍රමාණය ගන්න
                        $currentLoad = Allocation::where('teacher_id', $data['teacher_id'])
                                        ->where('section_id', '!=', $section_id) // මේ පන්තිය අතහරින්න (Update නිසා)
                                        ->sum('periods_per_week');

                        // 2. අලුත් ගණන එකතු කරලා බලන්න
                        $newTotal = $currentLoad + $periods;

                        if ($newTotal > $maxTeacherLoad) {
                            $tName = Teacher::find($data['teacher_id'])->name;
                            return back()->with('error', "Teacher Overload (Row " . ($index + 1) . "): {$tName} already has {$currentLoad} periods. Adding {$periods} will exceed the limit of {$maxTeacherLoad}.");
                        }
                    }
                }
            }
        }

        // --- SAVING PHASE (තැන්පත් කිරීම) ---

        // 1. පරණ දත්ත සියල්ල මකන්න (Fresh Update for this class)
        Allocation::where('section_id', $section_id)->delete();

        // 2. අලුත් දත්ත ඇතුලත් කරන්න
        if ($request->has('allocations')) {
            foreach ($request->allocations as $data) {

                if (!empty($data['subject_id']) && isset($data['periods']) && $data['periods'] > 0) {

                    Allocation::create([
                        'section_id' => $section->id,
                        'subject_id' => $data['subject_id'],
                        'teacher_id' => $data['teacher_id'] ?? null,
                        'periods_per_week' => $data['periods'],
                        'consecutive_periods' => $data['consecutive'],

                        'is_fixed_slot' => isset($data['is_fixed']),
                        'fixed_day' => isset($data['is_fixed']) ? $data['fixed_day'] : null,
                        'fixed_period' => isset($data['is_fixed']) ? $data['fixed_period'] : null,
                    ]);
                }
            }
        }

        return back()->with('success', 'Workload Updated Successfully!');
    }
}
