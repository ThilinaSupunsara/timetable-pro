@extends('layouts.app')

@section('title', 'Dashboard | Timetable System')

@push('styles')
<style>
    /* Dashboard Specific Styles */
    .fade-in { animation: fadeInUp 0.5s ease-out; }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Stat Cards */
    .stat-card {
        background: white; border: none; border-radius: 16px;
        padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        transition: 0.3s;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }

    .icon-box-soft {
        width: 50px; height: 50px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
    }
    .bg-indigo-soft { background: #e0e7ff; color: #4338ca; }
    .bg-emerald-soft { background: #d1fae5; color: #059669; }
    .bg-amber-soft { background: #fef3c7; color: #d97706; }

    /* Section Titles */
    .phase-title {
        font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em;
        color: var(--text-muted); font-weight: 700; margin: 2rem 0 1rem 0;
        display: flex; align-items: center;
    }
    .phase-title::after {
        content: ''; flex: 1; height: 1px; background: #e2e8f0; margin-left: 1rem;
    }

    /* Tool Cards */
    .tool-card {
        background: white; border: 1px solid #f1f5f9; border-radius: 16px;
        padding: 2rem 1.5rem; text-align: center; text-decoration: none;
        color: var(--text-dark); display: block; height: 100%; position: relative;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .tool-card:hover {
        border-color: var(--primary-color);
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        transform: translateY(-5px);
    }
    .tool-card h5 { font-weight: 700; margin-top: 1rem; font-size: 1.1rem; }
    .tool-card small { color: var(--text-muted); line-height: 1.4; display: block; margin-top: 0.5rem; }

    .tool-icon { font-size: 2.5rem; margin-bottom: 0.5rem; transition: 0.3s; }
    .tool-card:hover .tool-icon { transform: scale(1.1); }

    /* Action Card (Generate) */
    .card-generate {
        background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
        border: 1px dashed #16a34a;
    }
    .btn-generate {
        background: #16a34a; color: white; border: none; padding: 0.8rem 1.5rem;
        border-radius: 10px; font-weight: 600; width: 100%; transition: 0.2s;
    }
    .btn-generate:hover { background: #15803d; transform: scale(1.02); }
</style>
@endpush

@section('content')
<div class="container fade-in">

    <div class="row g-4 mt-2">
        <div class="col-md-4">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted mb-1 text-uppercase fw-bold" style="font-size: 0.75rem;">Total Teachers</h6>
                    <h2 class="fw-bold mb-0">{{ $counts['teachers'] }}</h2>
                </div>
                <div class="icon-box-soft bg-indigo-soft">
                    <i class="bi bi-person-badge"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted mb-1 text-uppercase fw-bold" style="font-size: 0.75rem;">Total Classes</h6>
                    <h2 class="fw-bold mb-0">{{ $counts['sections'] }}</h2>
                </div>
                <div class="icon-box-soft bg-emerald-soft">
                    <i class="bi bi-building"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted mb-1 text-uppercase fw-bold" style="font-size: 0.75rem;">Total Subjects</h6>
                    <h2 class="fw-bold mb-0">{{ $counts['subjects'] }}</h2>
                </div>
                <div class="icon-box-soft bg-amber-soft">
                    <i class="bi bi-book"></i>
                </div>
            </div>
        </div>
    </div>

    @if(session('warning_list'))
        <div class="alert alert-warning border-0 shadow-sm mt-4 rounded-4 d-flex" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning"></i>
            <div class="w-100">
                <h5 class="alert-heading fw-bold h6">Conflict Report Detected</h5>
                <p class="mb-2 small opacity-75">Some subjects could not be assigned automatically.</p>
                <div class="bg-white p-3 rounded-3 border" style="max-height: 150px; overflow-y: auto;">
                    <ul class="mb-0 text-danger small fw-semibold list-unstyled">
                        @foreach(session('warning_list') as $warning)
                            <li class="mb-1"><i class="bi bi-x-circle me-1"></i> {{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="phase-title">Phase 01: Setup & Data</div>
    <div class="row g-4">
        <div class="col-md-3">
            <a href="{{ route('settings.index') }}" class="tool-card">
                <div class="tool-icon text-secondary"><i class="bi bi-gear-wide-connected"></i></div>
                <h5>General Settings</h5>
                <small>Configure time slots & categories</small>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('subjects.index') }}" class="tool-card">
                <div class="tool-icon text-info"><i class="bi bi-journals"></i></div>
                <h5>Subjects</h5>
                <small>Manage school curriculum</small>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('teachers.index') }}" class="tool-card">
                <div class="tool-icon text-primary"><i class="bi bi-people"></i></div>
                <h5>Teachers</h5>
                <small>Staff details & competencies</small>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('sections.index') }}" class="tool-card">
                <div class="tool-icon text-success"><i class="bi bi-grid-3x3-gap"></i></div>
                <h5>Classes</h5>
                <small>Sections & Grades setup</small>
            </a>
        </div>
    </div>

    <div class="phase-title">Phase 02: Operations</div>
    <div class="row g-4">
        <div class="col-md-8">
            <a href="{{ route('allocations.index') }}" class="tool-card d-flex align-items-center text-start p-4">
                <div class="tool-icon text-warning me-4 mb-0"><i class="bi bi-clipboard-data"></i></div>
                <div>
                    <h5 class="mt-0">Workload Allocation</h5>
                    <small>Assign subjects to classes, set periods, and manage constraints.</small>
                </div>
                <div class="ms-auto"><i class="bi bi-chevron-right text-muted"></i></div>
            </a>
        </div>

        <div class="col-md-4">
            <div class="tool-card card-generate p-4">
                <div class="d-flex align-items-center justify-content-center mb-3 text-success">
                    <i class="bi bi-cpu fs-1 me-2"></i>
                    <h5 class="m-0 text-success">Auto-Generator</h5>
                </div>
                <form action="{{ route('timetable.generate_all') }}" method="POST" id="generateMasterForm">
                    @csrf
                    <button type="button" class="btn-generate shadow-sm" onclick="confirmGenerate(event)">
                        <i class="bi bi-play-fill me-1"></i> Run Algorithm
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="phase-title">Phase 03: Output & Reports</div>
    <div class="row g-4">
        <div class="col-md-4">
            <a href="{{ route('timetable.view') }}" class="tool-card">
                <div class="tool-icon text-danger"><i class="bi bi-calendar-week"></i></div>
                <h5>Class Timetables</h5>
                <small>View per class</small>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('timetable.teacher') }}" class="tool-card">
                <div class="tool-icon text-dark"><i class="bi bi-person-workspace"></i></div>
                <h5>Teacher Timetables</h5>
                <small>View per teacher</small>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('timetable.master') }}" class="tool-card">
                <div class="tool-icon text-primary"><i class="bi bi-table"></i></div>
                <h5>Master Overview</h5>
                <small>Full school schedule grid</small>
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Dashboard Generate Confirmation Logic
    function confirmGenerate(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Generate Master Timetable?',
            text: "This will overwrite all existing schedules! Ensure all configurations are correct.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Start Generation',
            background: '#fff',
            customClass: { popup: 'rounded-4' }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Running Algorithm...',
                    html: 'Optimizing slots and allocations.',
                    timerProgressBar: true,
                    didOpen: () => { Swal.showLoading(); }
                });
                document.getElementById('generateMasterForm').submit();
            }
        });
    }
</script>
@endpush
