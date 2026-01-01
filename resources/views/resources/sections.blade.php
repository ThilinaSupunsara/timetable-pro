@extends('layouts.app')

@section('title', 'Classes | Timetable System')

@section('content')
<div class="container pb-5">

    <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Manage Classes</h4>
            <span class="text-muted small">Create class sections (e.g. 6-A, 10-B) and assign time categories</span>
        </div>
        <a href="{{ route('allocations.index') }}" class="btn btn-light text-muted border px-4" style="border-radius: 10px;">
            Next: Workload <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="row g-4">

        <div class="col-md-4">
            <div class="card-modern h-100 fade-in">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold"><i class="bi bi-grid-fill text-primary me-2"></i> Add New Class</span>
                </div>
                <div class="p-4">

                    @if ($errors->any())
                        <div class="alert alert-danger border-0 bg-danger-subtle text-danger small rounded-3 mb-3">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('sections.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label small text-uppercase text-muted">Time Category</label>
                            <select name="class_category_id" class="form-select" required>
                                <option value="" selected disabled>Select Category...</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text small"><i class="bi bi-clock me-1"></i> Determines start/end times</div>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label small text-uppercase text-muted">Grade</label>
                                <select name="grade" class="form-select" required>
                                    @for($i=1; $i<=13; $i++)
                                        <option value="{{ $i }}">Gr {{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label small text-uppercase text-muted">Section</label>
                                <input name="class_name" class="form-control text-center fw-bold text-uppercase" placeholder="A" required maxlength="5">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 shadow-sm py-2 mt-2">
                            <i class="bi bi-plus-lg me-1"></i> Create Class
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card-modern h-100 fade-in">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold"><i class="bi bi-building text-success me-2"></i> Active Classes</span>
                    <span class="badge bg-light text-dark border">{{ $sections->count() }} Sections</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 text-muted small text-uppercase py-3">Class Name</th>
                                <th class="text-muted small text-uppercase py-3">Grade Level</th>
                                <th class="text-muted small text-uppercase py-3">Time Schedule</th>
                                <th class="text-end pe-4 text-muted small text-uppercase py-3" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sections as $sec)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center fw-bold me-3 text-secondary" style="width: 35px; height: 35px;">
                                            {{ $sec->grade }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark fs-5">{{ $sec->grade }} - {{ $sec->class_name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted">Grade {{ $sec->grade }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-indigo-soft text-primary border border-primary-subtle fw-normal px-2">
                                        <i class="bi bi-clock me-1"></i> {{ $sec->classCategory->name }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <form action="{{ route('sections.destroy', $sec->id) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(event)">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light text-danger border hover-shadow" title="Delete Class">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-grid-3x3-gap fs-1 d-block mb-2 opacity-25"></i>
                                    No classes created yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

@push('styles')
<style>
    .bg-indigo-soft { background-color: #e0e7ff; color: #4338ca; }
    .bg-emerald-soft { background-color: #d1fae5; color: #059669; }
    .hover-shadow:hover { background-color: #fee2e2; border-color: #fecaca !important; }
</style>
@endpush

@push('scripts')
<script>
    function confirmDelete(event) {
        event.preventDefault();
        let form = event.target;

        Swal.fire({
            title: 'Delete Class?',
            text: "This will remove the class and ALL its allocated subjects/timetables.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete it!',
            customClass: { popup: 'rounded-4' }
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
</script>
@endpush

@endsection
