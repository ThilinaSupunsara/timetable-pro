<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Timetable Export</title>
    <style>
        /* --- PAGE SETUP --- */
        @page {
            margin: 0.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            background-color: #fff;
            margin: 20px;
        }

        /* --- HEADER DESIGN --- */
        .header-container {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #0d6efd;
        }
        .main-title {
            font-size: 26px;
            font-weight: 800;
            color: #212529;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        .sub-title {
            font-size: 16px;
            color: #6c757d;
            margin-top: 8px;
            font-weight: normal;
        }
        .tag {
            background-color: #0d6efd;
            color: white;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            margin-left: 10px;
        }

        /* --- TABLE DESIGN --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #212529;
            color: #fff;
            padding: 12px 5px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 1px solid #212529;
        }
        td {
            border: 1px solid #dee2e6;
            padding: 12px 5px;
            text-align: center;
            vertical-align: middle;
        }

        /* Time Column */
        .time-col {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: bold;
            font-size: 11px;
            width: 13%;
        }

        /* Break/Interval Row */
        .break-row td {
            background-color: #fff3cd;
            color: #856404;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 12px;
            border: 1px solid #ffeeba;
        }

        /* Subject & Teacher Styling */
        .subject {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 4px;
        }
        .teacher {
            display: block;
            font-size: 10px;
            color: #6c757d;
            font-style: italic;
        }
        .empty {
            color: #e9ecef;
            font-size: 20px;
        }
    </style>
</head>
<body>

    <div class="header-container">
        <div class="main-title">
            Class Timetable
            <span class="tag">{{ $selectedSection->grade }} - {{ $selectedSection->class_name }}</span>
        </div>
        <div class="sub-title">
            Academic Year {{ date('Y') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%">Time</th>
                <th style="width: 17.6%">Monday</th>
                <th style="width: 17.6%">Tuesday</th>
                <th style="width: 17.6%">Wednesday</th>
                <th style="width: 17.6%">Thursday</th>
                <th style="width: 17.6%">Friday</th>
            </tr>
        </thead>
        <tbody>
            @foreach($timings as $slot)

                @php
                    // --- ROW HIDING LOGIC ---
                    $showRow = false;

                    // 1. Break එකක් නම් අනිවාර්යයෙන් පෙන්වන්න
                    if($slot->is_break) {
                        $showRow = true;
                    }
                    // 2. නැත්නම්, දවස් 5 ඇතුලත පන්තියක් තියෙනවද බලන්න
                    else {
                        for($d = 1; $d <= 5; $d++) {
                            if(isset($timetable[$d][$slot->period_number])) {
                                $showRow = true;
                                break;
                            }
                        }
                    }
                @endphp

                @if($showRow)
                    @if($slot->is_break)
                        <tr class="break-row">
                            <td>
                                {{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }}<br>
                                {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}
                            </td>
                            <td colspan="5">{{ strtoupper($slot->label) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td class="time-col">
                                {{ \Carbon\Carbon::parse($slot->start_time)->format('h:i') }} <span style="font-size:9px; color:#aaa;">AM</span><br>
                                <span style="color:#ccc; font-size: 10px;">to</span><br>
                                {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i') }} <span style="font-size:9px; color:#aaa;">PM</span>
                            </td>

                            @for($day=1; $day<=5; $day++)
                                @php $entry = $timetable[$day][$slot->period_number] ?? null; @endphp

                                <td>
                                    @if($entry)
                                        <span class="subject">{{ $entry->subject->name }}</span>
                                        @if($entry->teacher)
                                            <span class="teacher">{{ $entry->teacher->name }}</span>
                                        @endif
                                    @else
                                        <span class="empty">&minus;</span>
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @endif
                @endif

            @endforeach
        </tbody>
    </table>

</body>
</html>
