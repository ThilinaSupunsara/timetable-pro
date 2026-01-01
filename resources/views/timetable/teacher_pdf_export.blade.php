<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Teacher Timetable - {{ $teacher->short_code }}</title>
    <style>
        /* --- PAGE SETUP (A4) --- */
        @page { margin: 1cm; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px; /* අකුරු ප්‍රමාණය අඩු කළා */
            color: #333;
            line-height: 1.2;
        }

        /* --- HEADER --- */
        .header-container {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #444;
            padding-bottom: 10px;
        }
        .main-title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            color: #111;
            margin: 0;
        }
        .sub-title {
            font-size: 12px;
            color: #555;
            margin-top: 5px;
        }
        .badge {
            background-color: #eee;
            color: #000;
            border: 1px solid #ccc;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 14px;
        }

        /* --- TABLE --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        th {
            background-color: #f0f0f0;
            color: #000;
            font-weight: bold;
            padding: 8px;
            text-transform: uppercase;
            font-size: 9px;
            border: 1px solid #999;
        }
        td {
            border: 1px solid #999;
            padding: 6px; /* ඉඩ ඉතිරි කිරීමට Padding අඩු කළා */
            text-align: center;
            vertical-align: middle;
        }

        /* --- COLUMNS --- */
        .period-col {
            background-color: #fafafa;
            font-weight: bold;
            font-size: 12px;
            width: 50px;
        }

        /* --- CONTENT --- */
        .class-text {
            color: #006400; /* Green */
            font-weight: bold;
            font-size: 11px;
            display: block;
        }
        .subject-text {
            color: #00008B; /* Blue */
            font-weight: bold;
            font-size: 10px;
            display: block;
            margin-top: 2px;
        }
        .time-text {
            color: #666;
            font-size: 8px;
            margin-top: 4px;
            display: block;
            font-style: italic;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #aaa;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <div class="footer">
        Generated on {{ date('Y-m-d H:i') }} &bull; School Timetable System
    </div>

    <div class="header-container">
        <div class="main-title">
            Teacher Timetable <span class="badge">{{ $teacher->name }}</span>
        </div>
        <div class="sub-title">
            ID: {{ $teacher->short_code }} &bull; Academic Year {{ date('Y') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th width="19%">Monday</th>
                <th width="19%">Tuesday</th>
                <th width="19%">Wednesday</th>
                <th width="19%">Thursday</th>
                <th width="19%">Friday</th>
            </tr>
        </thead>
        <tbody>
            @for($p = 1; $p <= $maxPeriod; $p++)

                @php
                    // --- 1. Hide Empty Rows Logic ---
                    $hasClass = false;
                    for($day = 1; $day <= 5; $day++) {
                        if(isset($timetable[$day][$p])) {
                            $hasClass = true;
                            break;
                        }
                    }

                    // 2. Time Info
                    $timeInfo = $periodTimings[$p] ?? null;
                    $startTime = $timeInfo ? \Carbon\Carbon::parse($timeInfo->start_time)->format('h:i A') : '';
                    $endTime = $timeInfo ? \Carbon\Carbon::parse($timeInfo->end_time)->format('h:i A') : '';
                    $timeString = ($startTime && $endTime) ? "$startTime - $endTime" : '';
                @endphp

                @if($hasClass)
                <tr>
                    <td class="period-col">
                        {{ $p }}
                    </td>

                    @for($d = 1; $d <= 5; $d++)
                        @php
                            $entry = $timetable[$d][$p] ?? null;
                        @endphp

                        <td>
                            @if($entry)
                                <span class="class-text">
                                    {{ $entry->section->grade }}-{{ $entry->section->class_name }}
                                </span>
                                <span class="subject-text">
                                    {{ $entry->subject->name }}
                                </span>

                                @if($timeString)
                                    <span class="time-text">{{ $timeString }}</span>
                                @endif
                            @else
                                <span style="color:#eee;">-</span>
                            @endif
                        </td>
                    @endfor
                </tr>
                @endif

            @endfor
        </tbody>
    </table>

</body>
</html>
