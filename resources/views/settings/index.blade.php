@extends('layouts.app')

@section('title', 'Settings | Timetable System')

@section('content')
<div class="container pb-5">

    <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
        <div>
            <h4 class="fw-bold mb-1 text-dark">System Settings</h4>
            <span class="text-muted small">Manage Class Categories & Time Slots</span>
        </div>
        <a href="{{ route('subjects.index') }}" class="btn btn-light text-muted border px-4" style="border-radius: 10px;">
            Next: Subjects <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card-modern h-100 fade-in">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold"><i class="bi bi-plus-circle-fill text-primary me-2"></i> New Category</span>
                </div>
                <div class="p-4">
                    <form action="{{ route('settings.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Category Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Ex: Primary (Grade 1-5)" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 shadow-sm py-2">
                            <i class="bi bi-save me-1"></i> Create Category
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card-modern h-100 fade-in">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold"><i class="bi bi-list-ul text-success me-2"></i> Existing Categories</span>
                    <span class="badge bg-light text-dark border">{{ $categories->count() }} Active</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 text-muted small text-uppercase py-3">Name</th>
                                <th class="text-muted small text-uppercase py-3">Description</th>
                                <th class="text-end pe-4 text-muted small text-uppercase py-3" style="width: 160px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $cat)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $cat->name }}</td>
                                <td class="text-muted small">{{ $cat->description ?? '-' }}</td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('settings.manage', $cat->id) }}" class="btn btn-sm btn-light text-primary border hover-shadow" title="Configure Time Slots">
                                            <i class="bi bi-clock"></i> Timings
                                        </a>

                                        <form action="{{ route('settings.destroy', $cat->id) }}" method="POST" onsubmit="return confirmDelete(event)">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger border hover-shadow" title="Delete Category">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">
                                    <i class="bi bi-layers fs-1 d-block mb-2 opacity-25"></i>
                                    No categories found.
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
@endsection

@push('styles')
<style>
    .hover-shadow:hover { background-color: #f8fafc; border-color: #cbd5e1 !important; }
</style>
@endpush

@push('scripts')
<script>
    function confirmDelete(event) {
        event.preventDefault();
        let form = event.target;

        Swal.fire({
            title: 'Delete Category?',
            text: "This will remove the category. Ensure no classes are assigned to it first.",
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
