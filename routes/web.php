<?php

use App\Http\Controllers\AllocationController;
use App\Http\Controllers\ClassCategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterTimetableController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherTimetableController;
use App\Http\Controllers\TimetableController;
use Illuminate\Support\Facades\Route;



// Settings Routes
Route::get('/settings', [ClassCategoryController::class, 'index'])->name('settings.index');
Route::post('/settings', [ClassCategoryController::class, 'store'])->name('settings.store');
Route::get('/settings/{id}/manage', [ClassCategoryController::class, 'manage'])->name('settings.manage');
Route::post('/settings/{id}/update', [ClassCategoryController::class, 'updateTimings'])->name('settings.update');

// Resources Routes
Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');

Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
Route::post('/teachers', [TeacherController::class, 'store'])->name('teachers.store');

Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');
Route::post('/sections', [SectionController::class, 'store'])->name('sections.store');

// Workload / Allocations Routes
Route::get('/allocations', [AllocationController::class, 'index'])->name('allocations.index');
Route::get('/allocations/{id}/manage', [AllocationController::class, 'manage'])->name('allocations.manage');
Route::post('/allocations/{id}/update', [AllocationController::class, 'update'])->name('allocations.update');


// ... අනිත් routes වලට පහලින් ...

Route::get('/timetable', [TimetableController::class, 'view'])->name('timetable.view');
Route::post('/timetable/generate', [TimetableController::class, 'generate'])->name('timetable.generate');

Route::get('/teacher-timetable', [TeacherTimetableController::class, 'index'])->name('timetable.teacher');

// Dashboard (Tool Board)
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Master View
Route::get('/master-timetable', [MasterTimetableController::class, 'index'])->name('timetable.master');

Route::post('/timetable/generate-all', [TimetableController::class, 'generateAll'])->name('timetable.generate_all');

Route::get('/timetable/download-pdf', [TimetableController::class, 'downloadPdf'])->name('timetable.download_pdf');

Route::get('/timetable/download-teacher-pdf', [TimetableController::class, 'downloadTeacherPdf'])->name('timetable.download_teacher_pdf');

Route::get('/timetable/master/download', [TimetableController::class, 'downloadMasterPdf'])->name('timetable.download_master');
