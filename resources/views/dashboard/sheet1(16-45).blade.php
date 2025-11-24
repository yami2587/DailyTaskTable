@extends('layouts.app')

@section('content')
@php
    // Guarantee $primarySheet always exists
    $primarySheet = $primarySheet
        ?? ($sheet ?? null)
        ?? ($leaderSheets->first() ?? null)
        ?? ($memberAssignments->first()->sheet ?? null);

    if ($primarySheet && is_string($primarySheet->sheet_date)) {
        try {
            $primarySheet->sheet_date = \Carbon\Carbon::parse($primarySheet->sheet_date);
        } catch (\Exception $e) {
            $primarySheet->sheet_date = null;
        }
    }
@endphp

@php
    // Controller should pass: $empId, $employeeName, $date, $isLeader (bool), $sheet (TeamDailySheet model or null),
    // $members (collection of team members) - only for leader,
    // $assignments (collection) - for member view (assignments belonging to that emp for the date),
    // $clients (collection of client_id, client_company_name)
    //
    // This blade is defensive: will not fail if some variables are missing.
    $empId = $empId ?? session('emp_id') ?? null;
    $employeeName = $employeeName ?? session('emp_name') ?? null;
    $date = $date ?? now()->toDateString();
    $isLeader = $isLeader ?? false;
    $sheet = $sheet ?? null;
    $clients = $clients ?? collect();
    $members = $members ?? collect();
    $assignments = $assignments ?? collect();
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
.container-sheet { display:flex; gap:20px; margin-top:18px; align-items:flex-start; }
.left { flex:2; background:#fff; border:1px solid #ddd; padding:16px; box-sizing:border-box; }
.right { flex:1; background:#fff; border:1px solid #ddd; padding:16px; box-sizing:border-box; }
.section-title { font-weight:700; margin-bottom:8px; border-bottom:2px solid #000; padding-bottom:6px; }
.member-block { background:#fafafa; border:1px solid #e5e5e5; padding:12px; margin-bottom:14px; }
.task-row { background:#fff; border:1px solid #e8e8e8; padding:10px; margin-bottom:8px; }
.small { font-size:13px; color:#444; }
.label { font-weight:600; margin-bottom:6px; display:block; }
.input, textarea, select { width:100%; padding:8px; border:1px solid #cfcfcf; border-radius:4px; box-sizing:border-box; margin-top:6px; margin-bottom:8px; }
.btn { background:#007bff; color:#fff; padding:7px 12px; border-radius:4px; text-decoration:none; border:none; cursor:pointer; }
.btn.alt { background:#6c757d; }
.btn.danger { background:#dc3545; }
.btn.success { background:#28a745; }
.table-rows { border-collapse:collapse; width:100%; margin-top:8px;}
.table-rows td, .table-rows th { padding:6px; border:1px solid #e9e9e9; vertical-align:middle; }
.note { font-size:13px; color:#666; margin-top:6px; }
</style>

<div>
    <h2>Daily Task Sheet</h2>
    <div class="small">Logged as: {{ $employeeName ?? $empId ?? 'Unknown' }}</div>

    <form method="GET" action="{{ route('tasktable') }}" style="margin-top:8px;">
        <input type="hidden" name="emp_id" value="{{ $empId }}">
        <label class="small">Date</label>
        <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="input" style="width:200px;">
    </form>
</div>

<div class="container-sheet">

    <!-- LEFT: Assignments -->
    <div class="left">
        <div class="section-title">Assignments</div>

        {{-- No sheet yet --}}
        @if(!$sheet)
            <div class="note">No sheet for this date.</div>

            @if($isLeader)
                <form method="POST" action="{{ route('sheet.create') }}" style="margin-top:12px; max-width:480px;">
                    @csrf
                    <label class="label">Team</label>
                    <select name="team_id" class="input" required>
                        {{-- Leader's teams should be provided by controller via $members collection or separate --}}
                        @php
                            // if members exist, use their team_id
                            $teamIds = $members->pluck('team_id')->unique()->values();
                        @endphp
                        @foreach($teamIds as $tid)
                            <option value="{{ $tid }}">Team {{ $tid }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="leader_emp_id" value="{{ $empId }}">
                    <input type="hidden" name="sheet_date" value="{{ $date }}">
                    <button class="btn" type="submit">Create Sheet for {{ $date }}</button>
                </form>
            @endif

        @else

            {{-- LEADER VIEW --}}
            @if($isLeader)
                {{-- members should be passed in $members by controller --}}
                @foreach($members as $m)
                    @php
                        // m: team_member record; controller should add emp_name property if possible
                        $memberId = $m->emp_id ?? $m['emp_id'] ?? null;
                        $memberName = $m->emp_name ?? DB::table('employee_tbl')->where('emp_id',$memberId)->value('emp_name') ?? $memberId;
                        // existing assignments for this member from $sheet->assignments relation or $assignments collection
                        $memberAssignments = collect();
                        if(isset($sheet->assignments)) {
                            $memberAssignments = collect($sheet->assignments)->where('member_emp_id', $memberId);
                        } else {
                            // fallback: $assignments collection passed separately
                            $memberAssignments = $assignments->where('member_emp_id', $memberId);
                        }
                    @endphp

                    <div class="member-block" id="member-{{ $memberId }}">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div style="font-weight:700;">{{ $memberName }}</div>
                                <div class="small">ID: {{ $memberId }} @if($m->is_leader ?? $m['is_leader'] ?? false) — Team Leader @endif</div>
                            </div>
                        </div>

                        {{-- Existing assignments --}}
                        <div style="margin-top:10px;">
                            @forelse($memberAssignments as $a)
                                <div class="task-row" id="task-{{ $a->id }}">
                                    <div class="small"><strong>Client:</strong> {{ DB::table('m_client_tbl')->where('client_id',$a->client_id)->value('client_company_name') ?? '-' }}</div>
                                    <div class="small"><strong>Task:</strong> {{ $a->task_description ?? $a->leader_remark ?? '-' }}</div>
                                    <div class="small"><strong>Leader remark:</strong> {{ $a->leader_remark ?? '-' }}</div>
                                    <div class="small"><strong>Status:</strong> {{ ucfirst(str_replace('_',' ',$a->status ?? 'not_completed')) }}</div>

                                    @if(!empty($a->is_submitted) && !empty($a->member_remark))
                                        <div class="small"><strong>Member remark:</strong> {{ $a->member_remark }}</div>
                                    @endif

                                    @if(optional($sheet->sheet_date)->isToday())
                                        <div style="margin-top:8px;">
                                            <!-- Update opens inline edit -->
                                            <button class="btn alt" type="button" onclick="openEditAssignment({{ $a->id }})">Update</button>

                                            <form method="POST" action="{{ route('assign.delete', $a->id) }}" style="display:inline-block;">
                                                @csrf
                                                <button class="btn danger" type="submit">Delete</button>
                                            </form>
                                        </div>

                                        {{-- Inline edit block --}}
                                        <div id="edit-box-{{ $a->id }}" style="display:none; margin-top:8px;">
                                            <form method="POST" action="{{ route('assign.update', $a->id) }}">
                                                @csrf
                                                <label class="small">Client</label>
                                                <select name="client_id" class="input">
                                                    <option value="">-- select client --</option>
                                                    @foreach($clients as $c)
                                                        <option value="{{ $c->client_id }}" @if(($a->client_id ?? '') == $c->client_id) selected @endif>{{ $c->client_company_name }}</option>
                                                    @endforeach
                                                </select>

                                                <label class="small">Task description</label>
                                                <textarea name="task_description" class="input" rows="2">{{ $a->task_description }}</textarea>

                                                <label class="small">Leader remark</label>
                                                <textarea name="leader_remark" class="input" rows="2">{{ $a->leader_remark }}</textarea>

                                                <div style="margin-top:8px;">
                                                    <button class="btn" type="submit">Save Update</button>
                                                    <button type="button" class="btn alt" onclick="closeEditAssignment({{ $a->id }})">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="note">No assignments yet for this member.</div>
                            @endforelse
                        </div>

                        {{-- CREATE TASK table (always visible for Option B - shown by default) --}}
                        @if($isLeader && $primarySheet && optional($primarySheet->sheet_date)->isToday())

                            <div style="margin-top:12px;">
                                <div class="small" style="font-weight:600; margin-bottom:6px;">Add tasks for {{ $memberName }}</div>

                                <table class="table-rows" id="rows-{{ $memberId }}">
                                    <thead>
                                        <tr>
                                            <th style="width:40%;">Client</th>
                                            <th>Leader remark</th>
                                            <th style="width:120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <select name="client_id[]" class="input">
                                                    <option value="">-- select --</option>
                                                    @foreach($clients as $c)
                                                        <option value="{{ $c->client_id }}">{{ $c->client_company_name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <textarea name="leader_remark[]" class="input" rows="2"></textarea>
                                            </td>
                                            <td>
                                                <button type="button" class="btn" onclick="addRow('{{ $memberId }}')">+</button>
                                                <button type="button" class="btn alt" onclick="removeRow(this)">-</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div style="margin-top:8px;">
                                    <button type="button" class="btn success" onclick="saveTasksForMember('{{ $memberId }}','{{ $sheet->id }}')">Save Tasks</button>
                                    <div class="note" id="status-{{ $memberId }}"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach

                {{-- Leader: final Save Day Log --}}
                <div style="margin-top:12px; padding:12px; border-top:1px solid #eee;">
                    <form method="POST" action="{{ route('sheet.save_day', $sheet->id) }}">
                        @csrf
                        <label class="label">Today's Target</label>
                        <textarea name="today_target" rows="3" class="input">{{ $sheet->target_text ?? '' }}</textarea>

                        <label class="label">Day remark (optional)</label>
                        <textarea name="day_remark" rows="2" class="input"></textarea>

                        <button class="btn" type="submit">Save Day Log (final snapshot)</button>
                    </form>
                </div>

            {{-- MEMBER VIEW --}}
            @else
                <div class="member-block">
                    <h3>You: {{ $employeeName ?? $empId }}</h3>

                    @if($assignments->isEmpty())
                        <div class="note">No tasks assigned for today.</div>
                    @endif

                    @foreach($assignments as $a)
                        <div class="task-row" id="task-{{ $a->id }}">
                            <div class="small"><strong>Client:</strong> {{ DB::table('m_client_tbl')->where('client_id',$a->client_id)->value('client_company_name') ?? '-' }}</div>
                            <div class="small"><strong>Leader remark:</strong> {{ $a->leader_remark ?? '-' }}</div>

                            {{-- member submit form --}}
                            <form method="POST" action="{{ route('assign.submit', $a->id) }}" id="submit-form-{{ $a->id }}">
                                @csrf
                                <label class="small">Status</label>
                                <div style="display:flex; gap:12px; align-items:center;">
                                    <label><input type="radio" name="status" value="completed" @if(($a->status ?? '') == 'completed') checked @endif onchange="toggleRemarkBox({{ $a->id }}, this)"> Completed</label>
                                    <label><input type="radio" name="status" value="not_completed" @if(($a->status ?? '') == 'not_completed') checked @endif onchange="toggleRemarkBox({{ $a->id }}, this)"> Not completed</label>
                                    <label><input type="radio" name="status" value="in_progress" @if(($a->status ?? '') == 'in_progress') checked @endif onchange="toggleRemarkBox({{ $a->id }}, this)"> In progress</label>
                                </div>

                                <div id="member-remark-box-{{ $a->id }}" style="margin-top:8px; @if(($a->status ?? '') == 'completed') display:none; @endif">
                                    <label class="small">Remark (required if not completed / in progress)</label>
                                    <textarea name="member_remark" rows="3" class="input">{{ $a->member_remark ?? '' }}</textarea>
                                </div>

                                <div style="margin-top:8px;">
                                    <button class="btn" type="submit">Submit</button>
                                </div>
                            </form>

                            @if(!empty($a->is_submitted))
                                <div class="note" style="margin-top:6px;">Submitted: {{ $a->member_remark ?? '-' }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    <!-- RIGHT: Team info + Today's Target -->
    <div class="right">
        <div class="section-title">Team Details</div>

        @if($sheet)
            <div>
                <div class="small"><strong>Team:</strong> {{ $sheet->team->team_name ?? 'Team '.$sheet->team_id }}</div>
                <div class="small"><strong>Leader:</strong> {{ DB::table('employee_tbl')->where('emp_id',$sheet->leader_emp_id)->value('emp_name') ?? $sheet->leader_emp_id }}</div>
            </div>

            <div style="margin-top:12px;">
                <div class="small" style="font-weight:700;">Team Members</div>
                <ul>
                    @foreach($members as $tm)
                        @php $e = DB::table('employee_tbl')->where('emp_id',$tm->emp_id)->first(); @endphp
                        <li>{{ $e->emp_name ?? $tm->emp_id }} @if($tm->is_leader ?? false) (Leader) @endif</li>
                    @endforeach
                </ul>
            </div>

            <div style="margin-top:12px;">
                <div class="small" style="font-weight:700;">Today's Target</div>
                <div class="note">Editable by leader only (today).</div>

                {{-- @if($isLeader && $sheet->sheet_date->isToday()) --}}
                @if($isLeader && $primarySheet && optional($primarySheet->sheet_date)->isToday())

                    <form method="POST" action="{{ route('sheet.save_day', $sheet->id) }}">
                        @csrf
                        <textarea name="today_target" rows="4" class="input">{{ $sheet->target_text ?? '' }}</textarea>
                        <button class="btn" style="margin-top:6px;">Save Target</button>
                    </form>
                @else
                    <div style="margin-top:8px; border:1px solid #eee; padding:8px; min-height:80px;">{{ $sheet->target_text ?? 'No target set' }}</div>
                @endif
            </div>

        @else
            <div class="note">No sheet for this date.</div>
        @endif
    </div>
</div>

<script>
    // add/remove rows for create-task tables
    function addRow(memberId){
        const table = document.getElementById('rows-'+memberId);
        if(!table) return;
        const tbody = table.querySelector('tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <select name="client_id[]" class="input">
                    <option value="">-- select --</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->client_id }}">{{ addslashes($c->client_company_name) }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <textarea name="leader_remark[]" class="input" rows="2"></textarea>
            </td>
            <td>
                <button type="button" class="btn" onclick="addRow('${memberId}')">+</button>
                <button type="button" class="btn alt" onclick="removeRow(this)">-</button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    function removeRow(button){
        const tr = button.closest('tr');
        if(tr) tr.remove();
    }

    function openEditAssignment(id){
        const el = document.getElementById('edit-box-'+id);
        if(el) el.style.display = 'block';
    }
    function closeEditAssignment(id){
        const el = document.getElementById('edit-box-'+id);
        if(el) el.style.display = 'none';
    }

    // save multiple rows: one POST per entered row to /assign (keeps DB simple)
    async function saveTasksForMember(memberId, sheetId){
        const table = document.getElementById('rows-'+memberId);
        if(!table) return;
        const rows = table.querySelectorAll('tbody tr');
        if(!rows.length) return;
        const statusEl = document.getElementById('status-'+memberId);
        if(statusEl) statusEl.innerText = 'Saving...';

        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let created = 0;

        for(const r of rows){
            const clientSelect = r.querySelector('select[name="client_id[]"]');
            const remarkEl = r.querySelector('textarea[name="leader_remark[]"]');
            const client = clientSelect ? clientSelect.value : '';
            const remark = remarkEl ? remarkEl.value : '';

            if(!client && !remark) continue;

            const payload = new URLSearchParams();
            payload.append('sheet_id', sheetId);
            payload.append('member_emp_id', memberId);
            payload.append('client_id', client);
            payload.append('leader_remark', remark);
            payload.append('task_description', remark);

            try {
                const res = await fetch("{{ url('/assign') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: payload.toString()
                });
                if(res.ok) created++;
                else console.error('Assign error', res.status, await res.text());
            } catch(e){
                console.error(e);
            }
        }

        if(statusEl) statusEl.innerText = 'Saved ' + created + ' task(s). Reloading...';
        setTimeout(()=> location.reload(), 900);
    }

    // show/hide member remark when status changes
    function toggleRemarkBox(assignId, radioEl){
        const box = document.getElementById('member-remark-box-'+assignId);
        if(!box) return;
        box.style.display = (radioEl.value === 'completed') ? 'none' : 'block';
    }

    // helper to escape single quotes in JS-constructed innerHTML
    function addslashes(str) {
        return (str+'').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
    }
</script>

<!-- Reference mockup (local file): /mnt/data/1763958066010.jpg -->

@endsection
