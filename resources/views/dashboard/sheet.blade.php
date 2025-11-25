@extends('layouts.app')

@section('content')

    @php
        // safe defaults
        $leaderSheets = $leaderSheets ?? collect();
        $memberAssignments = $memberAssignments ?? collect();

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

        $empId = $empId ?? session('emp_id') ?? null;
        $employeeName = $employeeName ?? session('employee_name') ?? session('emp_name') ?? null;
        $date = $date ?? now()->toDateString();
        $isLeader = $isLeader ?? false;
        $sheet = $sheet ?? $primarySheet ?? null;
        $clients = $clients ?? collect();
        $members = $members ?? collect();
        $assignments = $assignments ?? collect();
        $isFinalized = $isFinalized ?? false;
    @endphp

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* Keep styling compact and close to your original */
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
            display: flex;
            align-items: center;
            justify-content: space-between;
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
/*  */
        .btn {
    background: #0069d9;
    color: #fff;
    padding: 6px 14px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 13px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 32px;            /* uniform button height */
    min-width: 90px;         /* all buttons same width */
    box-sizing: border-box;
    transition: background .15s;
}
.btn:hover {
    background: #005ac1;
}

.btn.alt {
    background: #6c757d;
}

.btn.alt:hover {
    background: #5c636a;
}

.btn.danger {
    background: #dc3545;
}

.btn.danger:hover {
    background: #bb2d3b;
}
.row-actions {
    display: flex;
    flex-direction: row;
    gap: 6px;
    align-items: center;
    justify-content: flex-end;
}

/* Smaller CREATE TASK button */
.btn-create {
    padding: 6px 14px;
    height: 32px;
    font-weight: 600;
}

/* Top Navbar buttons uniform */
.top-nav-btn {
    min-width: 110px;
}

/* Avoid button stretching in forms */
button.btn {
    width: auto !important;
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
    padding: 5px 10px;
    height: 28px;
    min-width: 60px;
    font-size: 12px;
}

        .client-link {
            color: #0d6efd;
            font-weight: 600;
            text-decoration: underline;
        }

        .lock-badge {
            background: #f5f5f5;
            padding: 6px 8px;
            border-radius: 4px;
            color: #666;
        }

        .inline-edit {
            margin-top: 8px;
            background: #f8f9fa;
            padding: 10px;
            border: 1px solid #eee;
        }

        .static-submitted {
            background: #f7fdf7;
            border: 1px solid #e2f3de;
            padding: 8px;
            border-radius: 4px;
            margin-top: 8px;
            color: #2f6b2f;
        }

        .toast {
            position: fixed;
            right: 22px;
            bottom: 22px;
            background: #111;
            color: #fff;
            padding: 10px 14px;
            border-radius: 8px;
            opacity: 0;
            transform: translateY(8px);
            transition: all .22s;
            z-index: 9999;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>

    <div>
        <h2>Daily Task Sheet</h2>
        <div class="small">Logged as: <strong>{{ $employeeName ?? $empId ?? 'Unknown' }}</strong></div>

        <form method="GET" action="{{ route('tasktable') }}" style="margin-top:8px;">
            <input type="hidden" name="emp_id" value="{{ $empId }}">
            <label class="small">Date</label>
            <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="input"
                style="width:200px;">
        </form>
    </div>

    <div class="container-sheet">

        <!-- LEFT: Assignments -->
        <div class="left">
            <div class="section-title">
                <div>Assignments</div>
                <div style="display:flex; gap:8px; align-items:center;">
                    @if($sheet)
                        <div style="font-size:13px; color:#666; margin-right:8px;">Team: {{ $sheet->team_id }}</div>
                    @endif

                    @if($isLeader)
                        <!-- Your Dashboard / Main Dashboard on same row as Assignments -->
                        <a class="btn btn-sm" href="{{ route('dashboard.mine', ['date' => $date]) }}">Your Dashboard</a>
                        <a class="btn btn.alt btn-sm" href="{{ route('tasktable', ['date' => $date]) }}">Main Dashboard</a>
                    @endif
                </div>
            </div>

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

                {{-- LEADER VIEW --}}
                @if($isLeader)
                    @foreach($members as $m)
                        @php
                            $memberId = $m->emp_id ?? $m['emp_id'] ?? null;
                            $memberName = $m->emp_name ?? ($m->employee->emp_name ?? $memberId);
                            $memberAssignments = collect($sheet->assignments)->where('member_emp_id', $memberId);
                        @endphp

                        <div class="member-block" id="member-{{ $memberId }}">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <div style="font-weight:700;">{{ $memberName }}</div>
                                    <div class="small">ID: {{ $memberId }} @if($m->is_leader ?? false) — Team Leader @endif</div>
                                </div>

                                <div>
                                    {{-- Create Task button hidden when finalized OR not today OR sheet finalized --}}
                                    @if(optional($sheet->sheet_date)->isToday() && !$isFinalized)
                                        <button type="button" class="btn" onclick="toggleCreateTask('{{ $memberId }}')">CREATE TASK</button>
                                    @endif
                                </div>
                            </div>

                            {{-- Existing assignments --}}
                            <div style="margin-top:10px;">
                                @forelse($memberAssignments as $a)
                                    <div class="task-row" id="task-{{ $a->id }}">
                                        <div style="display:flex; justify-content:space-between; gap:12px;">
                                            <div style="flex:1;">
                                                <div><strong>Client:</strong>
                                                    <span
                                                        class="client-link">{{ optional($clients->firstWhere('client_id', $a->client_id))->client_company_name ?? '-' }}</span>
                                                </div>
                                                <div class="small"><strong>Leader remark:</strong> {{ $a->leader_remark ?? '-' }}</div>
                                                <div class="small"><strong>Status:</strong>
                                                    {{ ucfirst(str_replace('_', ' ', $a->status ?? 'not_completed')) }}</div>

                                                @if(!empty($a->is_submitted) && !empty($a->member_remark))
                                                    <div class="small"><strong>Member remark:</strong> {{ $a->member_remark }}</div>
                                                @endif
                                            </div>

                                            <div class="row-actions" style="min-width:220px;">
                                                @if(optional($sheet->sheet_date)->isToday() && !$isFinalized)
                                                    <button class="btn alt btn-sm" type="button"
                                                        onclick="openEditAssignment({{ $a->id }})">Edit</button>

                                                    <form method="POST" action="{{ route('assign.delete', $a->id) }}"
                                                        style="display:inline-block;">
                                                        @csrf
                                                        <button class="btn danger btn-sm" type="submit">Delete</button>
                                                    </form>
                                                @else
                                                    <div class="lock-badge">Locked</div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Inline edit block --}}
                                        <div id="edit-box-{{ $a->id }}" class="inline-edit" style="display:none;">
                                            <form onsubmit="return false;" id="edit-form-{{ $a->id }}">
                                                <label class="small">Client</label>
                                                <select name="client_id" class="input">
                                                    <option value="">-- select client --</option>
                                                    @foreach($clients as $c)
                                                        <option value="{{ $c->client_id }}" @if(($a->client_id ?? '') == $c->client_id) selected
                                                        @endif>{{ $c->client_company_name }}</option>
                                                    @endforeach
                                                </select>

                                                <label class="small">Leader remark</label>
                                                <textarea name="leader_remark" class="input" rows="2">{{ $a->leader_remark }}</textarea>

                                                <label class="small">Member remark / response (editable by leader)</label>
                                                <textarea name="member_remark" class="input" rows="2"
                                                    id="leader-reply-{{ $a->id }}">{{ $a->member_remark ?? '' }}</textarea>

                                                <div style="margin-top:8px; display:flex; gap:8px;">
                                                    <button class="btn" type="button" onclick="saveLeaderReply({{ $a->id }})">Save</button>
                                                    <button class="btn alt" type="button"
                                                        onclick="closeEditAssignment({{ $a->id }})">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="note">No assignments yet for this member.</div>
                                @endforelse
                            </div>

                            {{-- CREATE TASK table (hidden block) --}}
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
                                                <button type="button" class="btn btn-sm"
                                                    onclick="addNewTaskRow('{{ $memberId }}')">+</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div style="margin-top:8px;">
                                    <button type="button" class="btn success"
                                        onclick="saveTasksForMember('{{ $memberId }}','{{ $sheet->id }}')">Save Tasks</button>
                                    <button type="button" class="btn alt" onclick="toggleCreateTask('{{ $memberId }}')">Close</button>
                                    <div class="note" id="status-{{ $memberId }}"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Leader final Save Day Log control (single button) --}}
                    <div style="margin-top:12px; padding:12px; border-top:1px solid #eee;">
                        @if($isFinalized)
                            <div class="note">This sheet has been finalized and is read-only.</div>
                            {{-- Unfreeze for testing; will call the route below (only visible to leader) --}}
                            <div style="margin-top:8px;">
                                <button class="btn alt" onclick="unfreezeSheet({{ $sheet->id }})">Unfreeze (testing)</button>
                            </div>
                        @else
                            <div class="note">When you press <strong>Save Day Log (final snapshot)</strong> the sheet will be locked and
                                snapshot saved.</div>
                            <div style="margin-top:8px;">
                                <button class="btn" onclick="finalizeDay({{ $sheet->id }})">Save Day Log (final snapshot)</button>
                            </div>
                        @endif
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
                                <div class="small"><strong>Client:</strong> <span
                                        class="client-link">{{ optional($clients->firstWhere('client_id', $a->client_id))->client_company_name ?? '-' }}</span>
                                </div>
                                <div class="small"><strong>Leader remark:</strong> {{ $a->leader_remark ?? '-' }}</div>

                                @if($isFinalized || !empty($a->is_submitted))
                                    <div class="static-submitted">
                                        <div><strong>Status:</strong> {{ ucfirst($a->status ?? 'not_completed') }}</div>
                                        <div><strong>Member remark:</strong> {{ $a->member_remark ?? '-' }}</div>
                                    </div>
                                @else
                                    <form method="POST" class="member-submit-form" data-assign-id="{{ $a->id }}"
                                        onsubmit="return submitMemberForm(event, {{ $a->id }})">
                                        @csrf
                                        <label class="small">Status</label>
                                        <div style="display:flex; gap:12px; align-items:center;">
                                            <label><input type="radio" name="status" value="completed" @if(($a->status ?? '') == 'completed')
                                            checked @endif onchange="toggleRemarkBox({{ $a->id }}, this)"> Completed</label>
                                            <label><input type="radio" name="status" value="not_completed" @if(($a->status ?? '') == 'not_completed') checked @endif onchange="toggleRemarkBox({{ $a->id }}, this)"> Not
                                                completed</label>
                                            <label><input type="radio" name="status" value="in_progress" @if(($a->status ?? '') == 'in_progress') checked @endif onchange="toggleRemarkBox({{ $a->id }}, this)"> In
                                                progress</label>
                                        </div>

                                        <div id="member-remark-box-{{ $a->id }}"
                                            style="margin-top:8px; @if(($a->status ?? '') == 'completed') display:none; @endif">
                                            <label class="small">Remark (required if not completed / in progress)</label>
                                            <textarea name="member_remark" rows="3" class="input">{{ $a->member_remark ?? '' }}</textarea>
                                        </div>

                                        <div style="margin-top:8px;">
                                            <button class="btn" type="submit">Submit</button>
                                        </div>
                                    </form>
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
                    <div class="small"><strong>Team:</strong> {{ $sheet->team->team_name ?? 'Team ' . $sheet->team_id }}</div>
                    <div class="small"><strong>Leader:</strong>
                        {{ DB::table('employee_tbl')->where('emp_id', $sheet->leader_emp_id)->value('emp_name') ?? $sheet->leader_emp_id }}
                    </div>
                </div>

                <div style="margin-top:12px;">
                    <div class="small" style="font-weight:700;">Team Members</div>
                    <ul>
                        @foreach($members as $tm)
                            @php $e = $tm->employee ?? null; @endphp
                            <li>{{ $e->emp_name ?? $tm->emp_id }} @if($tm->is_leader ?? false) (Leader) @endif</li>
                        @endforeach
                    </ul>
                </div>

                <div style="margin-top:12px;">
                    <div class="small" style="font-weight:700;">Today's Target</div>
                    <div class="note">Editable by leader only (today).</div>

                    @if($isLeader && optional($sheet->sheet_date)->isToday() && !$isFinalized)
                        <textarea id="today_target" class="input" rows="4">{{ $sheet->target_text ?? '' }}</textarea>
                        <div style="margin-top:8px; display:flex; gap:8px;">
                            <button class="btn" onclick="saveTarget({{ $sheet->id }})">Save Target</button>
                            <button class="btn alt" onclick="resetTarget()">Reset</button>
                        </div>
                    @else
                        <div style="margin-top:8px; border:1px solid #eee; padding:8px; min-height:80px;">
                            {{ $sheet->target_text ?? 'No target set' }}</div>
                    @endif
                </div>

            @else
                <div class="note">No sheet for this date.</div>
            @endif
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        /* Helpers */
        function showToast(msg, t = 1600) { const el = document.getElementById('toast'); el.innerText = msg; el.classList.add('show'); setTimeout(() => el.classList.remove('show'), t); }

        /* Toggle create task block */
        function toggleCreateTask(memberId) { const el = document.getElementById('create-task-' + memberId); if (!el) return; el.style.display = (el.style.display === '' || el.style.display === 'none') ? 'block' : 'none'; }

        /* Add new row */
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
            <td><textarea name="leader_remark[]" class="input" rows="2"></textarea></td>
            <td style="text-align:center;"><button type="button" class="btn danger btn-sm" onclick="removeThisRow(this)">-</button></td>
        `;
            tbody.appendChild(tr);
        }
        function removeThisRow(btn) { const tr = btn.closest('tr'); if (tr) tr.remove(); }

        /* Save tasks: loop rows, POST to /assign */
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
                        headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: payload.toString()
                    });
                    if (res.ok) created++; else { console.error('assign failed', await res.text()); showToast('Save failed'); }
                } catch (e) { console.error(e); showToast('Network error'); }
            }
            if (statusEl) statusEl.innerText = 'Saved ' + created + ' task(s). Reloading...';
            setTimeout(() => location.reload(), 700);
        }

        /* Member submit (AJAX) */
        async function submitMemberForm(ev, assignId) {
            ev.preventDefault();
            const form = ev.currentTarget;
            const fm = new FormData(form);
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            try {
                const res = await fetch("{{ url('/assign/submit') }}/" + assignId, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf },
                    body: fm
                });
                if (!res.ok) { showToast('Submit failed'); console.error(await res.text()); return false; }
                // success: make UI static
                const container = document.getElementById('task-' + assignId);
                if (container) {
                    const status = form.querySelector('input[name="status"]:checked')?.value ?? '';
                    const remark = form.querySelector('textarea[name="member_remark"]')?.value ?? '';
                    container.innerHTML = `<div class="static-submitted"><div><strong>Status:</strong> ${status}</div><div><strong>Member remark:</strong> ${remark || '-'}</div></div>`;
                    showToast('Submitted');
                } else location.reload();
            } catch (e) { console.error(e); showToast('Network error'); }
            return false;
        }

        /* Toggle remark box */
        function toggleRemarkBox(assignId, radioEl) {
            const box = document.getElementById('member-remark-box-' + assignId);
            if (!box) return;
            box.style.display = (radioEl.value === 'completed') ? 'none' : 'block';
        }

        /* Open/close inline edit */
        function openEditAssignment(id) { const el = document.getElementById('edit-box-' + id); if (el) el.style.display = 'block'; }
        function closeEditAssignment(id) { const el = document.getElementById('edit-box-' + id); if (el) el.style.display = 'none'; }

        /* Save leader reply / inline update via POST to /assign/{assign} */
        async function saveLeaderReply(assignId) {
            const form = document.getElementById('edit-form-' + assignId);
            if (!form) return;
            const client = form.querySelector('select[name="client_id"]').value;
            const leader_remark = form.querySelector('textarea[name="leader_remark"]').value;
            const member_remark = form.querySelector('textarea[name="member_remark"]').value;
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const payload = new URLSearchParams();
            payload.append('client_id', client || '');
            payload.append('leader_remark', leader_remark || '');
            payload.append('member_remark', member_remark || '');
            try {
                const res = await fetch("{{ url('/assign') }}/" + assignId, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: payload.toString()
                });
                if (!res.ok) { showToast('Update failed'); console.error(await res.text()); return; }
                showToast('Saved');
                closeEditAssignment(assignId);
                setTimeout(() => location.reload(), 700);
            } catch (e) { console.error(e); showToast('Network error'); }
        }

        /* Save target (AJAX to sheet.save_day) */
        async function saveTarget(sheetId) {
            const val = document.getElementById('today_target').value || '';
            const payload = new URLSearchParams();
            payload.append('today_target', val);
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            try {
                const res = await fetch("{{ url('/sheet/save_day') }}/" + sheetId, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: payload.toString()
                });
                if (!res.ok) { showToast('Save target failed'); console.error(await res.text()); return; }
                showToast('Target saved');
                setTimeout(() => location.reload(), 700);
            } catch (e) { console.error(e); showToast('Network error'); }
        }
        function resetTarget() { if (confirm('Reset target to empty?')) document.getElementById('today_target').value = ''; }

        /* Finalize day */
        async function finalizeDay(sheetId) {
            if (!confirm('This will finalize the day and lock editing. Continue?')) return;
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            try {
                const res = await fetch("{{ url('/sheet/save_day') }}/" + sheetId, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({}) // no extras required; controller will snapshot
                });
                if (!res.ok) { showToast('Finalize failed'); console.error(await res.text()); return; }
                showToast('Finalized');
                setTimeout(() => location.reload(), 900);
            } catch (e) { console.error(e); showToast('Network error'); }
        }

        /* Unfreeze (testing) — calls new backend route to delete TaskMain for sheet/team/date (controller below) */
        async function unfreezeSheet(sheetId) {
            if (!confirm('Unfreeze for testing? This will remove the snapshot and reopen editing.')) return;
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            try {
                const res = await fetch("{{ url('/sheet/unfreeze') }}/" + sheetId, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({})
                });
                if (!res.ok) { showToast('Unfreeze failed'); console.error(await res.text()); return; }
                showToast('Unfrozen');
                setTimeout(() => location.reload(), 900);
            } catch (e) { console.error(e); showToast('Network error'); }
        }

        /* helper for escaping */
        function addslashes(str) { return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0'); }
    </script>

@endsection