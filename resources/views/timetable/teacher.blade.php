<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Timetable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .tt-cell {
            height: 100px;
            text-align: center;
            vertical-align: middle;
            padding: 5px !important;
            width: 19%;
            border: 1px solid #dee2e6;
        }
        .period-num-col {
            width: 5%;
            font-weight: bold;
            background-color: #f8f9fa;
            text-align: center;
            vertical-align: middle;
            font-size: 1.2em;
        }
        .active-slot {
            background-color: #e3f2fd;
            border: 2px solid #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
        }
        .subject-name {
            font-weight: bold;
            color: #0d6efd;
            font-size: 1em;
            margin-bottom: 2px;
        }
        .class-name {
            font-weight: bold;
            color: #198754;
            font-size: 1.1em;
        }
        .time-badge {
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.75em;
            color: #555;
            margin-top: 5px;
            font-weight: 600;
        }
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .active-slot { border: 1px solid #000; background-color: #f0f0f0 !important; }
            .period-num-col { background-color: #ddd !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid mt-4 px-4">

    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <a href="{{ route('timetable.view') }}" class="btn btn-secondary btn-sm mb-2">&larr; Class View</a>
            <h3>👨‍🏫 Teacher's Timetable</h3>
        </div>
        @if(isset($selectedTeacher))
            <a href="{{ route('timetable.download_teacher_pdf', ['teacher_id' => $selectedTeacher->id]) }}" class="btn btn-success">
                📄 Download PDF
            </a>
        @else
            <button class="btn btn-secondary" disabled>Select a Teacher to Download</button>
        @endif
    </div>

    <div class="card mb-4 no-print shadow-sm">
        <div class="card-body">
            <form action="{{ route('timetable.teacher') }}" method="GET" class="row align-items-center g-3">
                <div class="col-auto">
                    <label class="col-form-label fw-bold">Select Teacher:</label>
                </div>
                <div class="col-auto">
                    <select name="teacher_id" class="form-select" onchange="this.form.submit()" style="min-width: 300px;">
                        <option value="">-- Choose a Teacher --</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->name }} ({{ $t->short_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($selectedTeacher)
    <div class="card shadow">
        <div class="card-header bg-primary text-white text-center">
            <h4 class="mb-0">{{ $selectedTeacher->name }} ({{ $selectedTeacher->short_code }})</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 border-secondary">
                    <thead class="table-dark text-center">
                        <tr>
                            <th style="width: 60px;">#</th> <th>Monday</th>
                            <th>Tuesday</th>
                            <th>Wednesday</th>
                            <th>Thursday</th>
                            <th>Friday</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($p = 1; $p <= $maxPeriod; $p++)

                            @php
                                // --- NEW LOGIC START ---
                                // මේ Period එකේ සතියේ කිසිම දවසක පන්තියක් තියෙනවද කියලා බලනවා
                                $hasClass = false;
                                for($day = 1; $day <= 5; $day++) {
                                    if(isset($timetable[$day][$p])) {
                                        $hasClass = true;
                                        break; // පන්තියක් හම්බුනා, දැන් Loop එක නවත්තන්න
                                    }
                                }
                                // --- NEW LOGIC END ---
                            @endphp

                            {{-- පන්තියක් තියෙනවා නම් විතරක් Table Row එක පෙන්වන්න --}}
                            @if($hasClass)
                                @php
                                    $timeInfo = $periodTimings[$p] ?? null;
                                    $startTime = $timeInfo ? \Carbon\Carbon::parse($timeInfo->start_time)->format('h:i A') : '';
                                    $endTime = $timeInfo ? \Carbon\Carbon::parse($timeInfo->end_time)->format('h:i A') : '';
                                    $timeString = ($startTime && $endTime) ? "$startTime - $endTime" : '';
                                @endphp

                                <tr>
                                    <td class="period-num-col">{{ $p }}</td>

                                    @for($d = 1; $d <= 5; $d++)
                                        @php
                                            $entry = $timetable[$d][$p] ?? null;
                                        @endphp

                                        <td class="tt-cell">
                                            @if($entry)
                                                <div class="active-slot">
                                                    <div class="class-name">{{ $entry->section->grade }} - {{ $entry->section->class_name }}</div>
                                                    <div class="subject-name">{{ $entry->subject->name }}</div>

                                                    @if($timeString)
                                                        <div class="time-badge">🕒 {{ $timeString }}</div>
                                                    @endif
                                                </div>
                                            @else
                                                @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endif
                            {{-- පන්තියක් නැතිනම් Row එක Skip වෙනවා --}}

                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
        <div class="alert alert-info text-center mt-5">
            <h4>Please select a teacher to view their schedule.</h4>
        </div>
    @endif

</div>

</body>
</html>
