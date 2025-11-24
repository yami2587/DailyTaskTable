@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="flex">
            <h2>Teams</h2>
            <a href="{{ route('team.create') }}" class="btn">Create Team</a>
        </div>

        @if(session('success'))
        <div class="card">{{ session('success') }}</div> @endif

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Members</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($teams as $t)
                    <tr>
                        <td>{{ $t->id }}</td>
                        <td>{{ $t->team_name }}</td>
                        <td>{{ Str::limit($t->description, 80) }}</td>
                        <td><a class="btn" href="{{ route('team.members', $t->id) }}">Manage Members</a></td>
                        <td>
                            <a class="btn" href="{{ route('team.edit', $t->id) }}">Edit</a>
                            <form style="display:inline" method="POST" action="{{ route('team.destroy', $t->id) }}">
                                @csrf @method('DELETE')
                                <button class="btn danger" onclick="return confirm('Delete team?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top:12px;">{{ $teams->links() }}</div>
    </div>
@endsection