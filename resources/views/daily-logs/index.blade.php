@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="flex">
            <h2>Daily Logs</h2>
            <div>
                <a href="{{ route('daily-logs.create') }}" class="btn">Create / Template</a>
                <a class="btn" href="{{ route('daily-logs.generate') }}">Generate Now</a>
            </div>
        </div>

        <form method="GET" style="margin:10px 0;">
            <select name="team_id">
                <option value="">All teams</option>
                @foreach($teams as $t)
                    <option value="{{ $t->id }}" @if(request('team_id') == $t->id) selected @endif>{{ $t->team_name }}</option>
                @endforeach
            </select>
            <input type="date" name="log_date" value="{{ request('log_date') }}">
            <button class="btn" type="submit">Filter</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Team</th>
                    <th>Leader</th>
                    <th>Member</th>
                    <th>Notes</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $l)
                    @php
                        $leader = DB::table('employee_tbl')->where('emp_id', $l->leader_emp_id)->first();
                        $member = DB::table('employee_tbl')->where('emp_id', $l->member_emp_id)->first();
                    @endphp
                    <tr>
                        <td>{{ $l->id }}</td>
                        <td>{{ $l->log_date }}</td>
                        <td>{{ $l->team?->team_name ?? '-' }}</td>
                        <td>{{ $leader?->emp_name ?? $l->leader_emp_id }}</td>
                        <td>{{ $member?->emp_name ?? $l->member_emp_id }}</td>
                        <td>{{ Str::limit($l->notes, 80) }}</td>
                        <td>
                            <form method="POST" action="{{ route('daily-logs.destroy', $l->id) }}">@csrf @method('DELETE')
                                <button class="btn danger" onclick="return confirm('Delete log?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top:12px;">{{ $logs->links() }}</div>
    </div>
@endsection