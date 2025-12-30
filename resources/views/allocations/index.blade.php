<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Class for Workload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="mb-4 text-center">
        <a href="{{ route('settings.index') }}" class="btn btn-secondary">Settings</a>
        <a href="{{ route('teachers.index') }}" class="btn btn-secondary">Teachers</a>
        <a href="{{ route('subjects.index') }}" class="btn btn-secondary">Subjects</a>
        <a href="{{ route('allocations.index') }}" class="btn btn-primary">Allocations</a>
    </div>

    <h3 class="text-center mb-4">Select a Class to Assign Workload</h3>

    <div class="row justify-content-center">
        @foreach($categories as $category)
        <div class="col-md-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    {{ $category->name }} Classes
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @forelse($category->sections as $section)
                            <a href="{{ route('allocations.manage', $section->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <strong>{{ $section->grade }} - {{ $section->class_name }}</strong>
                                <span class="badge bg-primary rounded-pill">Manage &rarr;</span>
                            </a>
                        @empty
                            <p class="text-muted text-center my-2">No classes found.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
</body>
</html>
