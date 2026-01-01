@extends('layouts.app')

@section('title', 'Workload | Timetable System')

@section('content')
<div class="container pb-5">

    <div class="d-flex justify-content-between align-items-center mb-5 fade-in">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Workload Allocation</h4>
            <span class="text-muted small">Select a class to assign subjects, teachers, and period counts</span>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('sections.index') }}" class="btn btn-light text-muted border" style="border-radius: 10px;">
                <i class="bi bi-arrow-left me-1"></i> Classes
            </a>
            <form action="{{ route('timetable.generate_all') }}" method="POST" id="quickGenForm">
                @csrf
                <button type="button" class="btn btn-success text-white px-4 shadow-sm" style="border-radius: 10px;" onclick="confirmQuickGenerate()">
                    Auto Generate <i class="bi bi-lightning-charge-fill ms-1"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="row g-4">
        @foreach($categories as $category)
        <div class="col-md-6 col-lg-4">
            <div class="card-modern h-100 fade-in">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-light rounded-top-4">
                    <span class="fw-bold text-dark">
                        <i class="bi bi-layers-half text-primary me-2"></i> {{ $category->name }}
                    </span>
                    <span class="badge bg-white text-secondary border">{{ $category->sections->count() }} Classes</span>
                </div>

                <div class="list-group list-group-flush p-2">
                    @forelse($category->sections as $section)
                        <a href="{{ route('allocations.manage', $section->id) }}"
                           class="list-group-item list-group-item-action border-0 rounded-3 mb-1 p-3 d-flex justify-content-between align-items-center hover-shadow transition-all">

                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-indigo-soft text-primary d-flex align-items-center justify-content-center fw-bold me-3"
                                     style="width: 40px; height: 40px;">
                                    {{ $section->grade }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $section->grade }} - {{ $section->class_name }}</div>
                                    <small class="text-muted" style="font-size: 0.75rem;">Click to manage</small>
                                </div>
                            </div>

                            <div class="text-primary">
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        </a>
                    @empty
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted opacity-25 mb-2 d-block"></i>
                            <span class="text-muted small">No classes found in this category.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        @endforeach

        @if($categories->isEmpty())
        <div class="col-12 text-center py-5">
            <div class="card-modern p-5 d-inline-block">
                <i class="bi bi-exclamation-circle fs-1 text-muted mb-3 d-block"></i>
                <h5 class="fw-bold text-muted">No Categories Found</h5>
                <p class="small text-muted mb-3">Please set up categories and classes first.</p>
                <a href="{{ route('settings.index') }}" class="btn btn-primary">Go to Settings</a>
            </div>
        </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    .bg-indigo-soft { background-color: #e0e7ff; color: #4338ca; }
    .hover-shadow:hover {
        background-color: #f8fafc;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        transform: translateX(5px);
    }
    .transition-all { transition: all 0.2s ease; }
</style>
@endpush

@push('scripts')
<script>
    function confirmQuickGenerate() {
        Swal.fire({
            title: 'Run Auto Generator?',
            text: "This will create timetables for all these classes based on your allocations.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            confirmButtonText: 'Yes, Generate',
            customClass: { popup: 'rounded-4' }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.showLoading();
                document.getElementById('quickGenForm').submit();
            }
        });
    }
</script>
@endpush

@endsection
