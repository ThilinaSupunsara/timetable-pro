@extends('layouts.app')

@section('title', 'Subjects | Timetable System')

@section('content')
<div class="container pb-5">

    <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Manage Subjects</h4>
            <span class="text-muted small">Add and manage school subjects</span>
        </div>
        <a href="{{ route('teachers.index') }}" class="btn btn-light text-muted border px-4" style="border-radius: 10px;">
            Next: Teachers <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="row g-4">

        <div class="col-md-4">
            <div class="card-modern h-100 fade-in">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold"><i class="bi bi-book-half text-primary me-2"></i> Add New Subject</span>
                </div>
                <div class="p-4">
                    <form action="{{ route('subjects.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label small text-uppercase text-muted">Subject Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-type"></i></span>
                                <input type="text" name="name" class="form-control border-start-0 ps-0" placeholder="Ex: Mathematics" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small text-uppercase text-muted">Subject Code</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-qr-code"></i></span>
                                <input type="text" name="code" class="form-control border-start-0 ps-0" placeholder="Ex: MAT" required>
                            </div>
                            <div class="form-text small">Short code for timetable (Max 3-5 chars)</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 shadow-sm py-2">
                            <i class="bi bi-plus-lg me-1"></i> Save Subject
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card-modern h-100 fade-in">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold"><i class="bi bi-list-columns text-success me-2"></i> Subject List</span>
                    <span class="badge bg-light text-dark border">{{ $subjects->count() }} Items</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 text-muted small text-uppercase py-3">Subject Name</th>
                                <th class="text-muted small text-uppercase py-3">Code</th>
                                <th class="text-end pe-4 text-muted small text-uppercase py-3" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subjects as $sub)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $sub->name }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-indigo-soft text-primary border border-primary-subtle px-3">
                                        {{ $sub->code }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <form action="{{ route('subjects.destroy', $sub->id) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(event)">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light text-danger border hover-shadow" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fs-1 d-block mb-2 opacity-25"></i>
                                    No subjects added yet.
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
    .hover-shadow:hover { background-color: #fee2e2; border-color: #fecaca !important; }
</style>
@endpush

@push('scripts')
<script>
    function confirmDelete(event) {
        event.preventDefault();
        let form = event.target;

        Swal.fire({
            title: 'Delete Subject?',
            text: "This will remove the subject from the system. Allocations using this subject might be affected.",
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
