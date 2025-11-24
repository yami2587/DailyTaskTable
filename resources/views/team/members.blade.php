@extends('layouts.app')

@section('content')
<div class="card">
    <h2>Members for: {{ $team->team_name }}</h2>

    <h3>Add member</h3>
    <form method="POST" action="{{ route('team.members.add', $team->id) }}">
        @csrf
        <label>Select Employee</label>
        <select class="input-field" name="emp_id" required>
            <option value="">-- choose --</option>
            @foreach($employees as $e)
                <option value="{{ $e->emp_id }}">{{ $e->emp_name }} ({{ $e->emp_id }})</option>
            @endforeach
        </select>

        <label>
            <input type="checkbox" name="is_leader" value="1"> Set as leader
        </label>

        <button class="btn" type="submit">Add</button>
    </form>

    <h3 style="margin-top:18px;">Current Members</h3>
    <table>
        <thead>
            <tr>
                <th>Emp ID</th>
                <th>Name</th>
                <th>Leader</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($members as $m)
            @php($emp = \Illuminate\Support\Facades\DB::table('employee_tbl')->where('emp_id', $m->emp_id)->first())
            <tr>
                <td>{{ $m->emp_id }}</td>
                <td>{{ $emp?->emp_name ?? 'Unknown' }}</td>
                <td>{{ $m->is_leader ? 'Yes' : '-' }}</td>
                <td>
                    <form method="POST"
                        action="{{ route('team.members.remove', ['team' => $team->id, 'member' => $m->id]) }}">
                        @csrf @method('DELETE')
                        <button class="btn danger" onclick="return confirm('Remove member?')">Remove</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection