@extends('layouts.app')

@section('title', 'Manage Workload | Timetable System')

@section('content')
<div class="container pb-5">

    <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
        <div>
            <a href="{{ route('allocations.index') }}" class="text-decoration-none text-muted small fw-bold mb-1 d-block">
                <i class="bi bi-arrow-left me-1"></i> Back to Selection
            </a>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-journal-bookmark-fill text-primary me-2"></i> Manage Workload
            </h4>
        </div>

        <button type="submit" form="allocationForm" id="saveBtn" class="btn btn-success px-4 py-2 shadow-sm fw-bold">
            <i class="bi bi-save-fill me-2"></i> Save Changes
        </button>
    </div>

    <div class="card-modern mb-4 fade-in">
        <div class="card-body p-4 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-indigo-soft text-primary d-flex align-items-center justify-content-center fw-bold me-3" style="width: 50px; height: 50px; font-size: 1.2rem;">
                    {{ $section->grade }}
                </div>
                <div>
                    <h5 class="fw-bold mb-0">{{ $section->grade }} - {{ $section->class_name }}</h5>
                    <span class="badge bg-light text-secondary border mt-1">
                        <i class="bi bi-clock me-1"></i> {{ $section->classCategory->name }} Category
                    </span>
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="addRow()">
                <i class="bi bi-plus-lg me-1"></i> Add Subject Row
            </button>
        </div>
    </div>

    <form action="{{ route('allocations.update', $section->id) }}" method="POST" id="allocationForm">
        @csrf

        <div class="card-modern fade-in">
            <div class="p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="allocTable">
                        <thead class="bg-light border-bottom">
                            <tr>
                                <th style="width: 25%;" class="ps-4 text-muted small text-uppercase py-3">Subject</th>
                                <th style="width: 20%;" class="text-muted small text-uppercase py-3">Teacher</th>
                                <th style="width: 10%;" class="text-center text-muted small text-uppercase py-3">Periods</th>
                                <th style="width: 15%;" class="text-muted small text-uppercase py-3">Block Type</th>
                                <th style="width: 25%;" class="text-muted small text-uppercase py-3">Fixed Slot (Optional)</th>
                                <th style="width: 5%;" class="text-end pe-4 text-muted small text-uppercase py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody id="allocBody">
                            </tbody>
                    </table>
                </div>

                <div class="text-center p-5" id="emptyMsg">
                    <i class="bi bi-clipboard-plus fs-1 text-muted opacity-25 mb-3 d-block"></i>
                    <h6 class="text-muted">No allocations yet</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addRow()">
                        Add First Subject
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .bg-indigo-soft { background-color: #e0e7ff; color: #4338ca; }
    .form-select-sm { font-size: 0.85rem; padding: 0.4rem 2rem 0.4rem 0.75rem; }
    .invalid-feedback-custom { color: #dc3545; font-size: 0.75em; font-weight: 600; display: block; margin-top: 4px; }
</style>
@endpush

@push('scripts')
<script>
    // Pass PHP data to JS
    const subjects = @json($subjects);
    const teachers = @json($teachers);
    const existingAllocations = @json($existingAllocations);
    // Get time slots excluding breaks
    const timeSlots = @json($section->classCategory->periodTimings->where('is_break', false)->values());

    let rowCount = 0;

    document.addEventListener("DOMContentLoaded", function() {
        if(existingAllocations.length > 0) {
            existingAllocations.forEach(alloc => addRow(alloc));
        } else {
            document.getElementById('emptyMsg').style.display = 'block';
        }
    });

    function addRow(data = null) {
        document.getElementById('emptyMsg').style.display = 'none';

        let tbody = document.getElementById('allocBody');
        let index = rowCount;

        // Extract Data
        let subjectId = data ? data.subject_id : '';
        let teacherId = data ? data.teacher_id : '';
        let periods = data ? data.periods_per_week : '';
        let consecutive = data ? data.consecutive_periods : 1;
        let isFixed = (data && data.is_fixed_slot) ? 'checked' : '';
        let fixedDisplay = (data && data.is_fixed_slot) ? '' : 'd-none';
        let fixedDay = data ? data.fixed_day : '';
        let fixedPeriod = data ? data.fixed_period : '';

        // Generate Slot Options
        let slotOptions = '<option value="">Select Time...</option>';
        if (timeSlots.length > 0) {
            timeSlots.forEach(slot => {
                let start = slot.start_time.substring(0, 5);
                let end = slot.end_time.substring(0, 5);
                let selected = (fixedPeriod == slot.period_number) ? 'selected' : '';
                slotOptions += `<option value="${slot.period_number}" ${selected}>P-${slot.period_number} (${start}-${end})</option>`;
            });
        } else {
            slotOptions += '<option value="" disabled>No slots configured</option>';
        }

        // HTML Template
        let row = `
            <tr id="row_${index}" class="fade-in">
                <td class="ps-4">
                    <select name="allocations[${index}][subject_id]" class="form-select subject-select" onchange="filterTeachers(${index})" required>
                        <option value="">Select Subject...</option>
                        ${subjects.map(sub => `<option value="${sub.id}" ${sub.id == subjectId ? 'selected' : ''}>${sub.name} (${sub.code})</option>`).join('')}
                    </select>
                </td>
                <td>
                    <select name="allocations[${index}][teacher_id]" id="teacher_select_${index}" class="form-select text-secondary">
                        <option value="">-- Choose Teacher --</option>
                    </select>
                </td>
                <td>
                    <input type="number" name="allocations[${index}][periods]" id="periods_${index}"
                           value="${periods}" min="1" max="15" class="form-control text-center fw-bold"
                           placeholder="0" required oninput="validateRow(${index})">
                </td>
                <td>
                    <select name="allocations[${index}][consecutive]" id="type_${index}"
                            class="form-select" onchange="validateRow(${index})">
                        <option value="1" ${consecutive == 1 ? 'selected' : ''}>Single Period</option>
                        <option value="2" ${consecutive == 2 ? 'selected' : ''}>Double Block (2)</option>
                        <option value="3" ${consecutive == 3 ? 'selected' : ''}>Triple Block (3)</option>
                        <option value="4" ${consecutive == 4 ? 'selected' : ''}>Quadruple Block (4)</option> </select>
                    </select>
                    <div id="error_type_${index}" class="invalid-feedback-custom d-none"></div>
                </td>
                <td>
                    <div class="d-flex align-items-center bg-light p-1 rounded border">
                        <div class="form-check ms-2 me-2">
                            <input class="form-check-input" type="checkbox" name="allocations[${index}][is_fixed]" value="1" id="fix_${index}" onchange="toggleFixed(${index})" ${isFixed} style="cursor: pointer;">
                            <label class="form-check-label small fw-bold text-muted" for="fix_${index}" style="cursor: pointer;">Fix</label>
                        </div>
                        <div id="fixed_opts_${index}" class="d-flex align-items-center ${fixedDisplay}" style="transition: all 0.3s;">
                            <select name="allocations[${index}][fixed_day]" id="fday_${index}" class="form-select form-select-sm me-1" style="width: 80px;" onchange="validateRow(${index})">
                                <option value="">Day</option>
                                <option value="1" ${fixedDay == 1 ? 'selected' : ''}>Mon</option>
                                <option value="2" ${fixedDay == 2 ? 'selected' : ''}>Tue</option>
                                <option value="3" ${fixedDay == 3 ? 'selected' : ''}>Wed</option>
                                <option value="4" ${fixedDay == 4 ? 'selected' : ''}>Thu</option>
                                <option value="5" ${fixedDay == 5 ? 'selected' : ''}>Fri</option>
                            </select>

                            <select name="allocations[${index}][fixed_period]" id="fperiod_${index}" class="form-select form-select-sm" style="width: 140px;" onchange="validateRow(${index})">
                                ${slotOptions}
                            </select>
                        </div>
                    </div>
                </td>
                <td class="text-end pe-4">
                    <button type="button" class="btn btn-light text-danger btn-sm border" onclick="removeRow(${index})">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </td>
            </tr>
        `;

        tbody.insertAdjacentHTML('beforeend', row);

        // Initialize logic for this row
        if(subjectId) filterTeachers(index, teacherId);
        validateRow(index);

        rowCount++;
    }

    // --- Validation Logic (Preserved & Enhanced) ---
    function validateRow(index) {
        let periodsInput = document.getElementById(`periods_${index}`);
        let typeSelect = document.getElementById(`type_${index}`);
        let errorDiv = document.getElementById(`error_type_${index}`);

        let fixCheckbox = document.getElementById(`fix_${index}`);
        let fDay = document.getElementById(`fday_${index}`);
        let fPeriod = document.getElementById(`fperiod_${index}`);

        // Remove existing info if any to prevent dupes (cleaner way: manage container)
        // Here we use parent traversal for simplicity or ID
        let fixedOptsDiv = document.getElementById(`fixed_opts_${index}`);
        let fixedInfo = fixedOptsDiv.querySelector('.fixed-info-badge');
        if(!fixedInfo) {
             fixedInfo = document.createElement('span');
             fixedInfo.className = "fixed-info-badge ms-2 small fw-bold";
             fixedOptsDiv.appendChild(fixedInfo);
        }

        let periods = parseInt(periodsInput.value) || 0;
        let type = parseInt(typeSelect.value) || 1;
        let isValid = true;
        let errorMsg = "";

        // 1. Block Logic
        if (periods > 0) {
            if (periods < type) {
                isValid = false;
                errorMsg = `Min ${type}`;
            } else if (periods % type !== 0) {
                isValid = false;
                errorMsg = `Not divisible`;
            }
        }

        if (!isValid) {
            periodsInput.classList.add('is-invalid');
            typeSelect.classList.add('is-invalid');
            errorDiv.innerText = errorMsg;
            errorDiv.classList.remove('d-none');
        } else {
            periodsInput.classList.remove('is-invalid');
            typeSelect.classList.remove('is-invalid');
            errorDiv.classList.add('d-none');
        }

        // 2. Fixed Slot Logic
        if (fixCheckbox && fixCheckbox.checked) {
            if (fDay.value === "" || fPeriod.value === "") {
                if(fDay.value === "") fDay.classList.add('is-invalid');
                if(fPeriod.value === "") fPeriod.classList.add('is-invalid');
                fixedInfo.innerText = "";
                isValid = false;
            } else {
                fDay.classList.remove('is-invalid');
                fPeriod.classList.remove('is-invalid');

                let start = parseInt(fPeriod.value);
                let end = start + type - 1;
                let maxSlot = timeSlots.length > 0 ? timeSlots[timeSlots.length - 1].period_number : 10;

                if (end > maxSlot) {
                    fPeriod.classList.add('is-invalid');
                    fixedInfo.className = "fixed-info-badge ms-2 small fw-bold text-danger";
                    fixedInfo.innerText = `Exceeds!`;
                    isValid = false;
                } else {
                    if (type > 1) {
                        fixedInfo.className = "fixed-info-badge ms-2 small fw-bold text-success";
                        fixedInfo.innerText = `P${start}-${end}`;
                    } else {
                        fixedInfo.innerText = "";
                    }
                }
            }
        } else {
            fixedInfo.innerText = "";
        }

        checkAllRows(isValid);
    }

    function checkAllRows(currentRowValid = true) {
        let saveBtn = document.getElementById('saveBtn');
        let invalids = document.querySelectorAll('.is-invalid');

        if (invalids.length > 0 || !currentRowValid) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i> Fix Errors';
            saveBtn.classList.replace('btn-success', 'btn-danger');
        } else {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="bi bi-save-fill me-2"></i> Save Changes';
            saveBtn.classList.replace('btn-danger', 'btn-success');
        }
    }

    function filterTeachers(rowIndex, selectedTeacherId = null) {
        let subjectId = document.querySelector(`#row_${rowIndex} .subject-select`).value;
        let teacherSelect = document.getElementById(`teacher_select_${rowIndex}`);

        teacherSelect.innerHTML = '<option value="">-- Choose Teacher --</option>';
        if(!subjectId) return;

        let filteredTeachers = teachers.filter(t => t.subject_ids.includes(parseInt(subjectId)));

        if(filteredTeachers.length === 0) {
            let option = document.createElement('option');
            option.text = "(No qualified teachers)";
            option.disabled = true;
            teacherSelect.appendChild(option);
        }

        filteredTeachers.forEach(t => {
            let option = document.createElement('option');
            option.value = t.id;
            option.text = t.name;
            if(selectedTeacherId && t.id == selectedTeacherId) option.selected = true;
            teacherSelect.appendChild(option);
        });
    }

    function toggleFixed(index) {
        let checkbox = document.getElementById(`fix_${index}`);
        let opts = document.getElementById(`fixed_opts_${index}`);
        if(checkbox.checked) {
            opts.classList.remove('d-none');
            opts.classList.add('d-flex');
        } else {
            opts.classList.add('d-none');
            opts.classList.remove('d-flex');
            // Reset values
            let fDay = document.getElementById(`fday_${index}`);
            let fPeriod = document.getElementById(`fperiod_${index}`);
            if(fDay) { fDay.value = ""; fDay.classList.remove('is-invalid'); }
            if(fPeriod) { fPeriod.value = ""; fPeriod.classList.remove('is-invalid'); }
        }
        validateRow(index);
    }

    function removeRow(index) {
        Swal.fire({
            title: 'Remove Subject?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, remove',
            customClass: { popup: 'rounded-4' }
        }).then((result) => {
            if (result.isConfirmed) {
                let row = document.getElementById(`row_${index}`);
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    // Check if empty
                    if(document.getElementById('allocBody').children.length === 0) {
                        document.getElementById('emptyMsg').style.display = 'block';
                    }
                    checkAllRows();
                }, 300);
            }
        });
    }
</script>
@endpush
