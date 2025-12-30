<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School Timetable Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📅 System Settings</h2>
        <a href="#" class="btn btn-secondary disabled">Teachers (Next)</a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">1. Create Class Category</div>
                <div class="card-body">
                    <form action="{{ route('settings.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label>Category Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Ex: Primary (Grade 1-5)" required>
                        </div>
                        <div class="mb-3">
                            <label>Description (Optional)</label>
                            <input type="text" name="description" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Create & Setup Times</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Existing Categories</div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $cat)
                            <tr>
                                <td><strong>{{ $cat->name }}</strong></td>
                                <td>{{ $cat->description }}</td>
                                <td>
                                    <a href="{{ route('settings.manage', $cat->id) }}" class="btn btn-warning btn-sm">
                                        ⚙️ Configure Timings
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
