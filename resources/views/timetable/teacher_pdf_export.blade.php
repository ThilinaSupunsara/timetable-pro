<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Timetable</title>
    <style>
        /* --- PAGE SETUP --- */
        @page { margin: 1cm; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
        }

        /* --- HEADER --- */
        .header-container {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #198754;
            padding-bottom: 10px;
        }
        .main-title {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            color: #212529;
        }
        .sub-title {
            font-size: 14px;
            color: #555;
            margin-top: 5px;
        }
        .teacher-badge {
            background-color: #198754;
            color: white;
            padding: 3px 10px;
            border-radius: 4px;
            margin-left: 10px;
        }

        /* --- TABLE STYLE --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #212529;
            color: #fff;
            padding: 10px;
            text-transform: uppercase;
            font-size: 11px;
            border: 1px solid #000;
        }
        td {
            border: 1px solid #999;
            padding: 5px;
            text-align: center;
            vertical-align: middle;
            height: 65px;
        }

        /* --- CONTENT STYLES --- */
        .period-col {
            background-color: #f0f0f0;
            width: 8%;
            font-weight: bold;
            font-size: 16px;
        }

        .class-name {
            font-weight: bold;
            color: #198754; /* Green */
            font-size: 14px;
            display: block;
            margin-bottom: 2px;
        }
        .subject-name {
            font-weight: bold;
            color: #0d6efd; /* Blue */
            font-size: 12px;
            display: block;
            margin-bottom: 5px;
        }

        /* Time Badge Style */
        .time-badge {
            display: inline-block;
            background-color: #f8f9fa;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 10px;
            color: #555;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="header-container">
        <div class="main-title">
            Teacher Timetable <span class="teacher-badge">{{ $teacher->name }}</span>
        </div>
        <div class="sub-title">
            Teacher ID: {{ $teacher->short_code }} &bull; Year {{ date('Y') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Period</th>
                <th width="18.4%">Monday</th>
                <th width="18.4%">Tuesday</th>
                <th width="18.4%">Wednesday</th>
                <th width="18.4%">Thursday</th>
                <th width="18.4%">Friday</th>
            </tr>
        </thead>
        <tbody>
            @for($p = 1; $p <= $maxPeriod; $p++)

                @php
                    // --- 1. පේළි හංගන කේතය (Hide Empty Rows) ---
                    // සතියේ දින 5 ඇතුලත මේ පීරියඩ් එකේ එක පන්තියක් හරි තියෙනවද බලනවා.
                    // එහෙම නැත්නම් මේ පේළිය Print වෙන්නේ නෑ.
                    $hasClass = false;
                    for($day = 1; $day <= 5; $day++) {
                        if(isset($timetable[$day][$p])) {
                            $hasClass = true;
                            break;
                        }
                    }

                    // 2. වෙලාව සකසා ගැනීම
                    $timeInfo = $periodTimings[$p] ?? null;
                    $startTime = $timeInfo ? \Carbon\Carbon::parse($timeInfo->start_time)->format('h:i A') : '';
                    $endTime = $timeInfo ? \Carbon\Carbon::parse($timeInfo->end_time)->format('h:i A') : '';
                    $timeString = ($startTime && $endTime) ? "$startTime - $endTime" : '';
                @endphp

                {{-- පන්තියක් තිබේ නම් පමණක් Row එක පෙන්වයි --}}
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
                                <span class="class-name">
                                    {{ $entry->section->grade }} - {{ $entry->section->class_name }}
                                </span>
                                <span class="subject-name">
                                    {{ $entry->subject->name }}
                                </span>

                                @if($timeString)
                                    <span class="time-badge">🕒 {{ $timeString }}</span>
                                @endif
                            @else
                                @endif
                        </td>
                    @endfor
                </tr>
                @endif
                {{-- End Check --}}

            @endfor
        </tbody>
    </table>

</body>
</html>
