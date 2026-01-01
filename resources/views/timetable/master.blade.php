@extends('layouts.app')

@section('title', 'Master Timetable | Timetable System')

@section('content')
<div class="container-fluid px-4 pb-5">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 fade-in no-print">
        <div>
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-table text-primary me-2"></i> Master Timetable
            </h5>
            <span class="text-muted" style="font-size: 0.85rem;">Overview of all teacher schedules</span>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('timetable.download_master') }}" class="btn btn-sm btn-danger shadow-sm px-3">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> PDF
            </a>
            
        </div>
    </div>

    @php
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    @endphp

    <div class="card-modern shadow-sm">
        <div class="card-body p-0">

            <ul class="nav nav-tabs nav-tabs-custom no-print px-3 pt-3" id="timetableTabs" role="tablist">
                @foreach($days as $key => $day)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $key == 0 ? 'active' : '' }} fw-bold small text-uppercase"
                                id="tab-{{ $key }}"
                                data-bs-toggle="tab"
                                data-bs-target="#content-{{ $key }}"
                                type="button"
                                role="tab">
                            {{ $day }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content" id="timetableTabsContent">
                @foreach($days as $key => $day)
                    @php
                        $dayIndex = $key + 1;
                    @endphp

                    <div class="tab-pane fade {{ $key == 0 ? 'show active' : '' }}"
                         id="content-{{ $key }}"
                         role="tabpanel"
                         data-day-name="{{ $day }}">

                        <div class="table-container">
                            <table class="table table-bordered mb-0 table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="sticky-col bg-light text-dark text-center align-middle" style="width: 180px; z-index: 10;">
                                            Teacher
                                        </th>

                                        @for($p = 1; $p <= $maxPeriod; $p++)
                                            <th class="text-center bg-light text-dark align-middle" style="min-width: 100px;">
                                                <div class="small fw-bold text-uppercase text-muted" style="font-size: 0.7em;">Period</div>
                                                <div class="fw-bold" style="font-size: 1.1em;">{{ $p }}</div>
                                            </th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($teachers as $teacher)
                                    <tr>
                                        <td class="sticky-col fw-bold text-primary align-middle bg-white">
                                            <div class="d-flex align-items-center justify-content-between px-2">
                                                <span style="font-size: 0.9em;">{{ $teacher->name }}</span>
                                                <span class="badge bg-light text-secondary border" style="font-size: 0.65em;">{{ $teacher->short_code }}</span>
                                            </div>
                                        </td>

                                        @for($p = 1; $p <= $maxPeriod; $p++)

                                            @php
                                                $entry = $masterTable[$teacher->id][$dayIndex][$p] ?? null;
                                                $timeInfo = $periodTimings[$p] ?? null;
                                                $startTime = $timeInfo ? \Carbon\Carbon::parse($timeInfo->start_time)->format('h:i') : '';
                                                $endTime = $timeInfo ? \Carbon\Carbon::parse($timeInfo->end_time)->format('h:i') : '';
                                                $timeString = ($startTime && $endTime) ? "$startTime-$endTime" : '';
                                            @endphp

                                            <td class="tt-cell align-middle text-center p-1">
                                                @if($entry)
                                                    <div class="active-slot p-1 rounded-1 border" style="background-color: #f0fdf4; border-color: #bbf7d0 !important;">
                                                        <div class="text-success fw-bold" style="font-size: 0.85em; line-height: 1;">
                                                            {{ $entry->section->grade }}-{{ $entry->section->class_name }}
                                                        </div>

                                                        <div class="text-dark fw-bold my-1" style="font-size: 0.75em; line-height: 1.1;">
                                                            {{ $entry->subject->name }}
                                                        </div>

                                                        @if($timeString)
                                                            <div class="text-muted border-top pt-1 mt-1" style="font-size: 0.65em;">
                                                                {{ $timeString }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted opacity-25" style="font-size: 1.5em;">&middot;</span>
                                                @endif
                                            </td>
                                        @endfor
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                @endforeach
            </div>

        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Compact Table Styles */
    .table-container {
        max-height: 80vh;
        overflow: auto;
        position: relative;
        border-top: 1px solid #dee2e6;
    }

    .table thead th { position: sticky; top: 0; z-index: 5; }

    .sticky-col {
        position: sticky;
        left: 0;
        z-index: 6;
        border-right: 2px solid #e5e7eb !important;
        box-shadow: 2px 0 5px rgba(0,0,0,0.02);
    }

    .tt-cell { height: auto; padding: 2px !important; }

    /* Custom Tabs */
    .nav-tabs-custom .nav-link {
        color: #64748b;
        border: none;
        border-bottom: 2px solid transparent;
        margin-right: 5px;
    }
    .nav-tabs-custom .nav-link:hover { color: #334155; }
    .nav-tabs-custom .nav-link.active {
        color: #0f172a;
        background: transparent;
        border-bottom: 2px solid #6366f1; /* Indigo */
    }

    @media print {
        .no-print { display: none !important; }
        .table-container { max-height: none; overflow: visible; border: none; }
        .sticky-col { position: static; box-shadow: none; border-right: 1px solid #dee2e6; }
        body { font-size: 8pt; background: white !important; }
        .card-modern { border: none; shadow: none; }

        /* Show all tabs */
        .tab-content > .tab-pane {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            margin-bottom: 20px;
            page-break-after: always;
        }
        .nav-tabs { display: none !important; }

        /* Print Headers */
        .tab-pane::before {
            content: attr(data-day-name) " Schedule";
            display: block;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        .active-slot { border: 1px solid #ccc !important; background: #fff !important; }
        .table-bordered td, .table-bordered th { border-color: #000 !important; }
    }
</style>
@endpush
