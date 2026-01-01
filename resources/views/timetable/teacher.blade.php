@extends('layouts.app')

@section('title', 'Teacher Timetable | Timetable System')

@section('content')
<div class="container pb-5">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 fade-in no-print">
        <div>
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-person-workspace text-primary me-2"></i> Teacher Timetable
            </h5>
            <span class="text-muted" style="font-size: 0.85rem;">View individual staff schedules</span>
        </div>

        <div class="d-flex gap-2">
            @if(isset($selectedTeacher))
                <a href="{{ route('timetable.download_teacher_pdf', ['teacher_id' => $selectedTeacher->id]) }}" class="btn btn-sm btn-danger shadow-sm px-3">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> PDF
                </a>
            @endif
        </div>
    </div>

    <div class="card-modern mb-4 no-print fade-in">
        <div class="card-body p-3">
            <form action="{{ route('timetable.teacher') }}" method="GET" class="row align-items-center g-2">
                <div class="col-auto">
                    <label class="fw-bold text-muted small text-uppercase">Select Teacher:</label>
                </div>
                <div class="col-md-4">
                    <select name="teacher_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Choose Staff --</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->name }} ({{ $t->short_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($selectedTeacher)
    <div class="card-modern fade-in shadow-sm">

        <div class="bg-light border-bottom p-3 text-center">
            <h5 class="fw-bold mb-0 text-dark">
                {{ $selectedTeacher->name }}
                <span class="badge bg-white text-primary border ms-2" style="font-size: 0.7rem; vertical-align: middle;">
                    {{ $selectedTeacher->short_code }}
                </span>
            </h5>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="text-center bg-light">
                    <tr>
                        <th style="width: 60px;" class="text-uppercase text-muted small py-2">#</th>
                        <th class="text-uppercase text-dark py-2" style="width: 19%">Monday</th>
                        <th class="text-uppercase text-dark py-2" style="width: 19%">Tuesday</th>
                        <th class="text-uppercase text-dark py-2" style="width: 19%">Wednesday</th>
                        <th class="text-uppercase text-dark py-2" style="width: 19%">Thursday</th>
                        <th class="text-uppercase text-dark py-2" style="width: 19%">Friday</th>
                    </tr>
                </thead>
                <tbody>
                    @for($p = 1; $p <= $maxPeriod; $p++)

                        @php
                            // --- Row Hiding Logic ---
                            $hasClass = false;
                            for($day = 1; $day <= 5; $day++) {
                                if(isset($timetable[$day][$p])) {
                                    $hasClass = true;
                                    break;
                                }
                            }

                            // Time Info
                            $timeInfo = $periodTimings[$p] ?? null;
                            $startTime = $timeInfo ? \Carbon\Carbon::parse($timeInfo->start_time)->format('h:i') : '';
                            $endTime = $timeInfo ? \Carbon\Carbon::parse($timeInfo->end_time)->format('h:i') : '';
                            $timeString = ($startTime && $endTime) ? "$startTime-$endTime" : '';
                        @endphp

                        @if($hasClass)
                        <tr>
                            <td class="bg-light text-center align-middle fw-bold text-muted border-end p-1">
                                <div style="font-size: 1rem;">{{ $p }}</div>
                                @if($timeString)
                                    <div class="text-muted small" style="font-size: 0.65em;">{{ $startTime }}<br>{{ $endTime }}</div>
                                @endif
                            </td>

                            @for($d = 1; $d <= 5; $d++)
                                @php $entry = $timetable[$d][$p] ?? null; @endphp

                                <td class="tt-cell align-middle text-center p-1">
                                    @if($entry)
                                        <div class="slot-content p-1 rounded-2 border" style="background-color: #f0fdf4; border-color: #dcfce7 !important;">
                                            <div class="text-success fw-bold" style="font-size: 0.85rem;">
                                                {{ $entry->section->grade }}-{{ $entry->section->class_name }}
                                            </div>

                                            <div class="text-dark small" style="font-size: 0.75rem; line-height: 1.2;">
                                                {{ $entry->subject->name }}
                                            </div>
                                        </div>
                                    @else
                                        @endif
                                </td>
                            @endfor
                        </tr>
                        @endif

                    @endfor
                </tbody>
            </table>
        </div>
    </div>
    @elseif(request('teacher_id'))
        <div class="text-center py-5">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
            <p class="mt-2 text-muted small">Loading...</p>
        </div>
    @else
        <div class="text-center py-5 fade-in">
            <i class="bi bi-person-workspace text-muted opacity-25" style="font-size: 3rem;"></i>
            <h6 class="text-muted mt-3">Select a Teacher</h6>
        </div>
    @endif

</div>
@endsection

@push('styles')
<style>
    /* Compact Styles */
    .table-bordered td, .table-bordered th { border-color: #e2e8f0; }

    .tt-cell { height: auto; min-height: 55px; } /* උස අඩු කළා */

    .slot-content { transition: all 0.2s; }
    .slot-content:hover {
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transform: scale(1.02);
    }

    @media print {
        .no-print { display: none !important; }
        .card-modern { box-shadow: none !important; border: none !important; }
        body { background-color: white !important; font-size: 10pt; }
        .tt-cell { border: 1px solid #000 !important; }
        .slot-content { border: 1px solid #ddd !important; background-color: white !important; }
    }
</style>
@endpush
