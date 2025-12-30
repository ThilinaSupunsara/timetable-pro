<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Teachers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="mb-4 text-center">
        <a href="{{ route('settings.index') }}" class="btn btn-secondary">Settings</a>
        <a href="{{ route('teachers.index') }}" class="btn btn-primary">Teachers</a>
        <a href="{{ route('subjects.index') }}" class="btn btn-secondary">Subjects</a>
        <a href="{{ route('sections.index') }}" class="btn btn-secondary">Classes</a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">Add Teacher</div>
                <div class="card-body">
                    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

                    <form action="{{ route('teachers.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label>Name</label>
                            <input name="name" class="form-control" placeholder="Mr. Kamal" required>
                        </div>
                        <div class="mb-3">
                            <label>Short Code</label>
                            <input name="short_code" class="form-control" placeholder="KML" required>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Teaches Subjects:</label>
                            <div class="border p-2 bg-white" style="max-height: 200px; overflow-y: auto;">
                                @if($subjects->isEmpty())
                                    <small class="text-danger">No subjects found. Please add subjects first.</small>
                                @else
                                    @foreach($subjects as $sub)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="subjects[]" value="{{ $sub->id }}" id="sub_{{ $sub->id }}">
                                            <label class="form-check-label" for="sub_{{ $sub->id }}">
                                                {{ $sub->name }} ({{ $sub->code }})
                                            </label>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <small class="text-muted">Select all subjects this teacher can teach.</small>
                        </div>

                        <button class="btn btn-primary w-100">Save Teacher</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <table class="table table-bordered bg-white table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Subjects Competency</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teachers as $t)
                    <tr>
                        <td>{{ $t->name }}</td>
                        <td>{{ $t->short_code }}</td>
                        <td>
                            @foreach($t->subjects as $sub)
                                <span class="badge bg-info text-dark">{{ $sub->code }}</span>
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
