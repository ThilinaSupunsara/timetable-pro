@extends('layouts.app')

@section('title', 'Class Timetable | Timetable System')

@section('content')
<div class="container pb-5">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 fade-in no-print">
        <div>
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-calendar-week text-primary me-2"></i> Class Timetable
            </h5>
            <span class="text-muted" style="font-size: 0.85rem;">View and print weekly schedules</span>
        </div>

        <div class="d-flex gap-2">
            @if($selectedSection)
                <a href="{{ route('timetable.download_pdf', ['section_id' => $selectedSection->id]) }}" class="btn btn-sm btn-danger shadow-sm px-3">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> PDF
                </a>

                <form action="{{ route('timetable.generate') }}" method="POST" onsubmit="return confirm('Regenerate?');">
                    @csrf
                    <input type="hidden" name="section_id" value="{{ $selectedSection->id }}">
                    <button type="submit" class="btn btn-sm btn-warning text-dark shadow-sm fw-bold">
                        <i class="bi bi-arrow-repeat me-1"></i> Regenerate
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="card-modern mb-4 no-print fade-in">
        <div class="card-body p-3">
            <form action="{{ route('timetable.view') }}" method="GET" class="row align-items-center g-2">
                <div class="col-auto">
                    <label class="fw-bold text-muted small text-uppercase">Select Class:</label>
                </div>
                <div class="col-md-3">
                    <select name="section_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Choose Class --</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->id }}" {{ request('section_id') == $sec->id ? 'selected' : '' }}>
                                {{ $sec->grade }} - {{ $sec->class_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($selectedSection)
    <div class="card-modern fade-in shadow-sm">

        <div class="bg-light border-bottom p-3 text-center">
            <h5 class="fw-bold mb-0 text-dark">
                {{ $selectedSection->grade }} - {{ $selectedSection->class_name }}
                <span class="badge bg-white text-secondary border ms-2" style="font-size: 0.7rem; vertical-align: middle;">
                    {{ $selectedSection->classCategory->name }}
                </span>
            </h5>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="text-center bg-light">
                    <tr>
                        <th style="width: 100px;" class="text-uppercase text-muted small py-2">Time</th>
                        <th class="text-uppercase text-dark py-2" style="width: 18%">Monday</th>
                        <th class="text-uppercase text-dark py-2" style="width: 18%">Tuesday</th>
                        <th class="text-uppercase text-dark py-2" style="width: 18%">Wednesday</th>
                        <th class="text-uppercase text-dark py-2" style="width: 18%">Thursday</th>
                        <th class="text-uppercase text-dark py-2" style="width: 18%">Friday</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($timings as $slot)
                    <tr>
                        <td class="bg-light text-center align-middle border-end p-1">
                            <div class="fw-bold text-dark small">
                                {{ \Carbon\Carbon::parse($slot->start_time)->format('h:i') }}
                                <span class="text-muted" style="font-size: 0.7em;">{{ \Carbon\Carbon::parse($slot->start_time)->format('A') }}</span>
                            </div>
                            <div class="text-muted" style="font-size: 0.7em; line-height: 1;">to</div>
                            <div class="fw-bold text-dark small">
                                {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i') }}
                                <span class="text-muted" style="font-size: 0.7em;">{{ \Carbon\Carbon::parse($slot->end_time)->format('A') }}</span>
                            </div>

                            <div class="badge bg-light text-secondary border mt-1" style="font-size: 0.65em;">
                                {{ $slot->label }}
                            </div>
                        </td>

                        @if($slot->is_break)
                            <td colspan="5" class="break-cell text-center align-middle p-0">
                                <div class="py-2" style="background-color: #fef9c3; color: #854d0e; font-size: 0.8rem; font-weight: 600; letter-spacing: 1px;">
                                    {{ strtoupper($slot->label) }}
                                </div>
                            </td>
                        @else
                            @for($day=1; $day<=5; $day++)
                                @php $entry = $timetable[$day][$slot->period_number] ?? null; @endphp

                                <td class="tt-cell align-middle text-center p-1">
                                    @if($entry)
                                        <div class="slot-content p-1 rounded-2">
                                            <div class="text-primary fw-bold" style="font-size: 0.85rem;">
                                                {{ $entry->subject->name }}
                                            </div>
                                            @if($entry->teacher)
                                                <div class="text-muted d-flex align-items-center justify-content-center gap-1" style="font-size: 0.7rem;">
                                                    {{ $entry->teacher->short_code }}
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted opacity-25">&middot;</span>
                                    @endif
                                </td>
                            @endfor
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @elseif(request('section_id'))
        <div class="text-center py-5">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
            <p class="mt-2 text-muted small">Loading...</p>
        </div>
    @else
        <div class="text-center py-5 fade-in">
            <i class="bi bi-calendar4-week text-muted opacity-25" style="font-size: 3rem;"></i>
            <h6 class="text-muted mt-3">Select a Class</h6>
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    /* Compact Table Styles */
    .table-bordered td, .table-bordered th { border-color: #e2e8f0; }

    .tt-cell { height: auto; min-height: 60px; } /* උස අඩු කළා */

    .slot-content { transition: all 0.2s; }
    .slot-content:hover {
        background-color: #f1f5f9;
        transform: scale(1.02);
    }

    @media print {
        .no-print { display: none !important; }
        .card-modern { box-shadow: none !important; border: 1px solid #000 !important; }
        body { background-color: white !important; font-size: 10pt; }
        .tt-cell { border: 1px solid #000 !important; }
        .break-cell div { background-color: #eee !important; -webkit-print-color-adjust: exact; border-top: 1px solid #000; border-bottom: 1px solid #000; }
    }
</style>
@endpush
