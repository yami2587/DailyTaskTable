@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Edit Task</h2>

        <form method="POST" action="{{ route('tasks.update', $task->id) }}">
            @csrf @method('PUT')

            <label>Task Title</label>
            <input class="input-field" name="task_title" value="{{ old('task_title', $task->task_title) }}" required>

            <label>Description</label>
            <textarea class="input-field"
                name="task_description">{{ old('task_description', $task->task_description) }}</textarea>

            <label>Client (optional)</label>
            <select class="input-field" name="client_id">
                <option value="">-- none --</option>
                @foreach($clients as $c)
                    <option value="{{ $c->client_id }}" @if($task->client_id == $c->client_id) selected @endif>
                        {{ $c->client_company_name }}</option>
                @endforeach
            </select>

            <label>Assigned Team (optional)</label>
            <select class="input-field" name="assigned_team_id">
                <option value="">-- none --</option>
                @foreach($teams as $tm)
                    <option value="{{ $tm->id }}" @if($task->assigned_team_id == $tm->id) selected @endif>{{ $tm->team_name }}
                    </option>
                @endforeach
            </select>

            <label>Assigned Member (optional)</label>
            <select class="input-field" name="assigned_member_id">
                <option value="">-- none --</option>
                @foreach($employees as $e)
                    <option value="{{ $e->emp_id }}" @if($task->assigned_member_id == $e->emp_id) selected @endif>
                        {{ $e->emp_name }}</option>
                @endforeach
            </select>

            <label>Type</label>
            <select class="input-field" name="task_type">
                <option value="main" @if($task->task_type == 'main') selected @endif>Main</option>
                <option value="other" @if($task->task_type == 'other') selected @endif>Other</option>
            </select>

            <label>Status</label>
            <select class="input-field" name="status">
                <option value="pending" @if($task->status == 'pending') selected @endif>Pending</option>
                <option value="in-progress" @if($task->status == 'in-progress') selected @endif>In Progress</option>
                <option value="completed" @if($task->status == 'completed') selected @endif>Completed</option>
            </select>

            <label>Due Date</label>
            <input class="input-field" type="date" name="due_date" value="{{ old('due_date', $task->due_date) }}">

            <button class="btn" type="submit">Update Task</button>
        </form>
    </div>
@endsection