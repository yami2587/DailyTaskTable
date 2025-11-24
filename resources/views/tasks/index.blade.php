@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="flex">
            <h2>Tasks</h2>
            <a href="{{ route('tasks.create') }}" class="btn">Create Task</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Team</th>
                    <th>Status</th>
                    <th>Due</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tasks as $t)
                    <tr>
                        <td>{{ $t->id }}</td>
                        <td>{{ $t->task_title }}</td>
                        <td>{{ $t->team?->team_name ?? '-' }}</td>
                        <td>{{ $t->status }}</td>
                        <td>{{ $t->due_date }}</td>
                        <td>
                            <a class="btn" href="{{ route('tasks.edit', $t->id) }}">Edit</a>
                            <form style="display:inline" method="POST" action="{{ route('tasks.destroy', $t->id) }}">
                                @csrf @method('DELETE')
                                <button class="btn danger" onclick="return confirm('Delete task?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top:12px;">{{ $tasks->links() }}</div>
    </div>
@endsection