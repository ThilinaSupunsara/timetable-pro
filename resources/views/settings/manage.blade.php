<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Time Structure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .drag-handle { cursor: move; color: #aaa; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="mb-3">
        <a href="{{ route('settings.index') }}" class="btn btn-secondary">&larr; Back to Settings</a>
    </div>

    <div class="card shadow">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">{{ $category->name }} - Time Structure</h4>
                <small>Define daily routine (Assembly, Periods, Intervals)</small>
            </div>
            <button type="button" class="btn btn-primary" onclick="addSlot()">+ Add New Slot</button>
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form action="{{ route('settings.update', $category->id) }}" method="POST">
                @csrf

                <table class="table table-bordered align-middle" id="slotsTable">
                    <thead class="table-secondary">
                        <tr>
                            <th width="5%">#</th>
                            <th width="30%">Slot Name (Label)</th>
                            <th width="20%">Start Time</th>
                            <th width="20%">End Time</th>
                            <th width="15%" class="text-center">Is Break?</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody id="slotsBody">
                        @foreach($category->periodTimings as $index => $timing)
                        <tr>
                            <td class="text-center fw-bold">{{ $index + 1 }}</td>
                            <td>
                                <input type="text" name="slots[{{ $index }}][label]" value="{{ $timing->label }}" class="form-control" placeholder="Ex: Period 1" required>
                            </td>
                            <td>
                                <input type="time" name="slots[{{ $index }}][start]" value="{{ \Carbon\Carbon::parse($timing->start_time)->format('H:i') }}" class="form-control" required>
                            </td>
                            <td>
                                <input type="time" name="slots[{{ $index }}][end]" value="{{ \Carbon\Carbon::parse($timing->end_time)->format('H:i') }}" class="form-control" required>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="slots[{{ $index }}][is_break]" class="form-check-input" value="1" {{ $timing->is_break ? 'checked' : '' }}>
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeSlot(this)">Remove</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="alert alert-info">
                    <small>💡 Tip: Add "Assembly", "Interval", "Lunch Break" as slots and check 'Is Break'.</small>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100">Save Time Structure</button>
            </form>
        </div>
    </div>
</div>

<script>
    let slotCount = {{ $category->periodTimings->count() }};

    function addSlot() {
        let tbody = document.getElementById('slotsBody');
        let index = slotCount;

        let row = `
            <tr>
                <td class="text-center fw-bold text-muted">New</td>
                <td>
                    <input type="text" name="slots[${index}][label]" class="form-control" placeholder="Ex: Period ${index + 1} or Interval" required>
                </td>
                <td>
                    <input type="time" name="slots[${index}][start]" class="form-control" required>
                </td>
                <td>
                    <input type="time" name="slots[${index}][end]" class="form-control" required>
                </td>
                <td class="text-center">
                    <input type="checkbox" name="slots[${index}][is_break]" class="form-check-input" value="1">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeSlot(this)">Remove</button>
                </td>
            </tr>
        `;

        tbody.insertAdjacentHTML('beforeend', row);
        slotCount++;
    }

    function removeSlot(btn) {
        if(confirm('Are you sure you want to remove this slot?')) {
            btn.closest('tr').remove();
        }
    }
</script>

</body>
</html>
