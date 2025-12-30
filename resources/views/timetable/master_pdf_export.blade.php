<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Master Timetable</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9px; } /* අකුරු පොඩි කළා ඉඩ මදි නිසා */

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #555; padding: 4px; text-align: center; vertical-align: middle; }

        th { background-color: #2c3e50; color: white; text-transform: uppercase; font-size: 8px; }

        /* Teacher Column Style */
        .teacher-col { text-align: left; font-weight: bold; width: 120px; background-color: #f8f9fa; }

        /* Cell Content Styles */
        .class-code { color: #198754; font-weight: bold; display: block; font-size: 9px; }
        .subject-name { color: #0d6efd; display: block; font-size: 8px; margin-top: 2px; }
        .time-text { color: #666; display: block; font-size: 7px; margin-top: 2px; }

        /* Page Break Logic */
        .page-break { page-break-after: always; }

        .header { text-align: center; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 5px; }
        .day-title { font-size: 18px; font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body>

    @foreach($days as $key => $dayName)
        @php $dayIndex = $key + 1; @endphp

        <div class="header">
            <div class="day-title">{{ $dayName }} Schedule</div>
            <div>Master Timetable &bull; {{ date('Y') }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="teacher-col">Teacher</th>
                    @for($p = 1; $p <= $maxPeriod; $p++)
                        <th>P-{{ $p }}</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @foreach($teachers as $teacher)
                    <tr>
                        <td class="teacher-col">
                            {{ $teacher->name }}
                            <br><span style="font-weight:normal; font-size:7px;">({{ $teacher->short_code }})</span>
                        </td>

                        @for($p = 1; $p <= $maxPeriod; $p++)
                            @php
                                $entry = $masterTable[$teacher->id][$dayIndex][$p] ?? null;

                                // Time Calculation
                                $timeInfo = $periodTimings[$p] ?? null;
                                $startTime = $timeInfo ? \Carbon\Carbon::parse($timeInfo->start_time)->format('h:i') : '';
                                $endTime = $timeInfo ? \Carbon\Carbon::parse($timeInfo->end_time)->format('h:i') : '';
                                $timeString = ($startTime && $endTime) ? "$startTime-$endTime" : '';
                            @endphp

                            <td>
                                @if($entry)
                                    <span class="class-code">{{ $entry->section->grade }}-{{ $entry->section->class_name }}</span>
                                    <span class="subject-name">{{ $entry->subject->name }}</span>
                                    @if($timeString)
                                        <span class="time-text">{{ $timeString }}</span>
                                    @endif
                                @else
                                    @endif
                            </td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif

    @endforeach

</body>
</html>
