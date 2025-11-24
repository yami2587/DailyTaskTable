@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Create Task</h2>

        <form method="POST" action="{{ route('tasks.store') }}">
            @csrf
            <label>Task Title</label>
            <input class="input-field" name="task_title" required>

            <label>Description</label>
            <textarea class="input-field" name="task_description"></textarea>

            <label>Client (optional)</label>
            <select class="input-field" name="client_id">
                <option value="">-- none --</option>
                @foreach($clients as $c)
                    <option value="{{ $c->client_id }}">{{ $c->client_company_name }}</option>
                @endforeach
            </select>

            <label>Assigned Team (optional)</label>
            <select class="input-field" name="assigned_team_id">
                <option value="">-- none --</option>
                @foreach($teams as $tm)
                    <option value="{{ $tm->id }}">{{ $tm->team_name }}</option>
                @endforeach
            </select>

            <label>Assigned Member (optional)</label>
            <select class="input-field" name="assigned_member_id">
                <option value="">-- none --</option>
                @foreach($employees as $e)
                    <option value="{{ $e->emp_id }}">{{ $e->emp_name }}</option>
                @endforeach
            </select>

            <label>Type</label>
            <select class="input-field" name="task_type">
                <option value="main">Main</option>
                <option value="other">Other</option>
            </select>

            <label>Status</label>
            <select class="input-field" name="status">
                <option value="pending">Pending</option>
                <option value="in-progress">In Progress</option>
                <option value="completed">Completed</option>
            </select>

            <label>Due Date</label>
            <input class="input-field" type="date" name="due_date">

            <button class="btn" type="submit">Save Task</button>
        </form>
    </div>
@endsection