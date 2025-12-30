<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tool Board - Timetable System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .tool-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            height: 100%;
        }
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
            background-color: #f8f9fa;
        }
        .icon-box { font-size: 2.5em; margin-bottom: 10px; }
        .section-title {
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #555;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
            letter-spacing: 1px;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/">🏫 School Timetable Tool Board</a>
    </div>
</nav>

<div class="container pb-5">

    <div class="row mb-5 text-center">
        <div class="col-md-4">
            <div class="bg-white p-3 rounded shadow-sm border-start border-5 border-primary">
                <h3 class="fw-bold mb-0">{{ $counts['teachers'] }}</h3>
                <small class="text-muted">Teachers</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bg-white p-3 rounded shadow-sm border-start border-5 border-success">
                <h3 class="fw-bold mb-0">{{ $counts['sections'] }}</h3>
                <small class="text-muted">Classes</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bg-white p-3 rounded shadow-sm border-start border-5 border-warning">
                <h3 class="fw-bold mb-0">{{ $counts['subjects'] }}</h3>
                <small class="text-muted">Subjects</small>
            </div>
        </div>
    </div>

    @if(session('warning_list'))
        <div class="alert alert-warning shadow-sm border-warning mb-5">
            <h4 class="alert-heading">⚠️ Conflict Report</h4>
            <p>The system generated the timetable, but the following subjects could not be assigned due to teacher unavailability:</p>
            <hr>
            <ul class="mb-0 text-danger fw-bold" style="max-height: 200px; overflow-y: auto;">
                @foreach(session('warning_list') as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            <div class="mt-3">
                <small class="text-muted">Tip: Go to "Master Overview" to check teacher schedules or "Workload Allocation" to reduce periods.</small>
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success text-center mb-4">
            {{ session('success') }}
        </div>
    @endif

    <h6 class="section-title">Phase 1: Setup & Configuration</h6>
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <a href="{{ route('settings.index') }}" class="card tool-card shadow-sm text-center p-4">
                <div class="icon-box">⚙️</div>
                <h5 class="fw-bold">General Settings</h5>
                <small class="text-muted">Set Class Categories (Primary/Senior) & Time Slots.</small>
            </a>
        </div>
    </div>

    <h6 class="section-title">Phase 2: Resources Data</h6>
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <a href="{{ route('subjects.index') }}" class="card tool-card shadow-sm text-center p-4">
                <div class="icon-box">📚</div>
                <h5 class="fw-bold">Manage Subjects</h5>
                <small class="text-muted">Add school subjects (Maths, Science...)</small>
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="{{ route('teachers.index') }}" class="card tool-card shadow-sm text-center p-4">
                <div class="icon-box">👨‍🏫</div>
                <h5 class="fw-bold">Manage Teachers</h5>
                <small class="text-muted">Add teachers & assign their competencies.</small>
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="{{ route('sections.index') }}" class="card tool-card shadow-sm text-center p-4">
                <div class="icon-box">🏫</div>
                <h5 class="fw-bold">Manage Classes</h5>
                <small class="text-muted">Create Class Sections (6-A, 10-B...).</small>
            </a>
        </div>
    </div>

    <h6 class="section-title">Phase 3: Workload & Generation</h6>
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <a href="{{ route('allocations.index') }}" class="card tool-card shadow-sm text-center p-4 border-primary">
                <div class="icon-box">📝</div>
                <h5 class="fw-bold text-primary">Workload Allocation</h5>
                <small class="text-muted">Assign subjects to classes (Periods, Double/Triple Blocks, Fixed Slots).</small>
            </a>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card shadow-sm text-center p-4 border-success bg-white">
                <div class="icon-box">⚡</div>
                <h5 class="fw-bold text-success">Auto Generate All</h5>
                <small class="text-muted mb-3 d-block">Create schedule for the ENTIRE school at once.</small>

                <form action="{{ route('timetable.generate_all') }}" method="POST" id="generateMasterForm">
                    @csrf
                    <button type="button" class="btn btn-success w-100 fw-bold" onclick="confirmGenerate(event)">
                        🚀 Generate Master Timetable
                    </button>
                </form>
            </div>
        </div>
    </div>

    <h6 class="section-title">Phase 4: Timetable Views & Printing</h6>
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <a href="{{ route('timetable.view') }}" class="card tool-card shadow-sm text-center p-4">
                <div class="icon-box">📅</div>
                <h5 class="fw-bold">Class Timetable</h5>
                <small class="text-muted">View & Print schedule for specific classes.</small>
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="{{ route('timetable.teacher') }}" class="card tool-card shadow-sm text-center p-4">
                <div class="icon-box">👤</div>
                <h5 class="fw-bold">Teacher Timetable</h5>
                <small class="text-muted">Individual schedule for each teacher.</small>
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="{{ route('timetable.master') }}" class="card tool-card shadow-sm text-center p-4">
                <div class="icon-box">📋</div>
                <h5 class="fw-bold">Master Overview</h5>
                <small class="text-muted">See all teachers' schedules in one big grid.</small>
            </a>
        </div>
    </div>

</div>

<footer class="text-center text-muted py-3">
    <small>&copy; {{ date('Y') }} School Timetable System | v1.0 Production Build</small>
</footer>

</body>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // 1. GLOBAL FLASH MESSAGES (Session එකෙන් එන මැසේජ් අල්ලගන්නවා)

    // Success Message එකක් ආවොත්
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Great Job!',
            text: "{{ session('success') }}",
            showConfirmButton: false,
            timer: 3000, // තත්පර 3කින් ඉබේ මැකෙනවා
            timerProgressBar: true
        });
    @endif

    // Error Message එකක් ආවොත්
    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            html: "{!! session('error') !!}", // HTML Tags තිබුනොත් වැඩ කරන්න
            
        });
    @endif

    // 2. GENERATE ALL CONFIRMATION BUTTON
    function confirmGenerate(event) {
        event.preventDefault(); // බොත්තම එබුවම Form එක Submit වෙන එක නවත්වනවා

        Swal.fire({
            title: 'Are you sure?',
            text: "This will DELETE all existing timetables and create fresh ones! This process cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754', // Green Color
            cancelButtonColor: '#d33',     // Red Color
            confirmButtonText: 'Yes, Generate it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // User "Yes" කිව්වොත් විතරක් Form එක යවනවා
                // අපි Form එක Submit කිරීමට පෙර Loading එකක් පෙන්නමු
                Swal.fire({
                    title: 'Generating...',
                    text: 'Please wait while the algorithm runs.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Form එක Submit කිරීම
                document.getElementById('generateMasterForm').submit();
            }
        });
    }
</script>
</html>
