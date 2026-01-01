<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Timetable System')</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #6366f1; /* Indigo */
            --bg-body: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar Design */
        .navbar {
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 0.8rem 0;
        }
        .navbar-brand {
            font-weight: 800;
            color: #0f172a !important;
            font-size: 1.2rem;
        }
        .nav-link {
            font-weight: 600;
            color: #64748b;
            margin-right: 1rem;
            transition: 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--primary-color);
        }

        /* Main Content Area */
        main {
            flex: 1; /* Pushes footer down */
        }

        /* Footer */
        footer {
            background: #fff;
            border-top: 1px solid #f1f5f9;
            padding: 1.5rem 0;
            margin-top: auto;
        }

        /* Common Components */
        .card-modern {
            border: none; border-radius: 16px;
            background: #fff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .btn-primary {
            background-color: var(--primary-color); border: none;
            padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600;
        }
        .btn-primary:hover { background-color: #4f46e5; }
    </style>

    @stack('styles')
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
                <i class="bi bi-grid-1x2-fill text-primary me-2"></i>
                Timetable<span class="text-primary">Master</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->is('settings*') || request()->is('subjects*') || request()->is('teachers*') || request()->is('sections*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            Data Setup
                        </a>
                        <ul class="dropdown-menu border-0 shadow-sm rounded-3">
                            <li><a class="dropdown-item py-2" href="{{ route('settings.index') }}"><i class="bi bi-gear me-2 text-secondary"></i>General Settings</a></li>
                            <li><a class="dropdown-item py-2" href="{{ route('subjects.index') }}"><i class="bi bi-book me-2 text-secondary"></i>Subjects</a></li>
                            <li><a class="dropdown-item py-2" href="{{ route('teachers.index') }}"><i class="bi bi-person-badge me-2 text-secondary"></i>Teachers</a></li>
                            <li><a class="dropdown-item py-2" href="{{ route('sections.index') }}"><i class="bi bi-grid me-2 text-secondary"></i>Classes</a></li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('allocations*') ? 'active' : '' }}" href="{{ route('allocations.index') }}">Allocations</a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->is('timetable*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                            Timetables
                        </a>
                        <ul class="dropdown-menu border-0 shadow-sm rounded-3">
                            <li><a class="dropdown-item py-2" href="{{ route('timetable.view') }}">Class Timetables</a></li>
                            <li><a class="dropdown-item py-2" href="{{ route('timetable.teacher') }}">Teacher Timetables</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 fw-bold text-primary" href="{{ route('timetable.master') }}">Master Overview</a></li>
                        </ul>
                    </li>
                </ul>


            </div>
        </div>
    </nav>

    <main class="py-4">
        @yield('content')
    </main>

    <footer class="text-center text-muted">
        <div class="container">
            <small class="fw-bold">&copy; {{ date('Y') }} TimetableMaster System</small>
            <div class="small opacity-75 mt-1">Version 1.0.0</div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true
        });

        @if(session('success'))
            Toast.fire({ icon: 'success', title: "{{ session('success') }}" });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error', title: 'Error', html: "{!! session('error') !!}", confirmButtonColor: '#1e293b'
            });
        @endif
    </script>

    @stack('scripts')
</body>
</html>
