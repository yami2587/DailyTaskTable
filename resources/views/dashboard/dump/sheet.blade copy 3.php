@extends('layouts.app')

@section('content')

    @php
        // defensive defaults
        $leaderSheets = $leaderSheets ?? collect();
        $memberAssignments = $memberAssignments ?? collect();

        // infer primary sheet
        $primarySheet = $primarySheet
            ?? ($sheet ?? null)
            ?? ($leaderSheets->first() ?? null)
            ?? ($memberAssignments->first()->sheet ?? null);

        // normalize date object if string
        if ($primarySheet && isset($primarySheet->sheet_date) && is_string($primarySheet->sheet_date)) {
            try {
                $primarySheet->sheet_date = \Carbon\Carbon::parse($primarySheet->sheet_date);
            } catch (\Exception $e) {
                $primarySheet->sheet_date = null;
            }
        }

        // variables
        $empId = $empId ?? session('emp_id') ?? null;
        $employeeName = $employeeName ?? session('employee_name') ?? null;
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
        /* clean modern minimal styling */
        :root {
            --accent: #0d6efd;
            --muted: #888;
            --danger: #dc3545;
            --success: #28a745;
            --card: #fff
        }

        body .container-sheet {
            display: flex;
            gap: 20px;
            margin-top: 18px;
            align-items: flex-start
        }

        .left {
            flex: 2;
            background: var(--card);
            border: 1px solid #e6e6e6;
            padding: 16px;
            box-sizing: border-box;
            border-radius: 6px
        }

        .right {
            flex: 1;
            background: var(--card);
            border: 1px solid #e6e6e6;
            padding: 16px;
            box-sizing: border-box;
            border-radius: 6px
        }

        .section-title {
            font-weight: 700;
            margin-bottom: 8px;
            border-bottom: 2px solid #eee;
            padding-bottom: 8px
        }

        .member-block {
            background: #fafafa;
            border: 1px solid #f0f0f0;
            padding: 12px;
            margin-bottom: 14px;
            border-radius: 6px
        }

        .task-row {
            background: #fff;
            border: 1px solid #f1f1f1;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 6px;
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.03)
        }

        .small {
            font-size: 13px;
            color: #444
        }

        .label {
            font-weight: 600;
            margin-bottom: 6px;
            display: block
        }

        .input,
        textarea,
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #d9d9d9;
            border-radius: 6px;
            box-sizing: border-box;
            margin-top: 6px;
            margin-bottom: 8px
        }

        .btn {
            background: var(--accent);
            color: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer
        }

        .btn.alt {
            background: #6c757d
        }

        .btn.danger {
            background: var(--danger)
        }

        .btn.success {
            background: var(--success)
        }

        .btn-sm {
            padding: 6px 8px;
            font-size: 13px;
            border-radius: 6px
        }

        .table-rows {
            border-collapse: collapse;
            width: 100%;
            margin-top: 8px
        }

        .table-rows td,
        .table-rows th {
            padding: 6px;
            border: 1px solid #f1f1f1;
            vertical-align: middle
        }

        .note {
            font-size: 13px;
            color: var(--muted);
            margin-top: 6px
        }

        .row-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            align-items: center
        }

        .toast {
            position: fixed;
            right: 20px;
            bottom: 20px;
            background: #323232;
            color: #fff;
            padding: 10px 14px;
            border-radius: 6px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            opacity: .95;
            z-index: 9999
        }

        .client-link {
            color: var(--accent);
            font-weight: 600
        }

        .kv {
            color: var(--muted);
            font-size: 12px
        }

        .static {
            opacity: 0.8;
        }

        .disabled {
            pointer-events: none;
            opacity: 0.6;
        }

        .flex-row {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
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
        <div class="left">
            <div class="section-title">Assignments</div>

            {{-- no sheet --}}
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

                {{-- leader view --}}
                @if($isLeader)
                
                    @foreach($members as $m)
                        @php
                            $memberId = $m->emp_id ?? $m['emp_id'] ?? null;
                            $memberName = $m->employee->emp_name ?? ($m->emp_name ?? $memberId);
                            $memberAssignments = collect($sheet->assignments ?? collect())->where('member_emp_id', $memberId);
                        @endphp

                        <div class="member-block" id="member-{{ $memberId }}">
                            <div class="top-row">
                                <div>
                                    <div style="font-weight:700;">{{ $memberName }}</div>
                                    <div class="small kv">ID: {{ $memberId }} @if($m->is_leader ?? false) — Team Leader @endif</div>
                                </div>

                                <div>
                                    @if(!$isFinalized && optional($sheet->sheet_date)->isToday())
                                        <button type="button" class="btn" onclick="toggleCreateTask('{{ $memberId }}')">CREATE TASK</button>
                                    @endif
                                </div>
                            </div>

                            {{-- existing assignments --}}
                            <div style="margin-top:10px;">
                                @if($memberAssignments->isEmpty())
                                    <div class="note">No assignments yet for this member.</div>
                                @endif

                                @foreach($memberAssignments as $a)
                                    <div class="task-row @if($isFinalized) static @endif" id="task-{{ $a->id }}">
                                        <div style="display:flex; justify-content:space-between; gap:12px;">
                                            <div style="flex:1;">
                                                <div class="small"><strong>Client:</strong> <span
                                                        class="client-link">{{ $clients->firstWhere('client_id', $a->client_id)->client_company_name ?? '-' }}</span>
                                                </div>
                                                <div class="small"><strong>Task:</strong>
                                                    {{ $a->task_description ?? $a->leader_remark ?? '-' }}</div>
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
                                                        style="display:inline-block">
                                                        @csrf
                                                        <button class="btn danger btn-sm" type="submit">Delete</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- inline edit -> leader can also edit member_remark to "respond" --}}
                                        <div id="edit-box-{{ $a->id }}" style="display:none; margin-top:8px;">
                                            <form onsubmit="return updateAssignmentAjax(event, {{ $a->id }})">
                                                @csrf
                                                <label class="small">Client</label>
                                                <select name="client_id" class="input">
                                                    <option value="">-- select client --</option>
                                                    @foreach($clients as $c)
                                                        <option value="{{ $c->client_id }}" @if(($a->client_id ?? '') == $c->client_id) selected
                                                        @endif>{{ $c->client_company_name }}</option>
                                                    @endforeach
                                                </select>

                                                <label class="small">Task description</label>
                                                <textarea name="task_description" class="input"
                                                    rows="2">{{ $a->task_description }}</textarea>

                                                <label class="small">Leader remark</label>
                                                <textarea name="leader_remark" class="input" rows="2">{{ $a->leader_remark }}</textarea>

                                                <label class="small">Member remark / response</label>
                                                <textarea name="member_remark" class="input"
                                                    rows="2">{{ $a->member_remark ?? '' }}</textarea>

                                                <div style="margin-top:8px; display:flex; gap:8px;">
                                                    <button class="btn" type="submit">Save</button>
                                                    <button class="btn alt" type="button"
                                                        onclick="closeEditAssignment({{ $a->id }})">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- create-task (hidden) --}}
                            @if(optional($sheet->sheet_date)->isToday() && !$isFinalized)
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
                            @endif

                        </div>
                    @endforeach

                    {{-- Leader final controls: Today's Target + final snapshot --}}
                    <div style="margin-top:12px; padding:12px; border-top:1px solid #eee; border-radius:6px">
                        <div class="small label">Actions</div>

                        <div style="margin-bottom:10px;">
                            <label class="small">Final Snapshot / Save full day log</label>
                            <div class="note">Click to finalize: this will create final TaskMain and freeze sheet for that date.
                            </div>
                            <div style="margin-top:8px; display:flex; gap:8px;">
                                <button class="btn" onclick="finalizeSnapshot({{ $sheet->id }})" @if($isFinalized)> Finalized
                                @endif>Save Day Log (final snapshot)</button>
                            </div>
                        </div>
                    </div>

                @else
                    {{-- member view --}}
                    <div class="member-block">
                        <h3>You: {{ $employeeName ?? $empId }}</h3>

                        @if($assignments->isEmpty())
                            <div class="note">No tasks assigned for today.</div>
                        @endif

                        @foreach($assignments as $a)
                            <div class="task-row @if($isFinalized || $a->is_submitted) static @endif" id="task-{{ $a->id }}">
                                <div class="small"><strong>Client:</strong> <span
                                        class="client-link">{{ $clients->firstWhere('client_id', $a->client_id)->client_company_name ?? '-' }}</span>
                                </div>
                                <div class="small"><strong>Leader remark:</strong> {{ $a->leader_remark ?? '-' }}</div>

                                <form onsubmit="return memberSubmitAjax(event, {{ $a->id }})" id="submit-form-{{ $a->id }}">
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
                                        <button class="btn" type="submit" @if($isFinalized || $a->is_submitted) disabled
                                        @endif>Submit</button>
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
                    <div class="small"><strong>Team:</strong> {{ $sheet->team->team_name ?? 'Team ' . $sheet->team_id }}</div>
                    <div class="small"><strong>Leader:</strong>
                        {{ optional($sheet->team)->team_name ? \App\Models\Employee::where('emp_id', $sheet->leader_emp_id)->value('emp_name') : $sheet->leader_emp_id }}
                    </div>
                </div>

                <div style="margin-top:12px;">
                    <div class="small" style="font-weight:700;">Team Members</div>
                    <ul>
                        @foreach($members as $tm)
                            <li>{{ $tm->employee->emp_name ?? $tm->emp_id }} @if($tm->is_leader ?? false) (Leader) @endif</li>
                        @endforeach
                    </ul>
                </div>

                <div style="margin-top:12px;">
                    <div class="small" style="font-weight:700;">Today's Target</div>
                    <div class="note">Editable by leader only (today).</div>

                    @if($isLeader && optional($sheet->sheet_date)->isToday() && !$isFinalized)
                        <label class="small">Target</label>
                        <textarea id="today_target" rows="4" class="input">{{ $sheet->target_text ?? '' }}</textarea>
                        <div style="margin-top:8px; display:flex; gap:8px;">
                            <button class="btn" onclick="saveTarget({{ $sheet->id }})">Save Target</button>
                            <button class="btn alt"
                                onclick="document.getElementById('today_target').value='{{ $sheet->target_text ?? '' }}'">Reset</button>
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

    <!-- toast container -->
    <div id="toast-container" style="position:fixed;right:20px;bottom:20px;z-index:9999;"></div>

    <script>
        /* small helpers */
        function showToast(msg, timeout = 2200) {
            const el = document.createElement('div');
            el.className = 'toast';
            el.innerText = msg;
            document.getElementById('toast-container').appendChild(el);
            setTimeout(() => el.remove(), timeout);
        }

        function getCsrf() {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        }

        /* CREATE TASK dynamic rows */
        function toggleCreateTask(memberId) {
            const el = document.getElementById('create-task-' + memberId);
            if (!el) return;
            el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
            const statusEl = document.getElementById('status-' + memberId);
            if (statusEl) statusEl.innerText = '';
        }

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

        /* Save tasks: loops rows and posts each row to /assign (controller handles AJAX) */
        async function saveTasksForMember(memberId, sheetId) {
            const table = document.getElementById('rows-' + memberId);
            if (!table) return;
            const rows = table.querySelectorAll('tbody tr');
            if (!rows.length) return;
            const statusEl = document.getElementById('status-' + memberId);
            if (statusEl) statusEl.innerText = 'Saving...';
            const csrf = getCsrf();
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
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: payload.toString()
                    });
                    if (res.ok) created++;
                    else {
                        console.error('Assign error', res.status, await res.text());
                    }
                } catch (e) {
                    console.error(e);
                }
            }

            if (statusEl) statusEl.innerText = 'Saved ' + created + ' task(s). Reloading...';
            showToast('Saved ' + created + ' task(s).');
            setTimeout(() => location.reload(), 800);
        }

        /* Member submit AJAX */
        async function memberSubmitAjax(ev, assignId) {
            ev.preventDefault();
            const form = ev.target;
            const inputs = form.elements;
            const status = [...inputs].find(i => i.name === 'status' && i.checked)?.value || '';
            const member_remark = form.querySelector('textarea[name="member_remark"]')?.value || '';
            const csrf = getCsrf();

            const payload = new URLSearchParams();
            payload.append('status', status);
            payload.append('member_remark', member_remark);

            try {
                const res = await fetch("{{ url('/assign/submit') }}/" + assignId, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: payload.toString()
                });
                if (res.ok) {
                    showToast('Submitted');
                    // make the assignment static for this user
                    const row = document.getElementById('task-' + assignId);
                    if (row) {
                        row.classList.add('static');
                        const btn = row.querySelector('button[type="submit"]');
                        if (btn) { btn.disabled = true; btn.innerText = 'Submitted'; }
                        // hide radios
                        const radios = row.querySelectorAll('input[type=radio]');
                        radios.forEach(r => r.disabled = true);
                    }
                } else {
                    const txt = await res.text();
                    console.error('submit error', res.status, txt);
                    showToast('Submit failed');
                }
            } catch (e) {
                console.error(e);
                showToast('Network error');
            }
            return false;
        }

        /* Toggle remark box visibility */
        function toggleRemarkBox(assignId, radioEl) {
            const box = document.getElementById('member-remark-box-' + assignId);
            if (!box) return;
            box.style.display = (radioEl.value === 'completed') ? 'none' : 'block';
        }

        /* Inline edit toggles */
        function openEditAssignment(id) {
            const el = document.getElementById('edit-box-' + id);
            if (el) el.style.display = 'block';
        }
        function closeEditAssignment(id) {
            const el = document.getElementById('edit-box-' + id);
            if (el) el.style.display = 'none';
        }

        /* Update assignment via AJAX when leader edits */
        async function updateAssignmentAjax(ev, assignId) {
            ev.preventDefault();
            const form = ev.target;
            const data = new URLSearchParams();
            for (const el of form.elements) {
                if (!el.name) continue;
                data.append(el.name, el.value);
            }
            const csrf = getCsrf();
            try {
                const res = await fetch("{{ url('/assign/update') }}/" + assignId, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: data.toString()
                });
                if (res.ok) {
                    showToast('Updated');
                    // update UI quickly (reload safest)
                    setTimeout(() => location.reload(), 600);
                } else {
                    showToast('Update failed');
                }
            } catch (e) {
                console.error(e);
                showToast('Network error');
            }
            return false;
        }

        /* Save target (AJAX, no full form submit) */
        async function saveTarget(sheetId) {
            const target = document.getElementById('today_target').value;
            const csrf = getCsrf();
            const payload = new URLSearchParams();
            payload.append('today_target', target);
            payload.append('save_target_only', '1');

            try {
                const res = await fetch("{{ url('/sheet/save') }}/" + sheetId, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: payload.toString()
                });
                if (res.ok) {
                    showToast('Target saved');
                } else {
                    showToast('Save failed');
                }
            } catch (e) {
                console.error(e);
                showToast('Network error');
            }
        }

        /* Finalize snapshot (leader) */
        async function finalizeSnapshot(sheetId) {
            if (!confirm('This will finalize the day log and freeze edits. Continue?')) return;
            const csrf = getCsrf();
            const payload = new URLSearchParams();
            payload.append('today_target', document.getElementById('today_target') ? document.getElementById('today_target').value : '');
            try {
                const res = await fetch("{{ url('/sheet/save') }}/" + sheetId, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: payload.toString()
                });
                if (res.ok) {
                    showToast('Snapshot saved — sheet finalized');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast('Finalize failed');
                }
            } catch (e) {
                console.error(e);
                showToast('Network error');
            }
        }

        /* helper to escape single quotes used in template innerHTML */
        function addslashes(str) {
            return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
        }
    </script>

@endsection