@extends('layouts.app')

@section('content')

@php
    // safe defaults / fallbacks
    $leaderSheets = $leaderSheets ?? collect();
    $memberAssignments = $memberAssignments ?? collect();

    $primarySheet = $primarySheet
        ?? ($sheet ?? null)
        ?? ($leaderSheets->first() ?? null)
        ?? ($memberAssignments->first()->sheet ?? null);

    if ($primarySheet && is_string($primarySheet->sheet_date)) {
        try { $primarySheet->sheet_date = \Carbon\Carbon::parse($primarySheet->sheet_date); }
        catch (\Exception $e) { $primarySheet->sheet_date = null; }
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

{{-- Bootstrap 5 (if your layout already loads it remove these two lines) --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* ===== Visual + layout (polished, keeps your structure) ===== */

/* page */
.page-wrap { padding:18px; font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }

/* header */
.header-row { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
.title { font-size:20px; font-weight:700; }
.sub { color:#6b7280; font-size:13px; }

/* grid */
.grid { display:flex; gap:20px; align-items:flex-start; }
.left { flex:2; }
.right { width:340px; }

/* gradient shell around everything */
.gradient-shell { border-radius:16px; padding:2px; background: linear-gradient(135deg, #6366F1 0%, #EC4899 40%, #10B981 100%); }
.gradient-shell > .inner { border-radius:14px; background: rgba(255,255,255,0.95); padding:18px; backdrop-filter: blur(4px); }

/* cards */
.card-surface { background:#fff; border-radius:12px; padding:14px; margin-bottom:14px; box-shadow: 0 6px 18px rgba(16,24,40,0.04); border:1px solid rgba(13,17,23,0.03); transition: transform .12s ease, box-shadow .12s ease; }
.card-surface:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(16,24,40,0.06); }

.member-card { border-radius:10px; padding:12px; background:linear-gradient(180deg,#ffffff,#fbfdff); border:1px solid #eef3fb; margin-bottom:12px; }
.task-row { background:#fff; border-radius:8px; padding:12px; border:1px solid #eef2f6; margin-bottom:10px; display:flex; gap:12px; align-items:flex-start; transition: transform .08s ease; }
.task-row:hover { transform: translateY(-2px); }

/* small text & labels */
.small { color:#4b5563; font-size:13px; }
.label { font-weight:600; }

/* inputs */
.input, textarea, select { width:100%; padding:8px 10px; border-radius:8px; border:1px solid #e6eefb; box-sizing:border-box; background:#fbfdff; transition: box-shadow .12s, border-color .12s; }
.input:focus, textarea:focus, select:focus { outline:none; box-shadow: 0 0 0 4px rgba(99,102,241,0.09); border-color: #6366F1; }

/* table rows for add-task */
.table-rows { width:100%; border-collapse:collapse; margin-top:8px; }
.table-rows td, .table-rows th { padding:8px; border-bottom:1px dashed #eef2f6; vertical-align:middle; }

/* badges and roles */
.badge-role { background:linear-gradient(90deg,#6366F1,#3B82F6); color:#fff; padding:6px 10px; border-radius:999px; font-weight:600; font-size:12px; }

/* member list modern */
.member-list { display:flex; flex-direction:column; gap:8px; }
.member-row { display:flex; gap:12px; align-items:center; }
.avatar { width:40px; height:40px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; }
.avatar.leader { background: linear-gradient(135deg,#6366F1,#3B82F6); }
.avatar.member { background: linear-gradient(135deg,#34D399,#3B82F6); }

/* static submitted */
.static-submitted { background: linear-gradient(135deg,#ECFDF5,#D1FAE5); border:1px solid #6EE7B7; padding:10px; border-radius:8px; color:#065F46; font-weight:600; }

/* buttons tweaks */
.btn-ghost { background:transparent; border:1px solid #e6eefb; color:#0b5ed7; }
.btn-danger { background:linear-gradient(90deg,#EF4444,#DC2626); color:#fff; border:none; box-shadow: 0 6px 12px rgba(220,53,69,0.14); }
.btn-primary { background: linear-gradient(90deg,#6366F1,#A855F7); color:#fff; border:none; box-shadow: 0 6px 14px rgba(99,102,241,0.18); }

/* icon-only delete button */
.icon-btn { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:8px; border:none; cursor:pointer; }

/* toast */
.toast { position:fixed; right:20px; bottom:20px; z-index:9999; background: linear-gradient(90deg,#6366F1,#EC4899); color:#fff; padding:10px 14px; border-radius:8px; opacity:0; transform:translateY(6px); transition:all .18s; }
.toast.show { opacity:1; transform:translateY(0); }

/* responsive */
@media(max-width:980px) {
  .grid { flex-direction:column; }
  .right { width:100%; }
}

/* dark mode (optional) */
:root { --bg: #ffffff; --text: #111827; }
[data-theme="dark"] {
    --bg: #0b1220;
    --text: #d1d5db;
}
[data-theme="dark"] body { background: linear-gradient(135deg,#081022,#0b1220); color:var(--text); }
[data-theme="dark"] .inner { background: rgba(7,10,16,0.6); }
[data-theme="dark"] .card-surface, [data-theme="dark"] .member-card, [data-theme="dark"] .task-row {
    background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01));
    border: 1px solid rgba(255,255,255,0.03);
    box-shadow: none;
}
[data-theme="dark"] .input, [data-theme="dark"] textarea, [data-theme="dark"] select {
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.06);
    color:var(--text);
}

/* subtle entrance animations */
.fade-in { opacity:0; transform:translateY(6px); animation: fadeInUp .38s forwards; }
@keyframes fadeInUp { to { opacity:1; transform:translateY(0); } }

</style>

<div class="page-wrap">
    <div class="header-row">
        <div>
            <div class="title">Daily Task Sheet</div>
            <div class="sub">Logged as: <strong>{{ $employeeName ?? $empId ?? 'Unknown' }}</strong> — ID: {{ $empId }}</div>
        </div>

        <div class="d-flex align-items-center gap-2">
            {{-- date selector --}}
            <form method="GET" action="{{ route('tasktable') }}" id="dateForm" class="d-flex align-items-center gap-2">
                <input type="date" name="date" value="{{ $date }}" class="input" onchange="document.getElementById('dateForm').submit()" />
            </form>

            {{-- leader personal dashboard button unchanged (as requested) --}}
            @if($isLeader)
                <a class="btn btn-ghost btn-sm" href="{{ route('dashboard.mine', ['date' => $date]) }}">Your Dashboard</a>
            @endif

            {{-- dark mode toggle --}}
            <button id="themeToggle" class="btn btn-ghost btn-sm" title="Toggle dark mode">
                <!-- sun/moon icon inline -->
                <svg id="themeIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M6 0a.5.5 0 0 1 .5.5V2A.5.5 0 0 1 6 2v-.5A.5.5 0 0 1 6 0zM10 0a.5.5 0 0 1 .5.5V2A.5.5 0 0 1 10 2v-.5A.5.5 0 0 1 10 0z"/>
                    <circle cx="8" cy="8" r="3"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="gradient-shell mt-3 fade-in">
        <div class="inner">
            <div class="grid">

                <!-- LEFT: assignments -->
                <div class="left">
                    <div class="card-surface">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div style="font-weight:700;">Assignments</div>
                            @if($sheet)
                                <div class="small">Team: <span class="badge-role">#{{ $sheet->team_id }}</span></div>
                            @endif
                        </div>

                        @if(!$sheet)
                            <div class="small mb-2">No sheet for this date.</div>
                            @if($isLeader)
                                <form method="POST" action="{{ route('sheet.create') }}" class="row gx-2 gy-2 align-items-end" style="max-width:520px;">
                                    @csrf
                                    <div class="col-6">
                                        <label class="small label">Team</label>
                                        <select name="team_id" class="input" required>
                                            @php $teamIds = $members->pluck('team_id')->unique()->values(); @endphp
                                            @foreach($teamIds as $tid)
                                                <option value="{{ $tid }}">Team {{ $tid }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <input type="hidden" name="leader_emp_id" value="{{ $empId }}">
                                    <input type="hidden" name="sheet_date" value="{{ $date }}">
                                    <div class="col-6">
                                        <button class="btn btn-primary" type="submit">Create Sheet for {{ $date }}</button>
                                    </div>
                                </form>
                            @endif

                        @else
                            {{-- LEADER VIEW --}}
                            @if($isLeader)
                                @foreach($members as $m)
                                    @php
                                        $memberId = $m->emp_id;
                                        $memberName = $m->employee->emp_name ?? $memberId;
                                        $memberAssignments = $sheet->assignments->where('member_emp_id', $memberId);
                                    @endphp

                                    <div class="member-card">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div style="font-weight:700;">{{ $memberName }}</div>
                                                <div class="small">ID: {{ $memberId }} @if($m->is_leader) — <span class="badge-role">Leader</span> @endif</div>
                                            </div>

                                            <div class="d-flex flex-column align-items-end">
                                                @if(optional($sheet->sheet_date)->isToday() && !$isFinalized)
                                                    <button class="btn btn-ghost btn-sm" type="button" onclick="toggleCreateTask('{{ $memberId }}')">Create task</button>
                                                @else
                                                    <div class="small">Locked / Read-only</div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- existing assignments --}}
                                        <div style="margin-top:10px;">
                                            @forelse($memberAssignments as $a)
                                                <div class="task-row" id="task-{{ $a->id }}">
                                                    <div style="flex:1;">
                                                        <div class="small"><strong>Project:</strong>
                                                            <span style="color:#0b5ed7; font-weight:700;">{{ optional($clients->firstWhere('client_id', $a->client_id))->client_company_name ?? '-' }}</span>
                                                        </div>
                                                        <div class="small"><strong>Task:</strong> {{ $a->leader_remark ?? '-' }}</div>
                                                        <div class="small"><strong>Status:</strong> {{ ucfirst($a->status ?? 'not_completed') }}</div>

                                                        @if($a->is_submitted && $a->member_remark)
                                                            <div class="static-submitted mt-2">Member remark: {{ $a->member_remark }}</div>
                                                        @endif
                                                    </div>

                                                    <div class="d-flex flex-column align-items-end" style="min-width:140px;">
                                                        @if(optional($sheet->sheet_date)->isToday() && !$isFinalized)
                                                            <div class="mb-1">
                                                                <button class="btn btn-outline-primary btn-sm" onclick="openEditAssignment({{ $a->id }})">Edit</button>
                                                            </div>

                                                            <form method="POST" action="{{ route('assign.delete', $a->id) }}" onsubmit="return confirmDelete(this);" style="display:inline-block;">
                                                                @csrf
                                                                <button class="icon-btn btn-danger" type="submit" title="Delete">
                                                                    {{-- trash icon --}}
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                                        <path d="M5.5 5.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-4a.5.5 0 0 1-.5-.5v-7z"/>
                                                                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 1 1 0-2h3.11a1 1 0 0 1 .9-.6h2.98c.36 0 .7.24.9.6H14.5a1 1 0 0 1 1 1zM11 4H5v9a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1V4z"/>
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                        @else
                                                            <div class="badge-role">Locked</div>
                                                        @endif
                                                    </div>

                                                    {{-- inline edit (hidden) --}}
                                                    <div id="edit-box-{{ $a->id }}" class="inline-edit mt-2" style="display:none; width:100%;">
                                                        <form id="edit-form-{{ $a->id }}" onsubmit="return false;">
                                                            <div class="row gx-2">
                                                                <div class="col-5">
                                                                    <label class="small label">Project</label>
                                                                    <select name="client_id" class="input">
                                                                        <option value="">-- select --</option>
                                                                        @foreach($clients as $c)
                                                                            <option value="{{ $c->client_id }}" @if(($a->client_id ?? '') == $c->client_id) selected @endif>{{ $c->client_company_name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>

                                                                <div class="col-7">
                                                                    <label class="small label">Task (Leader remark)</label>
                                                                    <textarea name="leader_remark" class="input" rows="2">{{ $a->leader_remark }}</textarea>
                                                                </div>
                                                            </div>

                                                            <label class="small label mt-2">Member remark / response (editable by leader)</label>
                                                            <textarea name="member_remark" id="leader-reply-{{ $a->id }}" class="input" rows="2">{{ $a->member_remark ?? '' }}</textarea>

                                                            <div class="mt-2 d-flex gap-2">
                                                                <button type="button" class="btn btn-primary btn-sm" onclick="saveLeaderReply({{ $a->id }})">Save</button>
                                                                <button type="button" class="btn btn-ghost btn-sm" onclick="closeEditAssignment({{ $a->id }})">Cancel</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="small">No assignments yet for this member.</div>
                                            @endforelse
                                        </div>

                                        {{-- CREATE TASK table (kept table structure) --}}
                                        <div id="create-task-{{ $memberId }}" style="display:none; margin-top:12px;">
                                            <div class="small" style="font-weight:600; margin-bottom:6px;">Add tasks for {{ $memberName }}</div>

                                            <table class="table-rows">
                                                <thead>
                                                    <tr>
                                                        <th style="width:45%;">Project</th>
                                                        <th>Leader remark</th>
                                                        <th style="width:80px;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="rows-{{ $memberId }}">
                                                    <tr class="task-input-row">
                                                        <td>
                                                            <select name="client_id[]" class="input client-select">
                                                                <option value="">-- select project --</option>
                                                                @foreach($clients as $c)
                                                                    <option value="{{ $c->client_id }}">{{ $c->client_company_name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <textarea name="leader_remark[]" class="input" rows="2"></textarea>
                                                        </td>
                                                        <td style="text-align:center;">
                                                            <button type="button" class="btn btn-primary btn-sm" onclick="addNewTaskRow('{{ $memberId }}')">+</button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            <div class="mt-2 d-flex gap-2">
                                                <button class="btn btn-primary btn-sm" onclick="saveTasksForMember('{{ $memberId }}','{{ $sheet->id }}')">Assign</button>
                                                <button class="btn btn-ghost btn-sm" onclick="toggleCreateTask('{{ $memberId }}')">Close</button>
                                                <div class="small text-muted" id="status-{{ $memberId }}"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                {{-- finalize control --}}
                                <div class="mt-3">
                                    @if($isFinalized)
                                        <div class="small">This sheet has been finalized and is read-only.</div>
                                    @else
                                        <div class="small">When ready press <strong>Save Day Log</strong> to snapshot & lock the sheet.</div>
                                        <div class="mt-2">
                                            <button class="btn btn-primary" onclick="finalizeDay({{ $sheet->id }})">Save Day Log (final snapshot)</button>
                                        </div>
                                    @endif
                                </div>

                                {{-- unfreeze for testing (kept) --}}
                                <div class="mt-2">
                                    <button class="btn btn-ghost btn-sm" onclick="unfreezeSheet({{ $sheet->id }})">
                                        Unfreeze Sheet (Testing)
                                    </button>
                                </div>

                            @else
                                {{-- MEMBER view --}}
                                <div class="member-card">
                                    <div style="font-weight:700;">You: {{ $employeeName ?? $empId }}</div>

                                    @if($assignments->isEmpty())
                                        <div class="small mt-2">No tasks assigned for today.</div>
                                    @endif

                                    @foreach($assignments as $a)
                                        <div class="task-row" id="task-{{ $a->id }}">
                                            <div style="flex:1;">
                                                <div class="small"><strong>Project:</strong>
                                                    <span style="color:#0b5ed7; font-weight:700;">{{ optional($clients->firstWhere('client_id', $a->client_id))->client_company_name ?? '-' }}</span>
                                                </div>
                                                <div class="small"><strong>Leader remark:</strong> {{ $a->leader_remark ?? '-' }}</div>

                                                @if($isFinalized || $a->is_submitted)
                                                    <div class="static-submitted mt-2">
                                                        <div><strong>Status:</strong> {{ ucfirst($a->status ?? 'not_completed') }}</div>
                                                        <div><strong>Member remark:</strong> {{ $a->member_remark ?? '-' }}</div>
                                                    </div>
                                                @else
                                                    <form class="member-submit-form" onsubmit="return submitMemberForm(event, {{ $a->id }})">
                                                        @csrf
                                                        <label class="small">Status</label>
                                                        <div class="d-flex gap-3 align-items-center">
                                                            <label><input type="radio" name="status" value="completed" @if(($a->status ?? '') == 'completed') checked @endif onchange="toggleRemarkBox({{ $a->id }}, this)"> Completed</label>
                                                            <label><input type="radio" name="status" value="not_completed" @if(($a->status ?? '') == 'not_completed') checked @endif onchange="toggleRemarkBox({{ $a->id }}, this)"> Not completed</label>
                                                            <label><input type="radio" name="status" value="in_progress" @if(($a->status ?? '') == 'in_progress') checked @endif onchange="toggleRemarkBox({{ $a->id }}, this)"> In progress</label>
                                                        </div>

                                                        <div id="member-remark-box-{{ $a->id }}" class="mt-2" style="@if(($a->status ?? '') == 'completed') display:none; @endif">
                                                            <label class="small">Remark (required if not completed / in progress)</label>
                                                            <textarea name="member_remark" class="input" rows="2">{{ $a->member_remark ?? '' }}</textarea>
                                                        </div>

                                                        <div class="mt-2">
                                                            <button class="btn btn-primary btn-sm" type="submit">Submit</button>
                                                        </div>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- RIGHT: Team details + target -->
                <div class="right">
                    <div class="card-surface">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div style="font-weight:700;">Team Details</div>
                            @if($sheet)
                                <div class="small">Date: {{ optional($sheet->sheet_date)->toDateString() ?? $date }}</div>
                            @endif
                        </div>

                        @if($sheet)
                            <div class="mb-3">
                                <div class="small"><strong>Team:</strong> {{ $sheet->team->team_name ?? 'Team '.$sheet->team_id }}</div>
                                <div class="small"><strong>Leader:</strong> {{ DB::table('employee_tbl')->where('emp_id',$sheet->leader_emp_id)->value('emp_name') ?? $sheet->leader_emp_id }}</div>
                            </div>

                            <div class="card-surface mb-3" style="padding:10px;">
                                <div style="font-weight:700;">Members</div>
                                <div class="member-list mt-2">
                                    @foreach($members as $tm)
                                        @php
                                            $e = $tm->employee ?? null;
                                            $name = $e->emp_name ?? $tm->emp_id;
                                            $initials = strtoupper(substr($name,0,1));
                                        @endphp
                                        <div class="member-row">
                                            <div class="avatar {{ $tm->is_leader ? 'leader' : 'member' }}">{{ $initials }}</div>
                                            <div>
                                                <div style="font-weight:600;">{{ $name }}</div>
                                                <div class="small">ID: {{ $tm->emp_id }}</div>
                                            </div>
                                            <div class="ms-auto">
                                                @if($tm->is_leader)
                                                    <span class="badge-role">Leader</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="card-surface">
                                <div style="font-weight:700;">Today's Target</div>
                                <div class="small text-muted mb-2">Editable by leader (today)</div>

                                @if($isLeader && optional($sheet->sheet_date)->isToday() && !$isFinalized)
                                    <textarea id="today_target" class="input" rows="4">{{ $sheet->target_text ?? '' }}</textarea>
                                    <div class="d-flex gap-2 mt-2">
                                        <button class="btn btn-primary btn-sm" onclick="saveTarget({{ $sheet->id }})">Save Target</button>
                                        <button class="btn btn-ghost btn-sm" onclick="resetTarget()">Reset</button>
                                    </div>
                                @else
                                    <div style="min-height:80px;" class="small">{{ $sheet->target_text ?? 'No target set' }}</div>
                                @endif
                            </div>
                        @else
                            <div class="small">No sheet for this date.</div>
                        @endif
                    </div>
                </div>

            </div> {{-- grid --}}
        </div> {{-- inner --}}
    </div> {{-- gradient-shell --}}
</div> {{-- page-wrap --}}

<div id="toast" class="toast"></div>

{{-- Bootstrap JS (if your layout already has it you can remove this line) --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* ===== helper & toast ===== */
function showToast(msg, t = 1500) {
    const el = document.getElementById('toast');
    el.innerText = msg;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), t);
}

/* confirm Delete: called by onsubmit on delete forms */
function confirmDelete(form) {
    if (!confirm('Delete this assignment? This action cannot be undone.')) return false;
    // allow submit
    return true;
}

/* toggleCreateTask (keeps add-row table intact) */
function toggleCreateTask(memberId) {
    const el = document.getElementById('create-task-' + memberId);
    if(!el) return;
    el.style.display = (el.style.display === '' || el.style.display === 'none') ? 'block' : 'none';
    const st = document.getElementById('status-' + memberId);
    if (st) st.innerText = '';
}

/* add/remove new task row (table structure preserved) */
function addNewTaskRow(memberId) {
    const tbody = document.getElementById('rows-' + memberId);
    if(!tbody) return;
    const tr = document.createElement('tr');
    tr.className = 'task-input-row';
    tr.innerHTML = `
        <td>
            <select name="client_id[]" class="input client-select">
                <option value="">-- select project --</option>
                @foreach($clients as $c)
                    <option value="{{ $c->client_id }}">{{ addslashes($c->client_company_name) }}</option>
                @endforeach
            </select>
        </td>
        <td><textarea name="leader_remark[]" class="input" rows="2"></textarea></td>
        <td style="text-align:center;"><button type="button" class="btn btn-danger btn-sm" onclick="removeThisRow(this)">-</button></td>
    `;
    tbody.appendChild(tr);
}
function removeThisRow(btn){ const tr = btn.closest('tr'); if(tr) tr.remove(); }

/* Save tasks for member via POST /assign (AJAX) */
async function saveTasksForMember(memberId, sheetId){
    const tableBody = document.getElementById('rows-' + memberId);
    if(!tableBody) return;
    const rows = tableBody.querySelectorAll('tr');
    if(!rows.length) return;
    const statusEl = document.getElementById('status-' + memberId);
    if(statusEl) statusEl.innerText = 'Saving...';
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let created = 0;
    for(const r of rows){
        const clientSelect = r.querySelector('select[name="client_id[]"]');
        const remarkEl = r.querySelector('textarea[name="leader_remark[]"]');
        const client = clientSelect ? clientSelect.value : '';
        const remark = remarkEl ? remarkEl.value.trim() : '';
        if(!client && !remark) continue;
        const payload = new URLSearchParams();
        payload.append('sheet_id', sheetId);
        payload.append('member_emp_id', memberId);
        payload.append('client_id', client);
        payload.append('leader_remark', remark);
        payload.append('task_description', remark);
        try {
            const res = await fetch("{{ url('/assign') }}", {
                method:'POST',
                headers:{ 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
                body: payload.toString()
            });
            if(res.ok) created++; else { console.error('assign failed', await res.text()); showToast('Save failed for one row'); }
        } catch(e) { console.error(e); showToast('Network error'); }
    }
    if(statusEl) statusEl.innerText = 'Saved ' + created + ' task(s). Reloading...';
    setTimeout(()=> location.reload(), 700);
}

/* Member submit (AJAX) -> make UI static on success */
async function submitMemberForm(ev, assignId){
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
        if(!res.ok) {
            showToast('Submit failed');
            console.error(await res.text());
            return false;
        }
        // success -> convert UI to static
        const container = document.getElementById('task-' + assignId);
        if(container){
            const status = form.querySelector('input[name="status"]:checked')?.value ?? '';
            const remark = form.querySelector('textarea[name="member_remark"]')?.value ?? '';
            container.innerHTML = `<div class="static-submitted"><div><strong>Status:</strong> ${status}</div><div style="margin-top:6px;"><strong>Member remark:</strong> ${remark || '-'}</div></div>`;
            showToast('Submitted');
        } else {
            location.reload();
        }
    } catch(e) { console.error(e); showToast('Network error'); }
    return false;
}

/* toggle remark box */
function toggleRemarkBox(assignId, radioEl){
    const box = document.getElementById('member-remark-box-' + assignId);
    if(!box) return;
    box.style.display = (radioEl.value === 'completed') ? 'none' : 'block';
}

/* inline edit open/close */
function openEditAssignment(id){ const el = document.getElementById('edit-box-' + id); if(el) el.style.display = 'block'; }
function closeEditAssignment(id){ const el = document.getElementById('edit-box-' + id); if(el) el.style.display = 'none'; }

/* leader reply/save -> POST to /assign/{assign} */
async function saveLeaderReply(assignId){
    const form = document.getElementById('edit-form-' + assignId);
    if(!form) return;
    const client = form.querySelector('select[name="client_id"]').value || '';
    const leader_remark = form.querySelector('textarea[name="leader_remark"]').value || '';
    const member_remark = form.querySelector('textarea[name="member_remark"]').value || '';
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const payload = new URLSearchParams();
    payload.append('client_id', client);
    payload.append('leader_remark', leader_remark);
    payload.append('member_remark', member_remark);
    try {
        const res = await fetch("{{ url('/assign') }}/" + assignId, {
            method:'POST',
            headers:{ 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: payload.toString()
        });
        if(!res.ok){ showToast('Update failed'); console.error(await res.text()); return; }
        showToast('Saved');
        closeEditAssignment(assignId);
        setTimeout(()=> location.reload(), 700);
    } catch(e){ console.error(e); showToast('Network error'); }
}

/* save target via AJAX to sheet.save_day (today_target only) */
async function saveTarget(sheetId){
    const val = document.getElementById('today_target').value || '';
    const payload = new URLSearchParams();
    payload.append('today_target', val);
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    try {
        const res = await fetch("{{ url('/sheet/save_day') }}/" + sheetId, {
            method:'POST',
            headers:{ 'X-CSRF-TOKEN': csrf, 'Content-Type':'application/x-www-form-urlencoded' },
            body: payload.toString()
        });
        if(!res.ok){ showToast('Save target failed'); console.error(await res.text()); return; }
        showToast('Target saved');
        setTimeout(()=> location.reload(), 700);
    } catch(e){ console.error(e); showToast('Network error'); }
}
function resetTarget(){ if(confirm('Reset target to empty?')) document.getElementById('today_target').value = ''; }

/* finalize day (snapshot + lock) */
async function finalizeDay(sheetId){
    if(!confirm('This will finalize the day, create snapshot and lock editing. Continue?')) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    try {
        const res = await fetch("{{ url('/sheet/save_day') }}/" + sheetId, {
            method:'POST',
            headers:{ 'X-CSRF-TOKEN': csrf, 'Content-Type':'application/x-www-form-urlencoded' },
            body: new URLSearchParams({'finalize':'1'}) // controller will treat as finalize
        });
        if(!res.ok){ showToast('Finalize failed'); console.error(await res.text()); return; }
        showToast('Finalized. Reloading...');
        setTimeout(()=> location.reload(), 900);
    } catch(e){ console.error(e); showToast('Network error'); }
}

/* unfreeze (testing helper) */
async function unfreezeSheet(sheetId){
    if(!confirm("Unfreeze this sheet? All final logs will be removed.")) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    try {
        const res = await fetch("{{ url('/sheet/unfreeze') }}/" + sheetId, {
            method: "POST",
            headers: { "X-CSRF-TOKEN": csrf, "Content-Type": "application/x-www-form-urlencoded" },
            body: ""
        });
        if(!res.ok){ showToast("Unfreeze failed"); return; }
        showToast("Sheet unfrozen");
        setTimeout(() => location.reload(), 800);
    } catch(e){ console.error(e); showToast("Network error"); }
}

/* small helper to escape strings injected into JS markup */
function addslashes(str){ return (str+'').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0'); }


/* ===== Dark mode (auto-detect + toggle) ===== */
(function(){
    const root = document.documentElement;
    const preferDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    // initial theme: check localStorage, else use system
    let theme = localStorage.getItem('tt_theme') || (preferDark ? 'dark' : 'light');
    function applyTheme(t) {
        if (t === 'dark') {
            document.documentElement.setAttribute('data-theme','dark');
            document.getElementById('themeIcon').innerHTML = '<path d="M6 0a.5.5 0 0 1 .5.5V2A.5.5 0 0 1 6 2v-.5A.5.5 0 0 1 6 0zM10 0a.5.5 0 0 1 .5.5V2A.5.5 0 0 1 10 2v-.5A.5.5 0 0 1 10 0z"/><path d=\"M14 8a6 6 0 1 1-6-6 6 6 0 0 1 6 6z\"/>';
        } else {
            document.documentElement.removeAttribute('data-theme');
            document.getElementById('themeIcon').innerHTML = '<path d=\"M8 0a.5.5 0 0 1 .5.5V2A.5.5 0 0 1 8 2V.5A.5.5 0 0 1 8 0z\"/><circle cx=\"8\" cy=\"8\" r=\"3\"/>';
        }
    }
    applyTheme(theme);
    document.getElementById('themeToggle').addEventListener('click', function(){
        theme = (theme === 'dark') ? 'light' : 'dark';
        localStorage.setItem('tt_theme', theme);
        applyTheme(theme);
    });
})();

</script>

@endsection
