<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Master Timetable Overview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Table Scroll Setup */
        .table-container {
            max-height: 75vh;
            overflow: auto;
            position: relative;
            border: 1px solid #dee2e6;
            border-top: none; /* Tabs border connects */
        }

        /* Sticky Headers */
        .table thead th { position: sticky; top: 0; background: #212529; color: white; z-index: 5; }

        /* Sticky Teacher Column */
        .sticky-col {
            position: sticky;
            left: 0;
            background: white;
            z-index: 6; /* Higher than headers to stay on top when scrolling */
            border-right: 2px solid #dee2e6;
            min-width: 200px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }

        /* Cell Styling */
        .tt-cell {
            min-width: 120px;
            text-align: center;
            vertical-align: middle;
            padding: 4px !important;
            height: 1px; /* Trick to make inner div fill height */
        }

        /* Active Slot Design */
        .active-slot {
            background-color: #e3f2fd;
            border: 1px solid #9ec5fe;
            padding: 5px;
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
            min-height: 75px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        /* Typography */
        .class-code { font-weight: bold; color: #198754; font-size: 1.1em; display: block; }
        .subject-name { font-weight: bold; color: #0d6efd; font-size: 0.9em; display: block; margin: 3px 0; line-height: 1.2; }
        .time-text { font-size: 0.75em; color: #666; display: block; font-weight: 600; border-top: 1px dashed #cbd5e1; margin-top: 4px; padding-top: 2px;}

        /* Print Specific Styles */
        @media print {
            .no-print { display: none !important; }
            .table-container { max-height: none; overflow: visible; border: none; }
            .sticky-col { position: static; box-shadow: none; border-right: 1px solid #dee2e6; }
            body { font-size: 10pt; }

            /* Show ALL tabs when printing */
            .tab-content > .tab-pane {
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
                margin-bottom: 30px;
                page-break-after: always; /* New page for each day */
            }
            .nav-tabs { display: none !important; }

            /* Print Header for each day */
            .tab-pane::before {
                content: attr(data-day-name) " Schedule";
                display: block;
                font-size: 16pt;
                font-weight: bold;
                margin-bottom: 10px;
                text-decoration: underline;
                text-align: center;
            }
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid mt-3 px-3">

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm mb-2">&larr; Back to Tool Board</a>
            <h3>📋 Master Teacher Timetable</h3>
        </div>
        <a href="{{ route('timetable.download_master') }}" class="btn btn-danger">
        📄 Download PDF
        </a>
    </div>

    @php
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    @endphp

    <div class="card shadow">
        <div class="card-body p-3">

            <ul class="nav nav-tabs no-print" id="timetableTabs" role="tablist">
                @foreach($days as $key => $day)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $key == 0 ? 'active' : '' }} fw-bold"
                                id="tab-{{ $key }}"
                                data-bs-toggle="tab"
                                data-bs-target="#content-{{ $key }}"
                                type="button"
                                role="tab">
                            {{ $day }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content" id="timetableTabsContent">
                @foreach($days as $key => $day)
                    @php
                        $dayIndex = $key + 1; // 1=Mon, 2=Tue...
                    @endphp

                    <div class="tab-pane fade {{ $key == 0 ? 'show active' : '' }}"
                         id="content-{{ $key }}"
                         role="tabpanel"
                         data-day-name="{{ $day }}">

                        <div class="table-container">
                            <table class="table table-bordered mb-0 table-hover">
                                <thead>
                                    <tr>
                                        <th class="sticky-col bg-light text-dark text-center" style="width: 200px;">Teacher</th>

                                        @for($p = 1; $p <= $maxPeriod; $p++)
                                            <th class="text-center bg-dark text-white">
                                                P-{{ $p }}
                                            </th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($teachers as $teacher)
                                    <tr>
                                        <td class="sticky-col fw-bold text-primary">
                                            {{ $teacher->name }}
                                            <div style="font-size:0.75em; color:#666; font-weight:normal;">{{ $teacher->short_code }}</div>
                                        </td>

                                        @for($p = 1; $p <= $maxPeriod; $p++)

                                            @php
                                                // දත්ත ලබා ගැනීම
                                                $entry = $masterTable[$teacher->id][$dayIndex][$p] ?? null;

                                                // වෙලාව සකසා ගැනීම
                                                $timeInfo = $periodTimings[$p] ?? null;
                                                $startTime = $timeInfo ? \Carbon\Carbon::parse($timeInfo->start_time)->format('h:i') : '';
                                                $endTime = $timeInfo ? \Carbon\Carbon::parse($timeInfo->end_time)->format('h:i') : '';
                                                $timeString = ($startTime && $endTime) ? "$startTime - $endTime" : '';
                                            @endphp

                                            <td class="tt-cell">
                                                @if($entry)
                                                    <div class="active-slot">
                                                        <span class="class-code">{{ $entry->section->grade }}-{{ $entry->section->class_name }}</span>

                                                        <span class="subject-name">{{ $entry->subject->name }}</span>

                                                        @if($timeString)
                                                            <span class="time-text">{{ $timeString }}</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted" style="opacity:0.1; font-size: 20px;">&middot;</span>
                                                @endif
                                            </td>
                                        @endfor
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div> </div> @endforeach
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
