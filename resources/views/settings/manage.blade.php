@extends('layouts.app')

@section('title', 'Manage Times | Timetable System')

@section('content')
<div class="container pb-5">

    <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
        <div>
            <a href="{{ route('settings.index') }}" class="text-decoration-none text-muted small fw-bold mb-1 d-block">
                <i class="bi bi-arrow-left me-1"></i> Back to Settings
            </a>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-clock-history text-primary me-2"></i> {{ $category->name }} Structure
            </h4>
        </div>

        <button type="button" class="btn btn-primary shadow-sm" onclick="addSlot()">
            <i class="bi bi-plus-lg me-1"></i> Add New Slot
        </button>
    </div>

    <div class="card-modern fade-in">
        <div class="p-4">

            <form action="{{ route('settings.update', $category->id) }}" method="POST" id="timeForm">
                @csrf

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="slotsTable">
                        <thead class="bg-light border-bottom">
                            <tr>
                                <th style="width: 50px;" class="text-center text-muted small text-uppercase">#</th>
                                <th style="min-width: 250px;" class="text-muted small text-uppercase ps-3">Slot Name (Label)</th>
                                <th style="width: 180px;" class="text-muted small text-uppercase">Start Time</th>
                                <th style="width: 180px;" class="text-muted small text-uppercase">End Time</th>
                                <th style="width: 100px;" class="text-center text-muted small text-uppercase">Is Break?</th>
                                <th style="width: 80px;" class="text-end text-muted small text-uppercase">Remove</th>
                            </tr>
                        </thead>
                        <tbody id="slotsBody">
                            @foreach($category->periodTimings as $index => $timing)
                            <tr>
                                <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                                <td class="ps-3">
                                    <input type="text" name="slots[{{ $index }}][label]" value="{{ $timing->label }}"
                                           class="form-control" placeholder="Ex: Period 1" required>
                                </td>
                                <td>
                                    <input type="time" name="slots[{{ $index }}][start]" value="{{ \Carbon\Carbon::parse($timing->start_time)->format('H:i') }}"
                                           class="form-control text-center" required>
                                </td>
                                <td>
                                    <input type="time" name="slots[{{ $index }}][end]" value="{{ \Carbon\Carbon::parse($timing->end_time)->format('H:i') }}"
                                           class="form-control text-center" required>
                                </td>
                                <td class="text-center">
                                    <div class="form-check d-flex justify-content-center">
                                        <input type="checkbox" name="slots[{{ $index }}][is_break]" class="form-check-input" value="1"
                                               {{ $timing->is_break ? 'checked' : '' }} style="transform: scale(1.2); cursor: pointer;">
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-light text-danger btn-sm border" onclick="removeSlot(this)">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 d-flex align-items-center justify-content-between p-3 bg-light rounded-3 border">
                    <div class="d-flex align-items-center text-muted small">
                        <i class="bi bi-info-circle-fill text-info fs-5 me-2"></i>
                        <span>Tip: Mark "Interval" or "Lunch" as <b>Break</b> to avoid scheduling classes.</span>
                    </div>
                    <button type="submit" class="btn btn-success fw-bold px-4 py-2 shadow-sm">
                        <i class="bi bi-check-circle-fill me-2"></i> Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Keep track of index to ensure unique names
    let slotCount = {{ $category->periodTimings->count() }};

    function addSlot() {
        let tbody = document.getElementById('slotsBody');
        let index = slotCount;

        // New Row HTML
        let row = `
            <tr class="fade-in">
                <td class="text-center fw-bold text-success small"><i class="bi bi-plus-circle-fill"></i></td>
                <td class="ps-3">
                    <input type="text" name="slots[${index}][label]" class="form-control" placeholder="Ex: Period ${index + 1}" required>
                </td>
                <td>
                    <input type="time" name="slots[${index}][start]" class="form-control text-center" required>
                </td>
                <td>
                    <input type="time" name="slots[${index}][end]" class="form-control text-center" required>
                </td>
                <td class="text-center">
                    <div class="form-check d-flex justify-content-center">
                        <input type="checkbox" name="slots[${index}][is_break]" class="form-check-input" value="1" style="transform: scale(1.2); cursor: pointer;">
                    </div>
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-light text-danger btn-sm border" onclick="removeSlot(this)">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </td>
            </tr>
        `;

        tbody.insertAdjacentHTML('beforeend', row);
        slotCount++;
    }

    function removeSlot(btn) {
        Swal.fire({
            title: 'Remove Slot?',
            text: "Are you sure you want to delete this row?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, remove it',
            customClass: { popup: 'rounded-4' }
        }).then((result) => {
            if (result.isConfirmed) {
                let row = btn.closest('tr');
                row.style.transition = "all 0.3s ease";
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                setTimeout(() => row.remove(), 300);
            }
        });
    }
</script>
@endpush
