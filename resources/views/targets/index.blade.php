@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="flex">
            <h2>Today Targets</h2>
            <a href="{{ route('targets.create') }}" class="btn">Create Target</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Task</th>
                    <th>Date</th>
                    <th>Done</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($targets as $tg)
                    <tr>
                        <td>{{ $tg->id }}</td>
                        <td>{{ $tg->title }}</td>
                        <td>{{ $tg->task?->task_title ?? '-' }}</td>
                        <td>{{ $tg->target_date }}</td>
                        <td>{{ $tg->is_done ? 'Yes' : 'No' }}</td>
                        <td>
                            <a class="btn" href="{{ route('targets.edit', $tg->id) }}">Edit</a>
                            <form method="POST" style="display:inline" action="{{ route('targets.destroy', $tg->id) }}">
                                @csrf @method('DELETE')
                                <button class="btn danger" onclick="return confirm('Delete target?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top:12px;">{{ $targets->links() }}</div>
    </div>
@endsection