@extends('layouts.app')

@section('content')
<div class="card">
    <h2>Daily Log Template</h2>

    <form method="GET" action="{{ route('daily-logs.create') }}">
        <label>Select Team</label>
        <select class="input-field" name="team_id" onchange="this.form.submit()">
            <option value="">-- choose team --</option>
            @foreach($teams as $t)
                <option value="{{ $t->id }}" @if(optional($team)->id == $t->id) selected @endif>{{ $t->team_name }}</option>
            @endforeach
        </select>
        <noscript><button class="btn">Load</button></noscript>
    </form>

    <form method="POST" action="{{ route('daily-logs.store') }}">
        @csrf
        <input type="hidden" name="team_id" value="{{ optional($team)->id }}">
        <label>Date</label>
        <input class="input-field" type="date" name="log_date" value="{{ $date }}">

        <label>Leader</label>
        <input class="input-field" name="leader_emp_id" value="{{ $leader?->emp_id ?? '' }}">

        <label>Task (optional)</label>
        <select class="input-field" name="task_id">
            <option value="">-- none --</option>
            @foreach($tasks as $tk)
                <option value="{{ $tk->id }}">{{ $tk->task_title }}</option>
            @endforeach
        </select>

        <h3>Members (check who worked today)</h3>
        @if($members->count())
        @foreach($members as $m)
        @php($emp = DB::table('employee_tbl')->where('emp_id', $m->emp_id)->first())
            <label style="display:block;">
                <input type="checkbox" name="member_emp_id[]" value="{{ $m->emp_id }}" checked>
                {{ $emp?->emp_name ?? $m->emp_id }}
                @if($m->is_leader) <strong> (Leader)</strong> @endif
            </label>
            @endforeach
        @else
        <div>No members for selected team.</div>
        @endif

        <label>Notes (general working plan)</label>
        <textarea class="input-field" name="notes" placeholder="Today's working plan..."></textarea>

        <h3>Today's Targets (write here or create separately)</h3>
        <p>Use Targets page to add structured targets. This template stores members logs and notes.</p>

        <button class="btn" type="submit">Save Daily Log</button>
    </form>
</div>
@endsection