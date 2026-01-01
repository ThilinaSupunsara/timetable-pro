@extends('layouts.app')

@section('title', 'Teachers | Timetable System')

@section('content')
<div class="container pb-5">

    <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Manage Teachers</h4>
            <span class="text-muted small">Add staff members and assign their subject competencies</span>
        </div>
        <a href="{{ route('sections.index') }}" class="btn btn-light text-muted border px-4" style="border-radius: 10px;">
            Next: Classes <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="row g-4">

        <div class="col-md-4">
            <div class="card-modern h-100 fade-in">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold"><i class="bi bi-person-plus-fill text-primary me-2"></i> Add New Teacher</span>
                </div>
                <div class="p-4">
                    <form action="{{ route('teachers.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label small text-uppercase text-muted">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" name="name" class="form-control border-start-0 ps-0" placeholder="Ex: Mr. Kamal Perera" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-uppercase text-muted">Short Code</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-tag"></i></span>
                                <input type="text" name="short_code" class="form-control border-start-0 ps-0" placeholder="Ex: KML" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small text-uppercase text-muted d-flex justify-content-between">
                                <span>Teaches Subjects</span>
                                <span class="text-primary" style="cursor: pointer; font-size: 10px;" onclick="selectAllSubjects()">Select All</span>
                            </label>

                            <div class="border rounded-3 p-2 bg-light" style="max-height: 200px; overflow-y: auto;">
                                @forelse($subjects as $sub)
                                    <div class="form-check p-2 border-bottom bg-white rounded-2 mb-1 hover-bg">
                                        <input class="form-check-input ms-1 subject-checkbox" type="checkbox" name="subjects[]" value="{{ $sub->id }}" id="sub_{{ $sub->id }}" style="cursor: pointer;">
                                        <label class="form-check-label w-100 ps-2" for="sub_{{ $sub->id }}" style="cursor: pointer; font-size: 0.9rem;">
                                            <span class="fw-bold text-dark">{{ $sub->code }}</span>
                                            <span class="text-muted small ms-1">- {{ $sub->name }}</span>
                                        </label>
                                    </div>
                                @empty
                                    <div class="text-center py-4 text-muted small">
                                        <i class="bi bi-exclamation-circle mb-1"></i><br>
                                        No subjects found.<br>
                                        <a href="{{ route('subjects.index') }}" class="text-decoration-none fw-bold">Add Subjects first</a>
                                    </div>
                                @endforelse
                            </div>
                            <div class="form-text small mt-1"><i class="bi bi-info-circle me-1"></i> Select all subjects this teacher is qualified to teach.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 shadow-sm py-2">
                            <i class="bi bi-save me-1"></i> Save Teacher
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card-modern h-100 fade-in">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold"><i class="bi bi-people-fill text-success me-2"></i> Teacher Directory</span>
                    <span class="badge bg-light text-dark border">{{ $teachers->count() }} Staff Members</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 text-muted small text-uppercase py-3">Teacher Name</th>
                                <th class="text-muted small text-uppercase py-3">Code</th>
                                <th class="text-muted small text-uppercase py-3" style="width: 30%;">Competencies</th>
                                <th class="text-end pe-4 text-muted small text-uppercase py-3" style="width: 80px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($teachers as $t)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-indigo-soft text-primary d-flex align-items-center justify-content-center fw-bold me-3" style="width: 35px; height: 35px; font-size: 0.8rem;">
                                            {{ substr($t->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $t->name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border px-2">
                                        {{ $t->short_code }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse($t->subjects as $sub)
                                            <span class="badge bg-emerald-soft text-success border border-success-subtle fw-normal">
                                                {{ $sub->code }}
                                            </span>
                                        @empty
                                            <span class="text-muted small fst-italic">No subjects</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <form action="{{ route('teachers.destroy', $t->id) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(event)">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light text-danger border hover-shadow" title="Remove Teacher">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-x fs-1 d-block mb-2 opacity-25"></i>
                                    No teachers added yet.
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
    .hover-bg:hover { background-color: #f8fafc !important; }
    .hover-shadow:hover { background-color: #fee2e2; border-color: #fecaca !important; }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
@endpush

@push('scripts')
<script>
    function selectAllSubjects() {
        let checkboxes = document.querySelectorAll('.subject-checkbox');
        let allChecked = Array.from(checkboxes).every(c => c.checked);
        checkboxes.forEach(c => c.checked = !allChecked);
    }

    function confirmDelete(event) {
        event.preventDefault();
        let form = event.target;

        Swal.fire({
            title: 'Delete Teacher?',
            text: "This will remove the teacher from the system. Any timetables assigned to this teacher might be cleared.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete!',
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
