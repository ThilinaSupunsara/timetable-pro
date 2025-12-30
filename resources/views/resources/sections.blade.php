<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Classes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="mb-4 text-center">
        <a href="{{ route('settings.index') }}" class="btn btn-secondary">Settings</a>
        <a href="{{ route('teachers.index') }}" class="btn btn-secondary">Teachers</a>
        <a href="{{ route('subjects.index') }}" class="btn btn-secondary">Subjects</a>
        <a href="{{ route('sections.index') }}" class="btn btn-primary">Classes</a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">Add Class (Section)</div>
                <div class="card-body">

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('sections.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label>Time Category</label>
                            <select name="class_category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">This decides the start/end times.</small>
                        </div>

                        <div class="mb-3">
                            <label>Grade</label>
                            <select name="grade" class="form-select" required>
                                @for($i=1; $i<=13; $i++)
                                    <option value="{{ $i }}">Grade {{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Class Name</label>
                            <input name="class_name" class="form-control" placeholder="A, B, C..." required>
                        </div>
                        <button class="btn btn-primary w-100">Save Class</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <table class="table table-bordered bg-white">
                <thead><tr><th>Full Name</th><th>Category</th><th>Grade</th></tr></thead>
                <tbody>
                    @foreach($sections as $sec)
                    <tr>
                        <td><strong>{{ $sec->grade }} - {{ $sec->class_name }}</strong></td>
                        <td><span class="badge bg-info text-dark">{{ $sec->classCategory->name }}</span></td>
                        <td>{{ $sec->grade }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
