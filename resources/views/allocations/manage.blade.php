<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Workload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .fixed-options { display: none; }
        .row-card { transition: background 0.3s; }
        .row-card:hover { background: #f8f9fa; }
        .invalid-feedback-custom {
            color: #dc3545;
            font-size: 0.8em;
            font-weight: bold;
            display: block;
            margin-top: 5px;
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid mt-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('allocations.index') }}" class="btn btn-secondary btn-sm mb-2">&larr; Back</a>
            <h4>Class: <span class="text-primary">{{ $section->grade }} - {{ $section->class_name }}</span></h4>
            <small class="text-muted">Category: {{ $section->classCategory->name }}</small>
        </div>
        <button type="submit" form="allocationForm" id="saveBtn" class="btn btn-success btn-lg">💾 Save Workload</button>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <form action="{{ route('allocations.update', $section->id) }}" method="POST" id="allocationForm">
        @csrf

        <div class="card shadow">
            <div class="card-header bg-dark text-white d-flex justify-content-between">
                <span>Subject Allocations</span>
                <button type="button" class="btn btn-warning btn-sm fw-bold" onclick="addRow()">+ Add Subject Row</button>
            </div>
            <div class="card-body p-2">
                <table class="table table-bordered align-middle" id="allocTable">
                    <thead class="table-secondary text-center">
                        <tr>
                            <th width="20%">Subject</th>
                            <th width="20%">Teacher</th>
                            <th width="12%">Periods</th>
                            <th width="15%">Type (Block)</th>
                            <th width="28%">Fixed Slot (Optional)</th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody id="allocBody">
                    </tbody>
                </table>

                <div class="text-center p-4" id="emptyMsg">
                    <p class="text-muted">No subjects added yet. Click <b>+ Add Subject Row</b> to start.</p>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    const subjects = @json($subjects);
    const teachers = @json($teachers);
    const existingAllocations = @json($existingAllocations);

    // --- NEW: Settings වලින් එන වෙලාවල් ටික ගන්න ---
    // (Interval නැති, උගන්වන පීරියඩ් ටික විතරක් පෙරලා ගනිමු)
    const timeSlots = @json($section->classCategory->periodTimings->where('is_break', false)->values());

    let rowCount = 0;

    document.addEventListener("DOMContentLoaded", function() {
        if(existingAllocations.length > 0) {
            existingAllocations.forEach(alloc => {
                addRow(alloc);
            });
        } else {
            addRow();
        }
    });

    function addRow(data = null) {
        document.getElementById('emptyMsg').style.display = 'none';

        let tbody = document.getElementById('allocBody');
        let index = rowCount;

        // Data setup
        let subjectId = data ? data.subject_id : '';
        let teacherId = data ? data.teacher_id : '';
        let periods = data ? data.periods_per_week : '';
        let consecutive = data ? data.consecutive_periods : 1;
        let isFixed = (data && data.is_fixed_slot) ? 'checked' : '';
        let fixedDisplay = (data && data.is_fixed_slot) ? '' : 'd-none';
        let fixedDay = data ? data.fixed_day : '';
        let fixedPeriod = data ? data.fixed_period : '';

        // --- NEW: Dropdown Options Generate කිරීම ---
        // කලින් තිබුන 1..12 ලූප් එක වෙනුවට, අපේ timeSlots පාවිච්චි කරමු
        let slotOptions = '<option value="">Select Slot</option>';

        if (timeSlots.length > 0) {
            timeSlots.forEach(slot => {
                // වෙලාව ලස්සනට පෙන්නන්න (07:50:00 -> 07:50)
                let start = slot.start_time.substring(0, 5);
                let end = slot.end_time.substring(0, 5);
                let selected = (fixedPeriod == slot.period_number) ? 'selected' : '';

                // Dropdown Text එක: "Slot 1 (07:50 - 08:30)"
                slotOptions += `<option value="${slot.period_number}" ${selected}>
                                    Slot ${slot.period_number} (${start} - ${end})
                                </option>`;
            });
        } else {
            slotOptions += '<option value="" disabled>No slots in settings!</option>';
        }

        let row = `
            <tr id="row_${index}" class="row-card">
                <td>
                    <select name="allocations[${index}][subject_id]" class="form-select subject-select" onchange="filterTeachers(${index})" required>
                        <option value="">Select Subject</option>
                        ${subjects.map(sub => `<option value="${sub.id}" ${sub.id == subjectId ? 'selected' : ''}>${sub.name}</option>`).join('')}
                    </select>
                </td>
                <td>
                    <select name="allocations[${index}][teacher_id]" id="teacher_select_${index}" class="form-select">
                        <option value="">-- Select Teacher --</option>
                    </select>
                </td>
                <td>
                    <input type="number" name="allocations[${index}][periods]" id="periods_${index}"
                           value="${periods}" min="1" max="15" class="form-control text-center"
                           placeholder="1" required oninput="validateRow(${index})">
                </td>
                <td>
                    <select name="allocations[${index}][consecutive]" id="type_${index}"
                            class="form-select" onchange="validateRow(${index})">
                        <option value="1" ${consecutive == 1 ? 'selected' : ''}>Single</option>
                        <option value="2" ${consecutive == 2 ? 'selected' : ''}>Double (2)</option>
                        <option value="3" ${consecutive == 3 ? 'selected' : ''}>Triple (3)</option>
                        <option value="4" ${consecutive == 4 ? 'selected' : ''}>Quadruple (4)</option>
                    </select>
                    <div id="error_type_${index}" class="invalid-feedback-custom d-none"></div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="form-check me-2">
                            <input class="form-check-input" type="checkbox" name="allocations[${index}][is_fixed]" value="1" id="fix_${index}" onchange="toggleFixed(${index})" ${isFixed}>
                            <label class="form-check-label" for="fix_${index}">Fix?</label>
                        </div>
                        <div id="fixed_opts_${index}" class="d-flex align-items-center ${fixedDisplay}">
                            <select name="allocations[${index}][fixed_day]" id="fday_${index}" class="form-select form-select-sm me-1" style="width: 80px;" onchange="validateRow(${index})">
                                <option value="">Day</option>
                                <option value="1" ${fixedDay == 1 ? 'selected' : ''}>Mon</option>
                                <option value="2" ${fixedDay == 2 ? 'selected' : ''}>Tue</option>
                                <option value="3" ${fixedDay == 3 ? 'selected' : ''}>Wed</option>
                                <option value="4" ${fixedDay == 4 ? 'selected' : ''}>Thu</option>
                                <option value="5" ${fixedDay == 5 ? 'selected' : ''}>Fri</option>
                            </select>

                            <select name="allocations[${index}][fixed_period]" id="fperiod_${index}" class="form-select form-select-sm" style="width: 160px;" onchange="validateRow(${index})">
                                ${slotOptions}
                            </select>

                        </div>
                    </div>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRow(${index})">×</button>
                </td>
            </tr>
        `;

        tbody.insertAdjacentHTML('beforeend', row);

        if(subjectId) filterTeachers(index, teacherId);

        validateRow(index);
        rowCount++;
    }

    function validateRow(index) {
        let periodsInput = document.getElementById(`periods_${index}`);
        let typeSelect = document.getElementById(`type_${index}`);
        let errorDiv = document.getElementById(`error_type_${index}`);

        let fixCheckbox = document.getElementById(`fix_${index}`);
        let fDay = document.getElementById(`fday_${index}`);
        let fPeriod = document.getElementById(`fperiod_${index}`);
        let fixedInfo = document.getElementById(`fixed_info_${index}`);

        let periods = parseInt(periodsInput.value) || 0;
        let type = parseInt(typeSelect.value) || 1;
        let isValid = true;
        let errorMsg = "";

        // 1. Periods vs Type Logic
        if (periods > 0) {
            if (periods < type) {
                isValid = false;
                errorMsg = `Must be ≥ ${type}`;
            } else if (periods % type !== 0) {
                isValid = false;
                errorMsg = `Multiple of ${type} req.`;
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

        // 2. Fixed Slot Logic & Range Preview
        if (fixCheckbox && fixCheckbox.checked) {

            if (!fixedInfo) {
                let parentDiv = document.getElementById(`fixed_opts_${index}`);
                fixedInfo = document.createElement('small');
                fixedInfo.id = `fixed_info_${index}`;
                fixedInfo.className = "ms-2 fw-bold";
                parentDiv.appendChild(fixedInfo);
            }

            if (fDay.value === "" || fPeriod.value === "") {
                if(fDay.value === "") fDay.classList.add('is-invalid');
                if(fPeriod.value === "") fPeriod.classList.add('is-invalid');
                fixedInfo.innerText = "";
                isValid = false;
            } else {
                fDay.classList.remove('is-invalid');
                fPeriod.classList.remove('is-invalid');

                // Range Calculation
                let start = parseInt(fPeriod.value);
                let end = start + type - 1;

                // Get Max Slot from our timeSlots array
                let maxSlot = timeSlots.length > 0 ? timeSlots[timeSlots.length - 1].period_number : 10;

                if (end > maxSlot) {
                    fPeriod.classList.add('is-invalid');
                    fixedInfo.className = "ms-2 fw-bold text-danger";
                    fixedInfo.innerText = `⚠️ Ends at ${end} (Limit!)`;
                    isValid = false;
                } else {
                    if (type > 1) {
                        fixedInfo.className = "ms-2 fw-bold text-success";
                        fixedInfo.innerText = `✅ Covers ${start}-${end}`;
                    } else {
                        fixedInfo.innerText = "";
                    }
                }
            }
        } else {
            let existingInfo = document.getElementById(`fixed_info_${index}`);
            if(existingInfo) existingInfo.innerText = "";
        }

        checkAllRows(isValid);
    }

    function checkAllRows(currentRowValid = true) {
        let saveBtn = document.getElementById('saveBtn');
        let invalids = document.querySelectorAll('.is-invalid');

        if (invalids.length > 0 || !currentRowValid) {
            saveBtn.disabled = true;
            saveBtn.innerText = "⚠️ Fix Errors";
            saveBtn.classList.remove('btn-success');
            saveBtn.classList.add('btn-danger');
        } else {
            saveBtn.disabled = false;
            saveBtn.innerText = "💾 Save Workload";
            saveBtn.classList.remove('btn-danger');
            saveBtn.classList.add('btn-success');
        }
    }

    function filterTeachers(rowIndex, selectedTeacherId = null) {
        let subjectId = document.querySelector(`#row_${rowIndex} .subject-select`).value;
        let teacherSelect = document.getElementById(`teacher_select_${rowIndex}`);

        teacherSelect.innerHTML = '<option value="">-- Select Teacher --</option>';

        if(!subjectId) return;

        let filteredTeachers = teachers.filter(t => t.subject_ids.includes(parseInt(subjectId)));
        filteredTeachers.forEach(t => {
            let option = document.createElement('option');
            option.value = t.id;
            option.text = t.name;
            if(selectedTeacherId && t.id == selectedTeacherId) {
                option.selected = true;
            }
            teacherSelect.appendChild(option);
        });
    }

    function toggleFixed(index) {
        let checkbox = document.getElementById(`fix_${index}`);
        let opts = document.getElementById(`fixed_opts_${index}`);

        if(checkbox.checked) {
            opts.classList.remove('d-none');
        } else {
            opts.classList.add('d-none');
            let fDay = document.getElementById(`fday_${index}`);
            let fPeriod = document.getElementById(`fperiod_${index}`);
            let fInfo = document.getElementById(`fixed_info_${index}`);
            if(fDay) fDay.classList.remove('is-invalid');
            if(fPeriod) fPeriod.classList.remove('is-invalid');
            if(fInfo) fInfo.innerText = "";

            if(fDay) fDay.value = "";
            if(fPeriod) fPeriod.value = "";
        }
        validateRow(index);
    }

    function removeRow(index) {
        document.getElementById(`row_${index}`).remove();
        checkAllRows();
    }
</script>

</body>
</html>
