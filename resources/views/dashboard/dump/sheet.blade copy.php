@extends('layouts.app')

@section('content')

@php
// Fix: ensure variables always exist for members (prevents undefined variable errors)
$leaderSheets = $leaderSheets ?? collect();
$memberAssignments = $memberAssignments ?? collect();
@endphp

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



// Controller-provided or fallback values
$empId = $empId ?? session('emp_id') ?? null;
$employeeName = $employeeName ?? session('emp_name') ?? null;
$date = $date ?? now()->toDateString();
$isLeader = $isLeader ?? false;
$sheet = $sheet ?? $primarySheet ?? null;
$clients = $clients ?? collect();
$members = $members ?? collect();
$assignments = $assignments ?? collect();
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    .container-sheet {
        display: flex;
        gap: 20px;
        margin-top: 18px;
        align-items: flex-start;
    }

    .left {
        flex: 2;
        background: #fff;
        border: 1px solid #ddd;
        padding: 16px;
        box-sizing: border-box;
    }

    .right {
        flex: 1;
        background: #fff;
        border: 1px solid #ddd;
        padding: 16px;
        box-sizing: border-box;
    }

    .section-title {
        font-weight: 700;
        margin-bottom: 8px;
        border-bottom: 2px solid #000;
        padding-bottom: 6px;
    }

    .member-block {
        background: #fafafa;
        border: 1px solid #e5e5e5;
        padding: 12px;
        margin-bottom: 14px;
    }

    .task-row {
        background: #fff;
        border: 1px solid #e8e8e8;
        padding: 10px;
        margin-bottom: 8px;
    }

    .small {
        font-size: 13px;
        color: #444;
    }

    .label {
        font-weight: 600;
        margin-bottom: 6px;
        display: block;
    }

    .input,
    textarea,
    select {
        width: 100%;
        padding: 8px;
        border: 1px solid #cfcfcf;
        border-radius: 4px;
        box-sizing: border-box;
        margin-top: 6px;
        margin-bottom: 8px;
    }

    .btn {
        background: #007bff;
        color: #fff;
        padding: 7px 12px;
        border-radius: 4px;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }

    .btn.alt {
        background: #6c757d;
    }

    .btn.danger {
        background: #dc3545;
    }

    .btn.success {
        background: #28a745;
    }

    .table-rows {
        border-collapse: collapse;
        width: 100%;
        margin-top: 8px;
    }

    .table-rows td,
    .table-rows th {
        padding: 6px;
        border: 1px solid #e9e9e9;
        vertical-align: middle;
    }

    .note {
        font-size: 13px;
        color: #666;
        margin-top: 6px;
    }

    .row-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }

    .btn-sm {
        padding: 4px 8px;
        font-size: 14px;
        border-radius: 4px;
    }
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

        @if(!$sheet)
        <div class="note">No sheet for this date.</div>

        @if($isLeader)
        <form method="POST" action="{{ route('sheet.create') }}" style="margin-top:12px; max-width:480px;">
            @csrf
            <label class="label">Team</label>
            <select name="team_id" class="input" required>
                @php $teamIds = $members->pluck('team_id')->unique()->values(); @endphp
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

        {{-- Leader view --}}
        @if($isLeader)
        @foreach($members as $m)
        @php
        $memberId = $m->emp_id ?? $m['emp_id'] ?? null;
        $memberName = $m->emp_name ?? DB::table('employee_tbl')->where('emp_id',$memberId)->value('emp_name') ?? $memberId;
        // assignments for this member
        $memberAssignments = collect();
        if(isset($sheet->assignments)) {
        $memberAssignments = collect($sheet->assignments)->where('member_emp_id', $memberId);
        } else {
        $memberAssignments = $assignments->where('member_emp_id', $memberId);
        }
        @endphp

        <div class="member-block" id="member-{{ $memberId }}">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-weight:700;">{{ $memberName }}</div>
                    <div class="small">ID: {{ $memberId }} @if($m->is_leader ?? $m['is_leader'] ?? false) — Team Leader @endif</div>
                </div>

                <div>
                    <!-- Toggle create-task table -->
                    <button type="button" class="btn" onclick="toggleCreateTask('{{ $memberId }}')">CREATE TASK</button>
                </div>
            </div>

            {{-- Existing assignments --}}
            <div style="margin-top:10px;">
                @foreach($memberAssignments as $a)
                <div class="task-row" id="task-{{ $a->id }}">
                    <div style="display:flex; justify-content:space-between; gap:12px;">
                        <div style="flex:1;">
                            <div class="small"><strong>Client:</strong> {{ DB::table('m_client_tbl')->where('client_id',$a->client_id)->value('client_company_name') ?? '-' }}</div>
                            <div class="small"><strong>Task:</strong> {{ $a->task_description ?? $a->leader_remark ?? '-' }}</div>
                            <div class="small"><strong>Leader remark:</strong> {{ $a->leader_remark ?? '-' }}</div>
                            <div class="small"><strong>Status:</strong> {{ ucfirst(str_replace('_',' ',$a->status ?? 'not_completed')) }}</div>
                            @if(!empty($a->is_submitted) && !empty($a->member_remark))
                            <div class="small"><strong>Member remark:</strong> {{ $a->member_remark }}</div>
                            @endif
                        </div>

                        <div class="row-actions" style="min-width:220px;">
                            @if(optional($sheet->sheet_date)->isToday())
                            <button class="btn alt btn-sm" type="button" onclick="openEditAssignment({{ $a->id }})">Update</button>

                            <form method="POST" action="{{ route('assign.delete', $a->id) }}" style="display:inline-block;">
                                @csrf
                                <button class="btn danger btn-sm" type="submit">Delete</button>
                            </form>
                            @endif
                        </div>
                    </div>

                    {{-- Inline edit --}}
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

                            <div style="margin-top:8px; display:flex; gap:8px;">
                                <button class="btn" type="submit">Save Update</button>
                                <button type="button" class="btn alt" onclick="closeEditAssignment({{ $a->id }})">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- CREATE TASK: hidden block, shown on toggle --}}
            <div id="create-task-{{ $memberId }}" style="display:none; margin-top:12px;">
                <div class="small" style="font-weight:600; margin-bottom:6px;">Add tasks for {{ $memberName }}</div>

                <table class="table-rows" id="rows-{{ $memberId }}">
                    <thead>
                        <tr>
                            <th style="width:40%;">Client</th>
                            <th>Leader remark</th>
                            <th style="width:90px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- FIRST ROW: only + button --}}
                        <tr class="task-input-row">
                            <td>
                                <select name="client_id[]" class="input client-select">
                                    <option value="">-- select client --</option>
                                    @foreach($clients as $c)
                                    <option value="{{ $c->client_id }}">{{ $c->client_company_name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <textarea name="leader_remark[]" class="input" rows="2"></textarea>
                            </td>
                            <td style="text-align:center;">
                                <button type="button" class="btn btn-sm" onclick="addNewTaskRow('{{ $memberId }}')">+</button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div style="margin-top:8px;">
                    <button type="button" class="btn success" onclick="saveTasksForMember('{{ $memberId }}','{{ $sheet->id }}')">Save Tasks</button>
                    <button type="button" class="btn alt" onclick="toggleCreateTask('{{ $memberId }}')">Close</button>
                    <div class="note" id="status-{{ $memberId }}"></div>
                </div>
            </div>
        </div>
        @endforeach

        {{-- Leader: Save Day Log (single target only) --}}
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

        @else
        {{-- Member view --}}
        <div class="member-block">
            <h3>You: {{ $employeeName ?? $empId }}</h3>

            @if($assignments->isEmpty())
            <div class="note">No tasks assigned for today.</div>
            @endif

            @foreach($assignments as $a)
            <div class="task-row" id="task-{{ $a->id }}">
                <div class="small"><strong>Client:</strong> {{ DB::table('m_client_tbl')->where('client_id',$a->client_id)->value('client_company_name') ?? '-' }}</div>
                <div class="small"><strong>Leader remark:</strong> {{ $a->leader_remark ?? '-' }}</div>

                <form method="POST" action="{{ route('assign.submit', $a->id) }}" id="submit-form-{{ $a->id }}">
                    @csrf
                    <label class="small">Status</label>
                    <div style="display:flex; gap:12px; align-items:center;">
                        <label><input type="radio" name="status" value="completed" @if(($a->status ?? '') == 'completed') checked @endif onchange="toggleRemarkBox({{ $a->id }}, this)"> Completed</label>
                        <label><input type="radio" name="status" value="not_completed" @if(($a->status ?? '') == 'not_completed') checked @endif onchange="toggleRemarkBox({{ $a->id }}, this)"> Not completed</label>
                        <label><input type="radio" name="status" value="in_progress" @if(($a->status ?? '') == 'in_progress') checked @endif onchange="toggleRemarkBox({{ $a->id }}, this)"> In progress</label>
                    </div>

                    <div id="member-remark-box-{{ $a->id }}" style="margin-top:8px; @if(($a->status ?? '') == 'completed') display:none;  ">@endif
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

            @if($isLeader && optional($sheet->sheet_date)->isToday())
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
    // Toggle create-task block visibility for a member
    function toggleCreateTask(memberId) {
        const el = document.getElementById('create-task-' + memberId);
        if (!el) return;
        el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
        const statusEl = document.getElementById('status-' + memberId);
        if (statusEl) statusEl.innerText = '';
    }

    // Add a new row (with - only) under the first row
    function addNewTaskRow(memberId) {
        const tbody = document.querySelector("#rows-" + memberId + " tbody");
        if (!tbody) return;

        const tr = document.createElement('tr');
        tr.className = 'task-input-row';
        tr.innerHTML = `
            <td>
                <select name="client_id[]" class="input client-select">
                    <option value="">-- select client --</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->client_id }}">{{ addslashes($c->client_company_name) }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <textarea name="leader_remark[]" class="input" rows="2"></textarea>
            </td>
            <td style="text-align:center;">
                <button type="button" class="btn danger btn-sm" onclick="removeThisRow(this)">-</button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    function removeThisRow(btn) {
        const tr = btn.closest('tr');
        if (tr) tr.remove();
    }

    // Inline edit toggles
    function openEditAssignment(id) {
        const el = document.getElementById('edit-box-' + id);
        if (el) el.style.display = 'block';
    }

    function closeEditAssignment(id) {
        const el = document.getElementById('edit-box-' + id);
        if (el) el.style.display = 'none';
    }

    // Save multiple rows: one POST per entered row to /assign
    async function saveTasksForMember(memberId, sheetId) {
        const table = document.getElementById('rows-' + memberId);
        if (!table) return;
        const rows = table.querySelectorAll('tbody tr');
        if (!rows.length) return;
        const statusEl = document.getElementById('status-' + memberId);
        if (statusEl) statusEl.innerText = 'Saving...';

        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let created = 0;

        for (const r of rows) {
            const clientSelect = r.querySelector('select[name="client_id[]"]');
            const remarkEl = r.querySelector('textarea[name="leader_remark[]"]');
            const client = clientSelect ? clientSelect.value : '';
            const remark = remarkEl ? remarkEl.value.trim() : '';

            // skip empty row
            if (!client && !remark) continue;

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
                if (res.ok) created++;
                else console.error('Assign error', res.status, await res.text());
            } catch (e) {
                console.error(e);
            }
        }

        if (statusEl) statusEl.innerText = 'Saved ' + created + ' task(s). Reloading...';
        setTimeout(() => location.reload(), 900);
    }

    // Member remark show/hide
    function toggleRemarkBox(assignId, radioEl) {
        const box = document.getElementById('member-remark-box-' + assignId);
        if (!box) return;
        box.style.display = (radioEl.value === 'completed') ? 'none' : 'block';
    }

    // escape helper used in innerHTML building
    function addslashes(str) {
        return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
    }
</script>


@endsection