@extends('layouts.app')

@section('content')
<div class="card">
    <h2>Daily Sheet — {{ $date }}</h2>

    @if(session('success')) <div class="card">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="card" style="background:#fdd">{{ session('error') }}</div> @endif

    <div style="display:flex; gap:18px;">
        <!-- LEFT: Big Working Plan area -->
        <div style="flex:1;">
            <div class="card">
                <h3>Working Plan</h3>

                {{-- If leaderSheets present (leader view), show first team sheet ; otherwise show member's sheet if any --}}
                @php
                    $sheet = $leaderSheets->first() ?? null;
                @endphp

                @if($sheet)
                    <form method="POST" action="{{ route('tasktable.sheet.create') }}">
                        @csrf
                        <input type="hidden" name="team_id" value="{{ $sheet->team_id }}">
                        <input type="hidden" name="sheet_date" value="{{ $date }}">
                        <label>Leader emp id</label>
                        <input class="input-field" name="leader_emp_id" value="{{ $sheet->leader_emp_id ?? $empId }}">
                        <label>Working Plan</label>
                        <textarea class="input-field" name="working_plan">{{ old('working_plan', $sheet->working_plan) }}</textarea>
                        @if($sheet->sheet_date == \Carbon\Carbon::today()->toDateString())
                            <button class="btn" type="submit">Save Working Plan</button>
                        @else
                            <div>Past record — read only</div>
                        @endif
                    </form>
                @else
                    <div>No sheet found for your leader teams on this date.</div>
                @endif
            </div>

            <div class="card">
                <h3>Your Assignments (as employee id: {{ $empId }})</h3>
                @if($memberAssignments->count())
                    @foreach($memberAssignments as $a)
                        <div style="border:1px solid #eee;padding:10px;margin-bottom:8px;">
                            <strong>Task:</strong> {{ $a->task?->task_title ?? '- (no task)' }} <br>
                            <strong>Team:</strong> {{ $a->sheet->team->team_name ?? '-' }} <br>
                            <strong>Leader remark:</strong> {{ $a->leader_remark ?? '-' }} <br>
                            <strong>Your update:</strong> {{ $a->member_update ?? '-' }} <br>
                            <strong>Completed:</strong> {{ $a->is_completed ? 'Yes at '.$a->completed_at : 'No' }} <br>

                            {{-- if dated today allow updates --}}
                            @if($a->sheet->sheet_date->isToday())
                                <form method="POST" action="{{ route('tasktable.assignment.update', $a->id) }}">
                                    @csrf
                                    <textarea name="member_update" class="input-field" placeholder="Write progress...">{{ $a->member_update }}</textarea>
                                    <button class="btn" type="submit">Save Progress</button>
                                </form>

                                @if(!$a->is_completed)
                                    <form method="POST" action="{{ route('tasktable.assignment.complete', $a->id) }}" style="margin-top:6px;">
                                        @csrf
                                        <button class="btn" type="submit">Mark Completed</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    @endforeach
                @else
                    <div>No assignments for this date.</div>
                @endif
            </div>
        </div>

        <!-- RIGHT: Leader / Members list / assign controls (sheet view) -->
        <div style="width:420px;">
            <div class="card">
                <h3>Team & Members</h3>

                @if($leaderSheets->count())
                    @foreach($leaderSheets as $sheet)
                        <div style="margin-bottom:10px;">
                            <strong>Team: </strong> {{ $sheet->team->team_name }} <br>
                            <strong>Leader: </strong> {{ DB::table('employee_tbl')->where('emp_id', $sheet->leader_emp_id)->value('emp_name') ?? $sheet->leader_emp_id }} <br>
                            <small>Sheet date: {{ $sheet->sheet_date }}</small>

                            <hr>
                            <h4>Assign tasks to members</h4>

                            {{-- list team members --}}
                            @php
                                $members = \App\Models\TeamMember::where('team_id', $sheet->team_id)->get();
                            @endphp

                            @foreach($members as $m)
                                @php($emp = DB::table('employee_tbl')->where('emp_id', $m->emp_id)->first())
                                <div style="border:1px dashed #ddd;padding:8px;margin-bottom:8px;">
                                    <strong>{{ $emp?->emp_name ?? $m->emp_id }}</strong>
                                    @if($m->is_leader) <span style="color:#2c3e50"> (Leader)</span> @endif

                                    {{-- Existing assignments for this member on this sheet --}}
                                    @php
                                        $existing = $sheet->assignments->where('member_emp_id',$m->emp_id);
                                    @endphp

                                    <div>
                                        @foreach($existing as $ex)
                                            <div style="padding:6px;background:#fafafa;margin-bottom:6px;">
                                                <small>Task: {{ $ex->task?->task_title ?? '-' }}</small><br>
                                                <small>Leader remark: {{ $ex->leader_remark }}</small><br>
                                                <small>Member update: {{ $ex->member_update }}</small>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- assign form (leader only) --}}
                                    <form method="POST" action="{{ route('tasktable.assign') }}">
                                        @csrf
                                        <input type="hidden" name="sheet_id" value="{{ $sheet->id }}">
                                        <input type="hidden" name="member_emp_id" value="{{ $m->emp_id }}">
                                        <input type="hidden" name="assigned_by_emp_id" value="{{ $empId }}">
                                        <label>Task</label>
                                        <select name="task_id" class="input-field">
                                            <option value="">-- none --</option>
                                            @foreach($tasks as $t)
                                                <option value="{{ $t->id }}">{{ $t->task_title }}</option>
                                            @endforeach
                                        </select>

                                        <label>Remark (leader)</label>
                                        <input class="input-field" name="leader_remark" placeholder="Short remark">

                                        @if($sheet->sheet_date == \Carbon\Carbon::today()->toDateString())
                                            <button class="btn" type="submit">Assign to {{ $emp?->emp_name ?? $m->emp_id }}</button>
                                        @else
                                            <div>Past date — read only</div>
                                        @endif
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                @else
                    <div>No sheet available for leader teams on this date. You can create one below.</div>

                    {{-- allow creating a new sheet if leader --}}
                    <form method="POST" action="{{ route('tasktable.sheet.create') }}">
                        @csrf
                        <label>Team</label>
                        <select name="team_id" class="input-field">
                            @foreach(\App\Models\Team::whereIn('id',$leaderTeams)->get() as $tm)
                                <option value="{{ $tm->id }}">{{ $tm->team_name }}</option>
                            @endforeach
                        </select>
                        <label>Leader emp id</label>
                        <input class="input-field" name="leader_emp_id" value="{{ $empId }}">
                        <label>Date</label>
                        <input class="input-field" type="date" name="sheet_date" value="{{ $date }}">
                        <label>Working plan</label>
                        <textarea class="input-field" name="working_plan"></textarea>

                        <button class="btn" type="submit">Create Sheet</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
