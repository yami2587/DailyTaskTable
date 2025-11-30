{{-- resources/views/admin/dashboard.blade.php --}}
@php
    $teams = $teams ?? collect();
@endphp

@extends('layouts.app')

@section('content')
    @php
        // Provided by controller
        $teams = $teams ?? collect();
        $selectedTeamId = $selectedTeamId ?? null;
        $date = $date ?? date('Y-m-d');
        $sheet = $sheet ?? null;
        $members = $members ?? collect();
        $assignments = $assignments ?? collect();
        $clients = $clients ?? collect();
        $summary = $summary ?? ['total_tasks' => 0, 'completed' => 0, 'in_progress' => 0, 'not_completed' => 0];
    @endphp

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- NOTE: If your layout already includes Bootstrap, remove these lines --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- bootstrap-select CSS (only once) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">

    <style>
        /* ===== micro animations (kept but optimized) ===== */
        .team-item,
        .task-card,
        .member-card,
        .card-surface {
            transition: transform .22s ease, box-shadow .22s ease, opacity .22s ease;
            will-change: transform, box-shadow, opacity;
        }

        .team-item:hover { transform: translateX(3px); }
        .task-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(16,24,40,0.06); }
        .member-card:hover { transform: translateY(-2px); }

        .fade-soft { animation: fadeSoft .28s ease both; }
        @keyframes fadeSoft {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* layout / UI - preserved exactly as requested */
        .admin-wrap { padding: 18px; }
        .header-row { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
        .title { font-size:20px; font-weight:700; color:#111827; }
        .sub { color:#6b7280; font-size:13px; }

        .page-shell { border-radius:14px; padding:14px; background:linear-gradient(180deg,#ffffffcc,#fbfbffcc); box-shadow:0 10px 30px rgba(16,24,40,0.06); }
        .admin-grid { display:flex; gap:18px; align-items:flex-start; }
        .fixed-admin-layout { min-height:100%; height:auto !important; overflow:visible !important; }
        .admin-grid { height:auto !important; align-items:flex-start; }

        .sidebar { width:260px; position:sticky; top:20px; height:calc(100vh - 150px); overflow-y:auto !important; padding-right:6px; background:transparent; }
        .team-item { padding:10px 12px; border-radius:10px; cursor:pointer; display:flex; align-items:center; gap:10px; justify-content:space-between; color:#374151; text-decoration:none; border:1px solid transparent; margin-bottom:8px; }
        .team-item .icon-flat { width:40px; height:40px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#EEF2FF,#F8FAFF); border:1px solid #e6eefb; color:#0b5ed7; flex-shrink:0; }
        .team-item.active { background:linear-gradient(90deg,#6366F1,#A78BFA); color:white; box-shadow:0 6px 18px rgba(99,102,241,0.12); border-color:rgba(255,255,255,0.06); }

        .pane { flex:1; height:100%; overflow-y:auto; padding-right:8px; }

        .top-tabs { display:flex; gap:10px; margin-bottom:12px; flex-wrap:wrap; }
        .tab-btn { padding:8px 12px; border-radius:999px; font-weight:700; cursor:pointer; border:none; background:#f3f4f6; }
        .tab-btn.active { background:linear-gradient(90deg,#6366F1,#A855F7); color:white; }

        .card-surface { border-radius:10px; padding:12px; background:#fff; box-shadow:0 6px 18px rgba(16,24,40,0.04); margin-bottom:12px; }
        .member-card { padding:10px; border-radius:10px; background:#fff; border:1px solid #eef2ff; display:flex; gap:12px; align-items:center; }
        .task-card { padding:12px; border-radius:12px; background:#ffffffdd; border:1px solid #e5e7eb; margin-bottom:10px; display:flex; justify-content:space-between; gap:10px; align-items:flex-start; }
        .task-left { flex:1; max-width:72ch; word-break:break-word; }
        .task-title { font-weight:700; color:#0b5ed7; }
        .task-desc { color:#374151; margin-top:6px; max-height:3.6em; overflow:hidden; text-overflow:ellipsis; }

        .task-status { padding:6px 12px; border-radius:999px; font-weight:700; font-size:12px; text-transform:capitalize; }
        .task-status.completed { background:linear-gradient(135deg,#10B981,#059669); color:#fff; }
        .task-status.in_progress { background:linear-gradient(135deg,#F59E0B,#D97706); color:#fff; }
        .task-status.not_completed { background:linear-gradient(135deg,#EF4444,#B91C1C); color:#fff; }

        .small { font-size:13px; color:#6b7280; }
        .meta { font-size:13px; color:#6b7280; }

        .remark-full { white-space:pre-wrap; word-break:break-word; max-width:78ch; }
        #deleteConfirmInput { letter-spacing:0.6px; }

        @media(max-width:980px) {
            .admin-grid { flex-direction:column; }
            .sidebar { width:100%; order:2; position:relative; height:auto; }
            .pane { order:1; }
        }

        /* subtle premium touches kept */
        .icon-flat { border-radius:12px !important; transition:0.2s ease; box-shadow:0 3px 10px rgba(0,0,0,0.05); }
        .icon-flat:hover { transform:scale(1.07); box-shadow:0 6px 18px rgba(0,0,0,0.10); }
        .summary-card, .card-surface:hover { transform:translateY(-3px); box-shadow:0 10px 20px rgba(0,0,0,0.07); }

        /* small animation helper for JS-driven transitions */
        .fade-in { animation: fadeSoft .22s ease both; }
    </style>

    <div class="admin-wrap fade-soft">
        <div class="header-row">
            <div>
                <div class="title">Team Daily Reports</div>
                <div class="sub">Browse teams, sheets & manage teams</div>
            </div>
                        <div class="d-flex gap-2 align-items-center">
                {{-- date select + last 6 days pills --}}
                <form id="adminDateForm" method="GET" action="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="team_id" value="{{ $selectedTeamId }}" id="selectedTeamInput">
                    <input type="date" name="date" value="{{ $date }}" onchange="document.getElementById('adminDateForm').submit()" class="form-control form-control-sm" />
                </form>

                @php
                    $days = collect();
                    for ($i = 0; $i < 6; $i++) {
                        $days->push(now()->subDays($i)->format('Y-m-d'));
                    }
                @endphp

                <div class="d-flex gap-2 ms-2">
                    @foreach ($days as $d)
                        <a href="{{ route('admin.dashboard', ['team_id' => $selectedTeamId, 'date' => $d]) }}"
                            class="btn btn-sm {{ $d == $date ? 'btn-primary' : 'btn-outline-primary' }}"
                            style="border-radius:50px; font-weight:600;">
                            {{ \Carbon\Carbon::parse($d)->format('d M') }}
                        </a>
                    @endforeach
                </div>

                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </div>

        <div class="page-shell fade-soft">
            {{-- Only two tabs as requested --}}
            <div class="top-tabs">
                <button class="tab-btn active" data-tab="reports" id="tab-reports">Daily Reports</button>
                <button class="tab-btn" data-tab="teams" id="tab-teams">Teams</button>
            </div>

            <div class="fixed-admin-layout">
                <div class="admin-grid">
                    {{-- SIDEBAR: teams --}}
                    <div class="sidebar fade-soft">
                        {{-- Summary: only shows when a team is selected (keeps original behavior) --}}
                        @if ($selectedTeamId)
                            <div style="margin-top:6px;">
                                <div style="font-weight:700; margin-bottom:6px;">Summary ({{ \Carbon\Carbon::parse($date)->format('d M, Y') }})</div>
                                <div class="d-flex gap-2 mb-2 fade-soft">
                                    <div class="p-2" style="border-radius:8px;background:#fff;border:1px solid #eef2ff;min-width:92px;text-align:center;">
                                        <div class="small">Tasks</div>
                                        <div style="font-weight:700">{{ $summary['total_tasks'] }}</div>
                                    </div>
                                    <div class="p-2" style="border-radius:8px;background:#fff;border:1px solid #eef2ff;min-width:92px;text-align:center;">
                                        <div class="small">Completed</div>
                                        <div style="font-weight:700;color:#065f46;">{{ $summary['completed'] }}</div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 fade-soft">
                                    <div class="p-2" style="border-radius:8px;background:#fff;border:1px solid #eef2ff;min-width:92px;text-align:center;">
                                        <div class="small">In Progress</div>
                                        <div style="font-weight:700;">{{ $summary['in_progress'] }}</div>
                                    </div>
                                    <div class="p-2" style="border-radius:8px;background:#fff;border:1px solid #eef2ff;min-width:92px;text-align:center;">
                                        <div class="small">Not Done</div>
                                        <div style="font-weight:700;color:#7a0f0f;">{{ $summary['not_completed'] }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div style="margin-bottom:8px; font-weight:700;">Teams</div>
                        <div class="list-group mb-3">
                            @foreach ($teams as $t)
                                @php
                                    $tid = $t->id ?? $t->team_id;
                                    $active = $tid == $selectedTeamId;
                                @endphp

                                <a href="{{ route('admin.dashboard', ['team_id' => $tid, 'date' => $date]) }}" class="team-item {{ $active ? 'active' : '' }}">
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <div class="icon-flat" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z" />
                                                <path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                                            </svg>
                                        </div>
                                        <div style="font-weight:600;">{{ $t->team_name }}</div>
                                    </div>
                                    <div class="small text-muted">
                                        {{ \App\Models\TeamMember::where('team_id', $tid)->count() }} members
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- MAIN PANE: Reports --}}
                    <div class="pane fade-soft" id="panel-reports">
                        @if (!$selectedTeamId)
                            <div class="alert alert-secondary fade-soft">No team selected. Please choose a team from the left.</div>
                        @else
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 style="margin:0;">Team: {{ $sheet->team->team_name ?? 'Team ' . $selectedTeamId }}</h5>
                                    <div class="small">Date: {{ \Carbon\Carbon::parse($date)->format('d M, Y') }}</div>
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="{{ route('admin.dashboard', ['team_id' => $selectedTeamId, 'date' => $date]) }}" class="btn btn-primary btn-sm">Refresh</a>
                                    <button class="btn btn-outline-primary btn-sm" onclick="openManageMembersModal({{ $selectedTeamId }})">Manage Members</button>
                                </div>
                            </div>

                            {{-- Members summary card (compact) --}}
                            <div class="card-surface mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div style="font-weight:700;">Team Members</div>
                                    <div class="small text-muted">{{ $members->count() }} members</div>
                                </div>

                                <div class="mt-3 row g-2">
                                    @foreach ($members as $m)
                                        <div class="col-12 col-md-6">
                                            <div class="member-card fade-soft">
                                                <div class="icon-flat" aria-hidden="true">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor">
                                                        <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z" />
                                                        <path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                                                    </svg>
                                                </div>

                                                <div style="margin-left:6px; width:100%;">
                                                    <div style="font-weight:700; display:flex; align-items:center; gap:8px;">
                                                        <span>{{ $m->employee->emp_name ?? $m->emp_id }}</span>
                                                        @if ($m->is_leader)
                                                            <span class="small text-primary">— Leader</span>
                                                        @endif

                                                        {{-- EX-MEMBER BADGE: only for past dates when the employee record no longer exists --}}
                                                        @if(!$date || \Carbon\Carbon::parse($date)->isPast())
                                                            @if(!$m->employee)
                                                                <span class="badge bg-danger ms-1">Ex-Member</span>
                                                            @endif
                                                        @endif
                                                    </div>

                                                    @if (!empty($m->employee->emp_designation ?? false))
                                                        <div class="small text-muted">{{ $m->employee->emp_designation }}</div>
                                                    @endif
                                                </div>

                                                <div class="ms-auto small text-right">
                                                    Task: <strong>{{ $assignments->where('member_emp_id', $m->emp_id)->count() }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    @if ($members->isEmpty())
                                        <div class="col-12">
                                            <div class="small text-muted">No members found for this team.</div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Team Daily Target (Admin) --}}
                            @if($sheet)
                                <div class="card-surface mb-3 fade-soft">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div style="font-weight:700;">Team Daily Target</div>
                                        <div class="small text-muted">{{ \Carbon\Carbon::parse($date)->format('d M, Y') }}</div>
                                    </div>

                                    @if(\Carbon\Carbon::parse($date)->isToday())
                                        <textarea id="admin_team_target" class="form-control mt-2" rows="4">{{ $sheet->target_text ?? '' }}</textarea>
                                        <div class="d-flex gap-2 mt-2">
                                            <button class="btn btn-primary btn-sm" onclick="saveAdminTeamTarget({{ $sheet->id }})">Save Target</button>
                                            <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('admin_team_target').value=''">Reset</button>
                                        </div>
                                    @else
                                        <div class="small mt-2" style="min-height:80px; white-space:pre-wrap;">{{ $sheet->target_text ?? 'No target set for this day.' }}</div>
                                    @endif
                                </div>
                            @endif

                            {{-- Tasks grouped by member --}}
                            <div>
                                @if (!$sheet)
                                    <div class="alert alert-warning fade-soft">No sheet exists for this date.</div>
                                @endif

                                @if ($sheet && $assignments->isEmpty())
                                    <div class="alert alert-info fade-soft">No tasks added for today.</div>
                                @endif

                                @if ($sheet && $assignments->isNotEmpty())
                                    @php $grouped = $assignments->groupBy('member_emp_id'); @endphp

                                    @foreach ($grouped as $memberId => $rows)
                                        @php
                                            $mem = $members->firstWhere('emp_id', $memberId);
                                            $name = $mem ? $mem->employee->emp_name ?? $memberId : $memberId;
                                        @endphp

                                        <div class="mt-3" style="font-weight:800; display:flex; gap:8px; align-items:center;">
                                            <div class="icon-flat" style="width:40px;height:40px; display:flex; align-items:center; justify-content:center;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor">
                                                    <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z" />
                                                    <path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                                                </svg>
                                            </div>
                                            <div>
                                                {{ $name }}
                                                {{-- Ex-member badge in assignments list when no employee relation exists (and date is past) --}}
                                                @if(!$date || \Carbon\Carbon::parse($date)->isPast())
                                                    @if(!$mem || !$mem->employee)
                                                        <span class="badge bg-danger ms-1">Ex-Member</span>
                                                    @endif
                                                @endif
                                                <div class="small text-muted">{{ $rows->count() }} task(s)</div>
                                            </div>
                                        </div>

                                        @foreach ($rows as $a)
                                            <div class="task-card fade-soft">
                                                <div class="task-left">
                                                    <div class="task-title">
                                                        {{ optional($clients->firstWhere('client_id', $a->client_id))->client_company_name ?? '—' }}
                                                    </div>
                                                    <div class="task-desc" title="{{ $a->leader_remark ?? ($a->task_description ?? '') }}">
                                                        {{ \Illuminate\Support\Str::limit($a->leader_remark ?? ($a->task_description ?? '-'), 240) }}
                                                    </div>

                                                    <div class="meta mt-2">
                                                        <strong>Member remark:</strong>
                                                        <span class="small">{{ \Illuminate\Support\Str::limit($a->member_remark ?? '-', 100) }}</span>
                                                        @if (strlen($a->member_remark ?? '') > 100)
                                                            &nbsp;<a href="#" class="ms-2 view-remark" data-title="Member remark" data-content="{{ e($a->member_remark) }}">View</a>
                                                        @endif
                                                        @if (strlen($a->leader_remark ?? '') > 240)
                                                            &nbsp;<a href="#" class="ms-2 view-remark" data-title="Leader remark" data-content="{{ e($a->leader_remark) }}">View</a>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div style="min-width:150px; text-align:right;">
                                                    <div><span class="task-status {{ $a->status ?? 'not_completed' }}">{{ ucfirst($a->status ?? 'not_completed') }}</span></div>
                                                    <div class="small text-muted mt-2">Submitted: {{ $a->is_submitted ? 'Yes' : 'No' }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endforeach
                                @endif
                            </div>
                        @endif
                    </div>
                    {{-- TEAMS panel (list + inline actions). Note: Members button opens inline modal (no navigation). --}}
                    <div class="pane fade-soft" id="panel-teams" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div style="font-weight:700;">All Teams</div>
                            <div>
                                <button class="btn btn-primary btn-sm" onclick="openCreateTeamModal()">Create Team</button>
                            </div>
                        </div>

                        <div class="row g-2">
                            @foreach ($teams as $t)
                                <div class="col-12 col-md-6">
                                    <div class="card-surface fade-soft">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div style="font-weight:800;">{{ $t->team_name }}</div>
                                                @if (!empty($t->description))
                                                    <div class="small text-muted mt-1">{{ \Illuminate\Support\Str::limit($t->description, 160) }}</div>
                                                @endif
                                            </div>

                                            <div class="text-end">
                                                {{-- Members: open manage modal with team preselected --}}
                                                <button class="btn btn-outline-primary btn-sm mb-1" onclick="openManageMembersModal({{ $t->id }}, true)">Members</button>

                                                {{-- Edit: opens modal (inline) --}}
                                                <button class="btn btn-outline-primary btn-sm mb-1" onclick="openEditTeamModal({{ $t->id }}, '{{ e($t->team_name) }}', '{{ e($t->description ?? '') }}')">Edit</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @if ($teams->isEmpty())
                                <div class="col-12">
                                    <div class="small text-muted">No teams available.</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div> {{-- admin-grid --}}
            </div> {{-- fixed-admin-layout --}}
        </div> {{-- page-shell --}}
    </div> {{-- admin-wrap --}}
    {{-- ----- MODALS ----- --}}

    {{-- Remark modal (view full text) --}}
    <div class="modal fade" id="remarkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="remarkModalTitle">Remark</h5>
                    <small class="text-muted ms-2">click outside the box to close</small>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="remarkModalBody" style="white-space:pre-wrap;"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Team Modal --}}
    <div class="modal fade" id="createTeamModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="createTeamForm" method="POST" action="{{ route('team.store') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="small">Team Name</label>
                    <input name="team_name" class="form-control mb-2" required>
                    <label class="small">Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Team Modal --}}
    <div class="modal fade" id="editTeamModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editTeamForm" method="POST" class="modal-content" data-tid="" data-tname="">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="small">Team Name</label>
                    <input name="team_name" id="edit_team_name" class="form-control mb-2" required>
                    <label class="small">Description</label>
                    <textarea name="description" id="edit_team_description" class="form-control" rows="3"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-danger" id="editDeleteBtn">Delete</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Manage Members Modal (Add / Remove members inline; uses TeamController routes) --}}
    <div class="modal fade" id="manageMembersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Members — <span id="manageModalTeamName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div style="font-weight:700;">Current Members</div>
                            <div id="membersListArea" class="mt-2">
                                {{-- fallback server-rendered list --}}
                                @foreach ($members as $m)
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div>
                                            <div style="font-weight:700;">{{ $m->employee->emp_name ?? $m->emp_id }}
                                                @if(!$date || \Carbon\Carbon::parse($date)->isPast())
                                                    @if(!$m->employee)
                                                        <span class="badge bg-danger ms-1">Ex-Member</span>
                                                    @endif
                                                @endif
                                            </div>
                                            <div class="small text-muted">@if ($m->is_leader) Leader @endif</div>
                                        </div>
                                        <form method="POST" class="remove-member-form" data-team-id="{{ $selectedTeamId }}" data-member-id="{{ $m->id }}" action="{{ route('team.members.remove', ['team' => $selectedTeamId, 'member' => $m->id]) }}">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm">Remove</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div style="font-weight:700;">Add Member</div>
                            <form id="addMemberForm" method="POST" action="" class="mt-2">
                                @csrf
                                <input type="hidden" name="team_id" id="addMemberTeamId" value="">
                                <div class="mb-2">
                                    <label class="small">Employee</label>
                                    <select name="emp_id" id="addMemberSelect" class="selectpicker form-control" data-live-search="true" data-dropup-auto="false" data-container="body" title="Search employee..." data-size="7" required>
                                        
                                        <option value="">-- choose employee --</option>
                                        @foreach ($employees as $e)
                                            <option value="{{ $e->emp_id }}">{{ $e->emp_name }} ({{ $e->emp_id }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="small">Is Leader?</label>
                                    <select name="is_leader" class="form-select">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>

                                <div>
                                    <button class="btn btn-primary btn-sm">Add Member</button>
                                </div>
                            </form>
                        </div>
                    </div> {{-- row --}}
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Team Modal --}}
    <div class="modal fade" id="deleteTeamModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="deleteTeamForm" class="modal-content">
                @csrf @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Delete Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p class="mb-2">This action <strong>cannot</strong> be undone. Deleting a team removes it and its membership links. If tasks/sheets are linked to this team you will lose the group association.</p>
                    <p class="mb-2">To confirm deletion, type the team name exactly:</p>

                    <div class="mb-2">
                        <input type="text" id="deleteConfirmInput" class="form-control" placeholder="Type team name to confirm">
                    </div>

                    <div class="mt-2">
                        <strong>Team:</strong> <span id="deleteTeamName" style="font-weight:700"></span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button id="deleteTeamButton" class="btn btn-danger" disabled>Delete</button>
                </div>
            </form>
        </div>
    </div>

    {{-- js includes (only once) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>

    <script>
    /* =========================================================
       Unified, cleaned JS: one place for all behaviors
       - No duplicate functions
       - Modal-safe selectpicker init
       - Delegated event-handling for dynamic content
       - Async fetch with graceful fallback
       - Small JS animations (fade-in classes)
       ========================================================= */

    (function() {
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        /* ------------------------------
           Utility: safe selectpicker init
           ------------------------------ */
        function initSelectpicker(selector) {
            const $el = $(selector);
            try { $el.selectpicker('destroy'); } catch (e) { /**/ }
            $el.selectpicker({
                liveSearch: true,
                liveSearchPlaceholder: "Search...",
                noneResultsText: "No match found",
                dropupAuto: false,
                container: 'body',
                size: 7
            });
            $el.selectpicker('refresh');
        }

        /* initialize static selects on page load */
        document.addEventListener('DOMContentLoaded', () => {
            initSelectpicker('.selectpicker');

            // Tab click behavior (keeps UI exactly same)
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.tab-btn').forEach(x => x.classList.remove('active'));
                    btn.classList.add('active');
                    const target = btn.dataset.tab;
                    document.querySelectorAll('#panel-reports, #panel-teams').forEach(p => {
                        p.style.display = (p.id === 'panel-' + target) ? '' : 'none';
                    });
                });
            });

            // remark modal via delegation
            document.body.addEventListener('click', function(e) {
                if (e.target.closest('.view-remark')) {
                    e.preventDefault();
                    const el = e.target.closest('.view-remark');
                    document.getElementById('remarkModalTitle').innerText = el.dataset.title || 'Remark';
                    document.getElementById('remarkModalBody').innerText = el.dataset.content || '';
                    const rm = new bootstrap.Modal(document.getElementById('remarkModal'));
                    rm.show();
                }
            });
        });

        /* -----------------------------------
           Helper: show modal by id (Bootstrap)
           ----------------------------------- */
        function showModal(id) {
            const el = document.getElementById(id);
            if (!el) return;
            const modal = new bootstrap.Modal(el);
            modal.show();
            // small visual cue
            el.classList.add('fade-in');
            setTimeout(() => el.classList.remove('fade-in'), 300);
        }

        /* -------------------------
           Open / fill Edit Team modal
           ------------------------- */
        window.openEditTeamModal = function(id, name, desc) {
            const form = document.getElementById('editTeamForm');
            form.action = '/team/' + id;
            form.dataset.tid = id;
            form.dataset.tname = name;
            document.getElementById('edit_team_name').value = name || '';
            document.getElementById('edit_team_description').value = desc || '';
            // wire delete -> open delete modal with values
            document.getElementById('editDeleteBtn').onclick = () => openDeleteTeamModal(id, name);
            showModal('editTeamModal');
        };

        /* -------------------------
           Delete modal helper
           ------------------------- */
        window.openDeleteTeamModal = function(id, name) {
            const form = document.getElementById('deleteTeamForm');
            form.action = '/team/' + id;
            document.getElementById('deleteTeamName').innerText = name || '';
            document.getElementById('deleteConfirmInput').value = '';
            document.getElementById('deleteTeamButton').disabled = true;
            showModal('deleteTeamModal');
        };

        // enable delete button only when typed name matches
        (function wireDeleteConfirm() {
            const input = document.getElementById('deleteConfirmInput');
            const btn = document.getElementById('deleteTeamButton');
            if (!input || !btn) return;
            input.addEventListener('input', () => {
                const expected = document.getElementById('deleteTeamName').innerText.trim();
                btn.disabled = (input.value.trim() !== expected);
            });
            // guard on submit
            document.getElementById('deleteTeamForm')?.addEventListener('submit', (ev) => {
                const expected = document.getElementById('deleteTeamName').innerText.trim();
                const typed = input.value.trim();
                if (typed !== expected) {
                    ev.preventDefault();
                    alert('Please type the exact team name to confirm deletion.');
                }
            });
        })();

        /* -------------------------
           Create Team modal
           ------------------------- */
        window.openCreateTeamModal = function() { showModal('createTeamModal'); };

        /* -------------------------
           Manage Members modal (Unified)
           - fetches /team/{id}/members JSON
           - populates members list and employee dropdown
           - initializes selectpicker for modal
           ------------------------- */
        window.openManageMembersModal = async function(teamId, focusMembersList = false) {
            if (!teamId) {
                alert('Team id missing');
                return;
            }

            // set hidden team id early (fallback)
            document.getElementById('addMemberTeamId').value = teamId;
            document.getElementById('addMemberForm').action = '/team/' + teamId + '/members';

            try {
                const res = await fetch('/team/' + teamId + '/members', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('Failed to fetch team data');
                const json = await res.json();

                // team name
                document.getElementById('manageModalTeamName').innerText = json.team?.team_name ?? '';

                // members list (clear + append)
                const membersArea = document.getElementById('membersListArea');
                membersArea.innerHTML = '';
                (json.members || []).forEach(m => {
                    const div = document.createElement('div');
                    div.className = 'd-flex align-items-center justify-content-between mb-2';
                    const left = document.createElement('div');
                    left.innerHTML = `<div style="font-weight:700;">${m.employee_name ?? m.emp_id} ${((!m.employee && ( !('{{$date}}') || new Date('${$date}').getTime() < Date.now() )) ? '<span class="badge bg-danger ms-1">Ex-Member</span>' : '')}</div>
                                      <div class="small text-muted">${m.is_leader ? 'Leader' : ''}</div>`;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.className = 'remove-member-form';
                    form.action = '/team/' + teamId + '/members/' + m.id;
                    form.innerHTML = `<input type="hidden" name="_token" value="${csrf}"><input type="hidden" name="_method" value="DELETE"><button class="btn btn-outline-danger btn-sm">Remove</button>`;
                    div.appendChild(left);
                    div.appendChild(form);
                    membersArea.appendChild(div);
                });

                // employees list -> dropdown
                refreshMemberDropdown(json.employees || []);

                // show modal
                showModal('manageMembersModal');

                // re-wire remove forms inside modal (delegated below also handles it)
            } catch (err) {
                // fallback: show modal with server-rendered content
                document.getElementById('addMemberTeamId').value = teamId;
                document.getElementById('addMemberForm').action = '/team/' + teamId + '/members';
                showModal('manageMembersModal');
                console.warn('openManageMembersModal error:', err);
            }
        };

        /* -------------------------
           Refresh employee dropdown (AJAX helper)
           ------------------------- */
        function refreshMemberDropdown(list) {
            const $select = $('#addMemberSelect');
            $select.empty();
            $select.append(`<option value="">-- choose employee --</option>`);
            list.forEach(e => {
                $select.append(`<option value="${e.emp_id}">${e.emp_name} (${e.emp_id})</option>`);
            });
            initSelectpicker('#addMemberSelect');
        }

        /* -------------------------
           Ensure selectpicker works inside modal when it opens
           ------------------------- */
        $('#manageMembersModal').on('shown.bs.modal', function () {
            initSelectpicker('#addMemberSelect');
        });

        /* -------------------------
           Add member form: set action based on hidden team id (guard)
           ------------------------- */
        document.getElementById('addMemberForm')?.addEventListener('submit', function(ev) {
            const teamId = document.getElementById('addMemberTeamId').value;
            if (!teamId) { ev.preventDefault(); alert('Team not selected'); return false; }
            this.action = '/team/' + teamId + '/members';
            // let standard form submit do the rest (server-side redirect will refresh view)
        });
            
        /* -------------------------
           Delegated handler: remove member forms inside modal
           - submits via normal POST/DELETE (fallback server)
           - optionally could be AJAX-enhanced later
           ------------------------- */
        document.getElementById('membersListArea')?.addEventListener('submit', function(ev) {
            if (ev.target && ev.target.classList.contains('remove-member-form')) {
                // allow default submit (server handles)
                // if you prefer AJAX removal, implement fetch here
            }
        });

        /* -------------------------
           Edit team form submit: set action already set by openEditTeamModal
           ------------------------- */
        document.getElementById('editTeamForm')?.addEventListener('submit', function() {
            // default POST/PUT to server route
        });

        /* -------------------------
           Delete confirmation guard already wired above
           ------------------------- */

        /* -------------------------
           Save team daily target - AJAX
           ------------------------- */
        window.saveAdminTeamTarget = async function(sheetId) {
            const val = document.getElementById('admin_team_target').value || '';
            try {
                const res = await fetch('/sheet/save_day/' + sheetId, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ today_target: val })
                });
                if (!res.ok) { alert('Failed to save daily target.'); return; }
                // small success animation + reload
                alert('Team daily target saved!');
                location.reload();
            } catch (e) {
                console.error(e);
                alert('Network error');
            }
        };

        /* -------------------------
           Tab switching already handled on DOMContentLoaded
           ------------------------- */

        /* -------------------------
           Utility: refresh members list server-rendered fallback
           ------------------------- */
        window.refreshMembersFallback = function(htmlFragment) {
            // placeholder if you want to inject server rendered HTML into #membersListArea
            document.getElementById('membersListArea').innerHTML = htmlFragment;
        };

        /* -------------------------
           Prevent double-inclusion guard (optional)
           ------------------------- */
        if (window._adminDashboardInitialized) {
            // already initialized (do nothing)
        } else {
            window._adminDashboardInitialized = true;
        }
    })();
    </script>

@endsection

