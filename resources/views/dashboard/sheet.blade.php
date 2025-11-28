@extends('layouts.app')

@section('content')

@php
    // safe defaults / fallbacks
    $leaderSheets = $leaderSheets ?? collect();
    $memberAssignments = $memberAssignments ?? collect();

    $primarySheet =
        $primarySheet ??
        ($sheet ?? (null ?? ($leaderSheets->first() ?? (null ?? ($memberAssignments->first()->sheet ?? null)))));

    if ($primarySheet && is_string($primarySheet->sheet_date)) {
        try {
            $primarySheet->sheet_date = \Carbon\Carbon::parse($primarySheet->sheet_date);
        } catch (\Exception $e) {
            $primarySheet->sheet_date = null;
        }
    }

    $empId = $empId ?? (session('emp_id') ?? null);
    $employeeName = $employeeName ?? (session('employee_name') ?? (session('emp_name') ?? null));
    $date = $date ?? now()->toDateString();
    $isLeader = $isLeader ?? false;
    $sheet = $sheet ?? ($primarySheet ?? null);
    $clients = $clients ?? collect();
    $members = $members ?? collect();
    $assignments = $assignments ?? collect();
    $isFinalized = $isFinalized ?? false;
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* --- Core trimmed CSS: use Bootstrap for basics, custom only for fixes/UX --- */

.page-wrap { padding: 18px; }
.header-row { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
.title { font-size:20px; font-weight:700; }
.sub { color:#6b7280; font-size:13px; }

/* layout */
.grid { display:flex; gap:20px; align-items:flex-start; }
.left { flex:2; }
.right { width:320px; }

/* cards */
.card-surface { background:#fff; border-radius:12px; padding:14px; margin-bottom:14px; box-shadow:0 6px 18px rgba(16,24,40,0.06); border:1px solid rgba(13,17,23,0.03); }
.member-card { border-radius:10px; padding:12px; background:linear-gradient(180deg,#fff,#fbfdff); border:1px solid #eef3fb; margin-bottom:12px; }
.task-row { background:#fff; border-radius:8px; padding:12px; border:1px solid #eef2f6; margin-bottom:10px; display:flex; gap:12px; align-items:flex-start; transition: transform .12s ease, box-shadow .12s ease; }
.task-row:hover { transform: translateY(-3px); box-shadow: 0 10px 26px rgba(99,102,241,0.06); }

/* small text */
.small { color:#4b5563; font-size:13px; }
.label { font-weight:600; }

/* inputs */
.input, textarea, select { width:100%; padding:8px 10px; border-radius:8px; border:1px solid #e6eefb; box-sizing:border-box; background:#FAFBFF; }

/* badge-style status pills */
.task-status {
    display:inline-block;
    padding:6px 10px;
    border-radius:999px;
    font-weight:700;
    font-size:12px;
    text-transform:capitalize;
}
.task-status.completed { background: linear-gradient(135deg,#10B981,#059669); color:#fff; }
.task-status.in_progress { background: linear-gradient(135deg,#F59E0B,#D97706); color:#fff; }
.task-status.not_completed { background: linear-gradient(135deg,#EF4444,#C92A2A); color:#fff; }

/* remark truncation */
.remark-limit {
    display:inline-block;
    align-items:center;
    gap:6px;
    max-width:60ch;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: pointer;
    color:#0b5ed7;
    font-weight:600;
}

/* show a small dropdown chevron - hidden by default; JS toggles .show-chevron */
.remark-limit svg { opacity:0.85; transition:transform .12s ease; }
.remark-limit.show-chevron svg { transform: translateY(0); opacity:1; }

/* modal body */
.modal-content.remark-modal { border-radius:12px; }
.member-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
}


/* small responsive */
@media(max-width:980px) {
    .grid { flex-direction:column; }
    .right { width:100%; }
}
/* Fix full remark modal width */
#remarkModal .modal-dialog {
    max-width: 600px !important;
    margin: 1.75rem auto;
}

#remarkModal .modal-body {
    max-height: 60vh;
    overflow-y: auto;
    white-space: pre-wrap;
    word-wrap: break-word;
    font-size: 14px;
}

</style>

<div class="page-wrap">
    <div class="header-row">
        <div>
            <div class="title">Daily Task Sheet</div>
            <div class="sub">Logged as: <strong>{{ $employeeName ?? ($empId ?? 'Unknown') }}</strong></div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <form method="GET" action="{{ route('tasktable') }}" id="dateForm" class="d-flex align-items-center gap-2">
                <input type="date" name="date" value="{{ $date }}" class="input" onchange="document.getElementById('dateForm').submit()" />
            </form>

            @if ($isLeader)
                <a class="btn btn-outline-primary btn-sm" href="{{ route('dashboard.mine', ['date' => $date]) }}">Your Dashboard</a>
            @endif
        </div>
    </div>

    <div class="gradient-shell mt-3" style="border-radius:16px;padding:2px;background:linear-gradient(135deg,#6366F1,#EC4899,#10B981);">
        <div class="inner" style="border-radius:14px;background:#ffffffdd;backdrop-filter:blur(6px);padding:18px;">
            <div class="grid">
                <!-- LEFT: assignments -->
                <div class="left">
                    <div class="card-surface">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div style="font-weight:700;">Assignments</div>
                            @if ($sheet)
                                <div class="small">Team: <span class="badge-role" style="background:linear-gradient(45deg,#6366F1,#3B82F6);color:white;padding:4px 8px;border-radius:999px;font-weight:600;font-size:12px;">#{{ $sheet->team_id }}</span></div>
                            @endif
                        </div>
                        @if (!$sheet)
                            <div class="small mb-2">No sheet for this date.</div>
                            @if ($isLeader)
                                <form method="POST" action="{{ route('sheet.create') }}" class="row gx-2 gy-2 align-items-end" style="max-width:520px;">
                                    @csrf
                                    <div class="col-6">
                                        <label class="small label">Team</label>
                                        <select name="team_id" class="input" required>
                                            @php $teamIds = $members->pluck('team_id')->unique()->values(); @endphp
                                            @foreach ($teamIds as $tid)
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
                            {{-- Leader view --}}
                            @if ($isLeader)
                                @foreach ($members as $m)
                                    @php
                                        $memberId = $m->emp_id;
                                        $memberName = $m->employee->emp_name ?? $memberId;
                                        $memberAssignments = $sheet->assignments->where('member_emp_id', $memberId);
                                    @endphp

                                    <div class="member-card">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div style="font-weight:700;">{{ $memberName }}</div>
                                                <div class="small">ID: {{ $memberId }}
                                                    @if ($m->is_leader)
                                                        — <span><!-- leader icon --><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z"/><path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg></span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="d-flex flex-column align-items-end">
                                                @if (optional($sheet->sheet_date)->isToday() && !$isFinalized)
                                                    <button class="btn btn-ghost btn-sm" type="button" onclick="toggleCreateTask('{{ $memberId }}')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/></svg>
                                                    </button>
                                                @else
                                                    <div class="small">Locked / Read-only</div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- existing assignments (cardized) --}}
                                        <div style="margin-top:10px;">
                                            @forelse($memberAssignments as $a)
                                                <div class="task-row" id="task-{{ $a->id }}">
                                                    <div style="flex:1;">
                                                        <div class="small"><strong>Project:</strong>
                                                            <span style="color:#0b5ed7;font-weight:700;">{{ optional($clients->firstWhere('client_id',$a->client_id))->client_company_name ?? '-' }}</span>
                                                        </div>

                                                        <div class="small"><strong>Task:</strong>
                                                            {{-- safe, escaped, truncated presentation --}}
                                                            @php
                                                                $leader_plain = e($a->leader_remark ?? '-');
                                                                $leader_short = \Illuminate\Support\Str::limit($a->leader_remark ?? '-', 60);
                                                                $leader_short_escaped = e($leader_short);
                                                            @endphp

                                                            <span class="remark-limit" data-full="{{ addslashes($leader_plain) }}" data-plain="{{ $leader_plain }}">
                                                                {{ $leader_short_escaped }}
                                                                <!-- chevron (will be toggled visible via JS only when overflow/multiline) -->
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#6366F1" viewBox="0 0 16 16"><path d="M1.5 5l5 5 5-5H1.5z"/></svg>
                                                            </span>
                                                        </div>

                                                        <div class="small mt-1"><strong>Status:</strong>
                                                            <span class="task-status {{ $a->status ?? 'not_completed' }}">{{ ucfirst($a->status ?? 'not_completed') }}</span>
                                                        </div>

                                                        @if($a->is_submitted && $a->member_remark)
                                                            @php
                                                                $member_plain = e($a->member_remark ?? '-');
                                                                $member_short = \Illuminate\Support\Str::limit($a->member_remark ?? '-', 60);
                                                                $member_short_escaped = e($member_short);
                                                            @endphp
                                                            <div class="static-submitted mt-2">
                                                                <strong>Member remark:</strong>
                                                                <div>
                                                                    <span class="remark-limit" data-full="{{ addslashes($member_plain) }}" data-plain="{{ $member_plain }}">
                                                                        {{ $member_short_escaped }}
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#6366F1" viewBox="0 0 16 16"><path d="M1.5 5l5 5 5-5H1.5z"/></svg>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="d-flex flex-column align-items-end" style="min-width:140px;">
                                                        @if(optional($sheet->sheet_date)->isToday() && !$isFinalized)
                                                            <div class="mb-1">
                                                                <button class="btn btn-outline-primary btn-sm" onclick="openEditAssignment({{ $a->id }})">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M11.096.644a2 2 0 0 1 2.791.036l1.433 1.433a2 2 0 0 1 .035 2.791l-.413.435-8.07 8.995a.5.5 0 0 1-.372.166h-3a.5.5 0 0 1-.234-.058l-.412.412A.5.5 0 0 1 2.5 15h-2a.5.5 0 0 1-.354-.854l1.412-1.412A.5.5 0 0 1 1.5 12.5v-3a.5.5 0 0 1 .166-.372l8.995-8.07zm-.115 1.47L2.727 9.52l3.753 3.753 7.406-8.254z"/></svg>
                                                                </button>
                                                            </div>

                                                            <form method="POST" action="{{ route('assign.delete', $a->id) }}">
                                                                @csrf
                                                                <button class="btn btn-danger btn-sm" type="submit">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1z"/></svg>
                                                                </button>
                                                            </form>
                                                        @else
                                                            <div class="badge-role" style="background:#eef2ff;color:#0b3aa3;padding:6px 10px;border-radius:999px;font-weight:600;">Locked</div>
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
                                                                        @foreach ($clients as $c)
                                                                            <option value="{{ $c->client_id }}" @if(($a->client_id ?? '') == $c->client_id) selected @endif>{{ $c->client_company_name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>

                                                                <div class="col-7">
                                                                    <label class="small label">AssignTask</label>
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

                                        {{-- CREATE TASK table --}}
                                        <div id="create-task-{{ $memberId }}" style="display:none; margin-top:12px;">
                                            <div class="small" style="font-weight:600; margin-bottom:6px;">Add tasks for {{ $memberName }}</div>

                                            <table class="table-rows">
                                                <thead>
                                                    <tr><th style="width:45%;">Project</th><th>Remark</th><th style="width:80px;">Action</th></tr>
                                                </thead>
                                                <tbody id="rows-{{ $memberId }}">
                                                    <tr class="task-input-row">
                                                        <td>
                                                            <select name="client_id[]" class="input client-select">
                                                                <option value="">-- select Project --</option>
                                                                @foreach ($clients as $c)
                                                                    <option value="{{ $c->client_id }}">{{ $c->client_company_name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td><textarea name="leader_remark[]" class="input" rows="2"></textarea></td>
                                                        <td style="text-align:center;">
                                                            <button type="button" class="btn btn-primary btn-sm" onclick="addNewTaskRow('{{ $memberId }}')">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/></svg>
                                                            </button>
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
                                    @if ($isFinalized)
                                        <div class="small">This sheet has been finalized and is read-only.</div>
                                    @else
                                        <div class="small">When ready press <strong>Save Day Log</strong> to snapshot & lock the sheet.</div>
                                        <div class="mt-2"><button class="btn btn-primary" onclick="finalizeDay({{ $sheet->id }})">Save Day Log (final snapshot)</button></div>
                                    @endif
                                </div>
                                <div class="mt-2"><button class="btn btn-ghost btn-sm" onclick="unfreezeSheet({{ $sheet->id }})">Unfreeze Sheet (Testing)</button></div>
                            @else
                                {{-- MEMBER view --}}
                                <div class="member-card">
                                    <div style="font-weight:700;">You: {{ $employeeName ?? $empId }}</div>

                                    @if ($assignments->isEmpty())
                                        <div class="small mt-2">No tasks assigned for today.</div>
                                    @endif

                                    @foreach($assignments as $a)
                                        <div class="task-row" id="task-{{ $a->id }}">
                                            <div style="flex:1;">
                                                <div class="small"><strong>Project:</strong>
                                                    <span style="color:#0b5ed7;font-weight:700;">{{ optional($clients->firstWhere('client_id',$a->client_id))->client_company_name ?? '-' }}</span>
                                                </div>

                                                <div class="small"><strong>Remark:</strong>
                                                    @php $leader_short = \Illuminate\Support\Str::limit($a->leader_remark ?? '-', 60); @endphp
                                                    <span class="remark-limit" data-full="{{ addslashes(e($a->leader_remark ?? '-')) }}" data-plain="{{ e($a->leader_remark ?? '-') }}">{{ e($leader_short) }}
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#6366F1" viewBox="0 0 16 16"><path d="M1.5 5l5 5 5-5H1.5z"/></svg>
                                                    </span>
                                                </div>
                                                @if($isFinalized || $a->is_submitted)
                                                    <div class="static-submitted mt-2">
                                                        <div><strong>Status:</strong> <span class="task-status {{ $a->status ?? 'not_completed' }}">{{ ucfirst($a->status ?? 'not_completed') }}</span></div>
                                                        <div style="margin-top:6px;"><strong>Member remark:</strong>
                                                            <span class="remark-limit" data-full="{{ addslashes(e($a->member_remark ?? '-')) }}" data-plain="{{ e($a->member_remark ?? '-') }}">{{ e(\Illuminate\Support\Str::limit($a->member_remark ?? '-', 60)) }}
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#6366F1" viewBox="0 0 16 16"><path d="M1.5 5l5 5 5-5H1.5z"/></svg>
                                                            </span>
                                                        </div>
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

                                                        <div class="mt-2"><button class="btn btn-primary btn-sm" type="submit">Submit</button></div>
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
                                <div class="small"><strong>Team:</strong> {{ $sheet->team->team_name ?? 'Team ' . $sheet->team_id }}</div>
                                <div class="small"><strong>Leader:</strong> {{ DB::table('employee_tbl')->where('emp_id',$sheet->leader_emp_id)->value('emp_name') ?? $sheet->leader_emp_id }}</div>
                            </div>

                            <div class="card-surface mb-3" style="padding:10px;">
                                <div style="font-weight:700;">Members</div>
                                <div class="member-list mt-2">
                                    @foreach($members as $tm)
                                        @php $e = $tm->employee ?? null; $name = $e->emp_name ?? $tm->emp_id; @endphp
                                        <div class="member-row">
                                            <div><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4 8a.5.5 0 0 1 .5-.5h5.793L8.146 5.354a.5.5 0 1 1 .708-.708l3 3a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708-.708L10.293 8.5H4.5A.5.5 0 0 1 4 8"/></svg></div>
                                            <div style="margin-left:8px;">
                                                <div style="font-weight:600;">{{ $name }}</div>
                                                <div class="small">ID: {{ $tm->emp_id }}</div>
                                            </div>
                                            <div class="ms-auto">@if($tm->is_leader)<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z"/><path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>@endif</div>
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
            </div>
        </div>
    </div>
</div>

<!-- toast + modal -->
<div id="toast" class="toast" style="background:linear-gradient(90deg,#6366F1,#EC4899);box-shadow:0 4px 16px rgba(99,102,241,0.4);color:#fff;padding:10px 14px;border-radius:8px;position:fixed;right:20px;bottom:20px;z-index:9999;opacity:0;transform:translateY(6px);transition:all .18s;">
</div>
<!-- FINAL SNAPSHOT CONFIRM MODAL -->
<div class="modal fade" id="finalConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header">
                <h5 class="modal-title">Finalize Day Log</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:14px;">
                    To finalize and lock today's sheet, type <strong>today's date</strong> 
                    or <strong>your name</strong> below:
                </p>

                <input type="text" id="finalConfirmInput" class="form-control" placeholder="Type date or name">
                <small id="finalConfirmError" class="text-danger mt-1 d-block" style="display:none;"></small>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="confirmFinalizationNow()">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- MEMBER TASK SUBMIT CONFIRM MODAL -->
<div class="modal fade" id="taskSubmitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header">
                <h5 class="modal-title">Submit Task</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:14px;">
                    To submit this task, type <strong>SUBMIT</strong> below:
                </p>

                <input type="text" id="taskSubmitInput" class="form-control" placeholder="Type SUBMIT">
                <small id="taskSubmitError" class="text-danger mt-1 d-block" style="display:none;"></small>

                <input type="hidden" id="taskSubmitAssignId">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="confirmTaskSubmitNow()">Submit</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="remarkModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content remark-modal">
            <div class="modal-header">
                <h5 class="modal-title">Full Remark</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="remarkModalBody" style="white-space:pre-wrap;"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* Utility to show toast */
function showToast(msg, t = 1400) {
    const el = document.getElementById('toast');
    el.innerText = msg;
    el.style.opacity = '1';
    el.style.transform = 'translateY(0)';
    setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateY(6px)'; }, t);
}

/* Toggle create task block */
function toggleCreateTask(memberId) {
    const el = document.getElementById('create-task-' + memberId);
    if (!el) return;
    el.style.display = (el.style.display === '' || el.style.display === 'none') ? 'block' : 'none';
    const st = document.getElementById('status-' + memberId);
    if (st) st.innerText = '';
}

/* Add/Remove task rows (leader) */
function addNewTaskRow(memberId) {
    const tbody = document.getElementById('rows-' + memberId);
    if (!tbody) return;
    const tr = document.createElement('tr');
    tr.className = 'task-input-row';
    tr.innerHTML = `
        <td>
            <select name="client_id[]" class="input client-select">
                <option value="">-- select Project --</option>
                @foreach($clients as $c)
                    <option value="{{ $c->client_id }}">{{ addslashes($c->client_company_name) }}</option>
                @endforeach
            </select>
        </td>
        <td><textarea name="leader_remark[]" class="input" rows="2"></textarea></td>
        <td style="text-align:center;"><button type="button" class="btn btn-danger btn-sm" onclick="removeThisRow(this)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-square" viewBox="0 0 16 16">
  <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/>
  <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
</svg></button></td>
    `;
    tbody.appendChild(tr);
}
function removeThisRow(btn) { const tr = btn.closest('tr'); if(tr) tr.remove(); }

/* Save tasks via AJAX (leader) */
async function saveTasksForMember(memberId, sheetId) {
    const tableBody = document.getElementById('rows-' + memberId);  
    if (!tableBody) return;
    const rows = tableBody.querySelectorAll('tr');
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
            if (res.ok) created++;
            else { console.error('assign failed', await res.text()); showToast('Save failed for one row'); }
        } catch (e) { console.error(e); showToast('Network error'); }
    }
    if (statusEl) statusEl.innerText = 'Saved ' + created + ' task(s). Reloading...';
    setTimeout(() => location.reload(), 700);
}

/* Member submit (AJAX) -> auto-reload on success */
// async function submitMemberForm(ev, assignId) {
//     ev.preventDefault();
async function submitMemberForm(ev, assignId) {
    ev.preventDefault();

    // confirmation prompt
    const confirmText = prompt("To submit your task, type: SUBMIT");

    if (!confirmText || confirmText.trim().toUpperCase() !== "SUBMIT") {
        alert("Submission cancelled.");
        return false;
    }

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
        showToast('Submitted');
        // brief visual update then reload
        setTimeout(() => location.reload(), 650);
    } catch (e) { console.error(e); showToast('Network error'); }
    return false;
}

/* toggle remark box for member */
function toggleRemarkBox(assignId, radioEl) {
    const box = document.getElementById('member-remark-box-' + assignId);
    if (!box) return;
    box.style.display = (radioEl.value === 'completed') ? 'none' : 'block';
}

/* inline edit open/close */
function openEditAssignment(id) { const el = document.getElementById('edit-box-' + id); if (el) el.style.display = 'block'; }
function closeEditAssignment(id) { const el = document.getElementById('edit-box-' + id); if (el) el.style.display = 'none'; }

/* leader save */
async function saveLeaderReply(assignId) {
    const form = document.getElementById('edit-form-' + assignId);
    if (!form) return;
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
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: payload.toString()
        });
        if (!res.ok) { showToast('Update failed'); console.error(await res.text()); return; }
        showToast('Saved');
        closeEditAssignment(assignId);
        setTimeout(() => location.reload(), 600);
    } catch (e) { console.error(e); showToast('Network error'); }
}

/* save target */
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
function resetTarget() { if(confirm('Reset target to empty?')) document.getElementById('today_target').value = ''; }

/* finalize/unfreeze */
// async function finalizeDay(sheetId) {
//     if(!confirm('This will finalize the day, create snapshot and lock editing. Continue?')) return;
//     const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
//     try {
//         const res = await fetch("{{ url('/sheet/save_day') }}/" + sheetId, {
//             method: 'POST',
//             headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
//             body: new URLSearchParams({'finalize':'1'})
//         });
//         if(!res.ok){ showToast('Finalize failed'); console.error(await res.text()); return; }
//         showToast('Finalized. Reloading...');
//         setTimeout(()=>location.reload(),900);
//     } catch(e){ console.error(e); showToast('Network error'); }
// }
async function finalizeDay(sheetId) {

    // Fancy prompt with validation
    const today = new Date().toISOString().slice(0, 10); // yyyy-mm-dd
    const userName = "{{ $employeeName ?? $empId }}";

    const input = prompt(
        "To finalize and lock today's sheet, type either:\n\n" +
        "1. Today's date: " + today + "\n" +
        "OR\n" +
        "2. Your name: " + userName + "\n\n" +
        "This action cannot be undone."
    );

    if (!input) return;

    // validate: either matches date or name
    if (input.trim() !== today && input.trim().toLowerCase() !== userName.trim().toLowerCase()) {
        alert("Incorrect confirmation. Finalization cancelled.");
        return;
    }

    // proceed with original logic
    const csrf = document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content');
    try {
        const res = await fetch("{{ url('/sheet/save_day') }}/" + sheetId, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ 'finalize': '1' })
        });
        if (!res.ok) { showToast('Finalize failed'); console.error(await res.text()); return; }

        showToast('Finalized. Reloading...');
        setTimeout(() => location.reload(), 900);
    } catch (e) {
        console.error(e);
        showToast('Network error');
    }
}
let FINAL_SHEET_ID = null;

function finalizeDay(sheetId) {
    FINAL_SHEET_ID = sheetId;
    document.getElementById('finalConfirmInput').value = "";
    document.getElementById('finalConfirmError').style.display = "none";
    new bootstrap.Modal(document.getElementById('finalConfirmModal')).show();
}

async function confirmFinalizationNow() {
    const val = document.getElementById("finalConfirmInput").value.trim();
    const today = new Date().toISOString().slice(0, 10);
    const userName = "{{ $employeeName ?? $empId }}";

    if (val !== today && val.toLowerCase() !== userName.toLowerCase()) {
        const err = document.getElementById("finalConfirmError");
        err.innerText = "Incorrect — type today's date or your name.";
        err.style.display = "block";
        return;
    }

    // Proceed to finalize
    const modal = bootstrap.Modal.getInstance(document.getElementById('finalConfirmModal'));
    modal.hide();

    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const res = await fetch("{{ url('/sheet/save_day') }}/" + FINAL_SHEET_ID, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": csrf,
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({ finalize: "1" })
    });

    if (!res.ok) {
        showToast("Finalize failed");
        return;
    }

    showToast("Finalized Successfully");
    setTimeout(() => location.reload(), 900);
}


async function unfreezeSheet(sheetId) {
    if(!confirm("Unfreeze this sheet? All final logs will be removed.")) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    try {
        const res = await fetch("{{ url('/sheet/unfreeze') }}/" + sheetId, {
            method: "POST",
            headers: {"X-CSRF-TOKEN": csrf, "Content-Type": "application/x-www-form-urlencoded"},
            body: ""
        });
        if(!res.ok){ showToast("Unfreeze failed"); return; }
        showToast("Sheet unfrozen");
        setTimeout(()=>location.reload(),800);
    } catch(e){ console.error(e); showToast("Network error"); }
}

/* ===== Remark overflow detection & click handler =====
   - For each .remark-limit element we detect if the text is truncated/more-than-allowed lines.
   - The chevron is shown only if the content overflows vertically (approx > 4 lines).
   - Clicking opens the modal with the full plain-text (escaped via Blade earlier).
*/
function initRemarkChecks() {
    const items = document.querySelectorAll('.remark-limit');
    items.forEach(el => {
        // compute lines: use scrollHeight and computed line-height
        const cs = window.getComputedStyle(el);
        const lineHeight = parseFloat(cs.lineHeight) || 18;
        const clientH = el.clientHeight;
        const scrollH = el.scrollHeight;
        const lines = Math.round(scrollH / lineHeight);
        // If lines > 1 OR scrollWidth > clientWidth -> show chevron
        if (lines >= 2 || el.scrollWidth > el.clientWidth) {
            el.classList.add('show-chevron');
        } else {
            el.classList.remove('show-chevron');
        }

        el.addEventListener('click', function () {
            // data-plain is pre-escaped plain text (set by Blade), but we'll set innerText to be safe
            const full = el.getAttribute('data-plain') || el.getAttribute('data-full') || el.innerText;
            document.getElementById('remarkModalBody').innerText = full;
            new bootstrap.Modal(document.getElementById('remarkModal')).show();
        });
    });
}

// Init on DOM ready and on window resize (to detect overflow changes)
document.addEventListener('DOMContentLoaded', () => {
    initRemarkChecks();
    window.setTimeout(initRemarkChecks, 250); // slight delay in case fonts load
});
window.addEventListener('resize', () => { window.setTimeout(initRemarkChecks, 120); });
</script>

@endsection
