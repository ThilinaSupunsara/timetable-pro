<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subjects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="mb-4 text-center">
        <a href="{{ route('settings.index') }}" class="btn btn-secondary">Settings</a>
        <a href="{{ route('teachers.index') }}" class="btn btn-secondary">Teachers</a>
        <a href="{{ route('subjects.index') }}" class="btn btn-primary">Subjects</a>
        <a href="{{ route('sections.index') }}" class="btn btn-secondary">Classes</a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">Add Subject</div>
                <div class="card-body">
                    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
                    <form action="{{ route('subjects.store') }}" method="POST">
                        @csrf
                        <div class="mb-3"><label>Name</label><input name="name" class="form-control" placeholder="Mathematics" required></div>
                        <div class="mb-3"><label>Code</label><input name="code" class="form-control" placeholder="MAT" required></div>
                        <button class="btn btn-primary w-100">Save</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <table class="table table-bordered bg-white">
                <thead><tr><th>Name</th><th>Code</th></tr></thead>
                <tbody>
                    @foreach($subjects as $sub)
                    <tr><td>{{ $sub->name }}</td><td>{{ $sub->code }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
