<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Timetable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .tt-cell { height: 80px; text-align: center; vertical-align: middle; }
        .subject { font-weight: bold; font-size: 1.1em; display: block; }
        .teacher { font-size: 0.85em; color: #555; }
        .break-cell { background-color: #ffeeba !important; color: #856404; font-weight: bold; letter-spacing: 2px; }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid mt-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h3>📅 School Timetable</h3>
        <div class="btn-group">


            @if($selectedSection)
                <a href="{{ route('timetable.download_pdf', ['section_id' => $selectedSection->id]) }}" class="btn btn-danger">
                    📄 Download PDF
                </a>
            @endif
        </div>
    </div>

    <div class="card mb-4 no-print">
        <div class="card-body d-flex justify-content-between align-items-center">
            <form action="{{ route('timetable.view') }}" method="GET" class="d-flex align-items-center">
                <label class="me-2 fw-bold">Select Class:</label>
                <select name="section_id" class="form-select me-2" onchange="this.form.submit()" style="width: 250px;">
                    <option value="">-- Select --</option>
                    @foreach($sections as $sec)
                        <option value="{{ $sec->id }}" {{ request('section_id') == $sec->id ? 'selected' : '' }}>
                            {{ $sec->grade }} - {{ $sec->class_name }}
                        </option>
                    @endforeach
                </select>
            </form>

            @if($selectedSection)
            <form action="{{ route('timetable.generate') }}" method="POST">
                @csrf
                <input type="hidden" name="section_id" value="{{ $selectedSection->id }}">
                <button type="submit" class="btn btn-primary">⚡ Generate / Regenerate Timetable</button>
            </form>
            @endif
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    @if(session('error'))
    <div class="alert alert-danger">
        <strong>Error:</strong> {{ session('error') }}
    </div>
@endif

    @if($selectedSection)
    <div class="card shadow">
        <div class="card-header bg-dark text-white text-center">
            <h4 class="mb-0">TIMETABLE: {{ $selectedSection->grade }} - {{ $selectedSection->class_name }}</h4>
            <small>{{ $selectedSection->classCategory->name }} Section</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 border-dark">
                    <thead class="table-secondary text-center">
                        <tr>
                            <th>Time / Day</th>
                            <th>Monday</th>
                            <th>Tuesday</th>
                            <th>Wednesday</th>
                            <th>Thursday</th>
                            <th>Friday</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($timings as $slot)
                        <tr>
                            <td class="bg-light fw-bold text-center align-middle" style="width: 150px;">
                                <div style="font-size: 0.9em">{{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }}</div>
                                <div class="text-muted">-</div>
                                <div style="font-size: 0.9em">{{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}</div>
                                <div class="badge bg-secondary mt-1">{{ $slot->label }}</div>
                            </td>

                            @if($slot->is_break)
                                <td colspan="5" class="break-cell text-center align-middle">
                                    {{ strtoupper($slot->label) }}
                                </td>
                            @else
                                @for($day=1; $day<=5; $day++)
                                    @php
                                        $entry = $timetable[$day][$slot->period_number] ?? null;
                                    @endphp
                                    <td class="tt-cell">
                                        @if($entry)
                                            <span class="subject">{{ $entry->subject->name }}</span>
                                            @if($entry->teacher)
                                                <span class="teacher">{{ $entry->teacher->name }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endfor
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        .card { border: none !important; shadow: none !important; }
        .break-cell { background-color: #ddd !important; -webkit-print-color-adjust: exact; }
    }
</style>

</body>
</html>
