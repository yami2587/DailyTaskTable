{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')
    @php
        // Provided by controller
        $teams = $teams ?? collect();
        $selectedTeamId = $selectedTeamId ?? ($teams->first()->team_id ?? null);
        $date = $date ?? date('Y-m-d');
        $sheet = $sheet ?? null;
        $members = $members ?? collect();
        $assignments = $assignments ?? collect();
        $clients = $clients ?? collect();
        $summary = $summary ?? ['total_tasks' => 0, 'completed' => 0, 'in_progress' => 0, 'not_completed' => 0];
    @endphp

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Bootstrap CDN (remove if your layout already includes it) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* ===== Admin integrated dashboard (single-file) ===== */
        .admin-wrap {
            padding: 18px;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .title {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }

        .sub {
            color: #6b7280;
            font-size: 13px;
        }

        .page-shell {
            border-radius: 14px;
            padding: 14px;
            background: linear-gradient(180deg, #ffffffcc, #fbfbffcc);
            box-shadow: 0 10px 30px rgba(16, 24, 40, 0.06);
        }

        /* GRID */
        .admin-grid {
            display: flex;
            gap: 18px;
            align-items: flex-start;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            position: sticky;
            top: 20px;
            height: calc(100vh - 40px);
            overflow-y: auto;
            padding-right: 6px;
            background: transparent;
        }

        /* TEAM ITEM */
        .team-item {
            padding: 10px 12px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: space-between;
            color: #374151;
            text-decoration: none;
            border: 1px solid transparent;
            margin-bottom: 8px;
        }

        .team-item:hover {
            background: #f7f9ff;
            text-decoration: none;
        }

        .team-item.active {
            background: linear-gradient(90deg, #6366F1, #A78BFA);
            color: white;
            box-shadow: 0 6px 18px rgba(99, 102, 241, 0.12);
            border-color: rgba(255, 255, 255, 0.06);
        }

        /* ICON FLAT */
        .icon-flat {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #EEF2FF, #F8FAFF);
            border: 1px solid #e6eefb;
            color: #0b5ed7;
        }

        /* PANE */
        .pane {
            flex: 1;
        }

        /* Top tabs */
        .top-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 8px 12px;
            border-radius: 999px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            background: #f3f4f6;
        }

        .tab-btn.active {
            background: linear-gradient(90deg, #6366F1, #A855F7);
            color: white;
        }

        /* Cards & task */
        .card-surface {
            border-radius: 10px;
            padding: 12px;
            background: #fff;
            box-shadow: 0 6px 18px rgba(16, 24, 40, 0.04);
            margin-bottom: 12px;
        }

        .member-card {
            padding: 10px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #eef2ff;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .task-card {
            padding: 12px;
            border-radius: 12px;
            background: #ffffffdd;
            border: 1px solid #e5e7eb;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-start;
        }

        .task-left {
            flex: 1;
            max-width: 72ch;
            word-break: break-word;
        }

        .task-title {
            font-weight: 700;
            color: #0b5ed7;
        }

        .task-desc {
            color: #374151;
            margin-top: 6px;
            max-height: 3.6em;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* status */
        .task-status {
            padding: 6px 12px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 12px;
            text-transform: capitalize;
        }

        .task-status.completed {
            background: linear-gradient(135deg, #10B981, #059669);
            color: #fff;
        }

        .task-status.in_progress {
            background: linear-gradient(135deg, #F59E0B, #D97706);
            color: #fff;
        }

        .task-status.not_completed {
            background: linear-gradient(135deg, #EF4444, #B91C1C);
            color: #fff;
        }

        /* small meta */
        .small {
            font-size: 13px;
            color: #6b7280;
        }

        .meta {
            font-size: 13px;
            color: #6b7280;
        }

        /* pills */
        .day-pill {
            border-radius: 999px;
            padding: 6px 10px;
            font-weight: 700;
        }

        /* remark expand */
        .remark-full {
            white-space: pre-wrap;
            word-break: break-word;
            max-width: 78ch;
        }

        /* responsive */
        @media(max-width:980px) {
            .admin-grid {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                order: 2;
                position: relative;
                height: auto;
            }

            .pane {
                order: 1;
            }
        }
    </style>

    <div class="admin-wrap">
        <div class="header-row">
            <div>
                <div class="title">Admin: Team Daily Reports</div>
                <div class="sub">Super-admin — browse teams, sheets & manage teams</div>
            </div>

            <div class="d-flex gap-2 align-items-center">
                {{-- date select + last 6 days pills --}}
                <form id="adminDateForm" method="GET" action="{{ route('admin.dashboard') }}"
                    class="d-flex align-items-center gap-2">
                    <input type="hidden" name="team_id" value="{{ $selectedTeamId }}" id="selectedTeamInput">
                    <input type="date" name="date" value="{{ $date }}"
                        onchange="document.getElementById('adminDateForm').submit()" class="form-control form-control-sm" />
                </form>

                @php
                    $days = collect();
                    for ($i = 0; $i < 6; $i++) {
                        $days->push(now()->subDays($i)->format('Y-m-d'));
                    }
                @endphp

                <div class="d-flex gap-2 ms-2">
                    @foreach($days as $d)
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

        <div class="page-shell">
            {{-- Top Tabs (integrated: Reports | Teams | Create Team | Manage Members) --}}
            <div class="top-tabs">
                <button class="tab-btn active" data-tab="reports" id="tab-reports">Daily Reports</button>
                <button class="tab-btn" data-tab="teams" id="tab-teams">Teams</button>
                <button class="tab-btn" data-tab="create-team" id="tab-create-team">Create Team</button>
                <button class="tab-btn" data-tab="manage-members" id="tab-manage-members">Team Members</button>
            </div>

            <div class="admin-grid">
                {{-- Sidebar: team list + summary --}}
                <div class="sidebar">
                    <div style="margin-bottom:8px; font-weight:700;">Teams</div>
                    <div class="list-group mb-3">
                        @foreach($teams as $t)
                            @php
                                $tid = $t->team_id ?? $t->id ?? null;
                                $tname = $t->team_name ?? ('Team ' . $tid);
                                $active = ($tid == $selectedTeamId);
                            @endphp
                            <a href="{{ route('admin.dashboard', ['team_id' => $tid, 'date' => $date]) }}"
                                class="team-item list-group-item list-group-item-action {{ $active ? 'active' : '' }}">
                                <div style="display:flex; gap:10px; align-items:center;">
                                    <div class="icon-flat" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                            viewBox="0 0 16 16" style="color:#0b5ed7">
                                            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z" />
                                            <path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                                        </svg>
                                    </div>
                                    <div style="font-weight:600;">{{ $tname }}</div>
                                </div>
                                <div class="small text-muted">{{ \App\Models\TeamMember::where('team_id', $tid)->count() }}
                                    members</div>
                            </a>
                        @endforeach
                    </div>

                    <div style="margin-top:6px;">
                        <div style="font-weight:700; margin-bottom:6px;">Summary
                            ({{ \Carbon\Carbon::parse($date)->format('d M, Y') }})</div>
                        <div class="d-flex gap-2 mb-2">
                            <div class="p-2"
                                style="border-radius:8px; background:#fff; border:1px solid #eef2ff; min-width:92px; text-align:center;">
                                <div class="small">Tasks</div>
                                <div style="font-weight:700">{{ $summary['total_tasks'] }}</div>
                            </div>
                            <div class="p-2"
                                style="border-radius:8px; background:#fff; border:1px solid #eef2ff; min-width:92px; text-align:center;">
                                <div class="small">Completed</div>
                                <div style="font-weight:700; color:#065f46;">{{ $summary['completed'] }}</div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <div class="p-2"
                                style="border-radius:8px; background:#fff; border:1px solid #eef2ff; min-width:92px; text-align:center;">
                                <div class="small">In Progress</div>
                                <div style="font-weight:700;">{{ $summary['in_progress'] }}</div>
                            </div>
                            <div class="p-2"
                                style="border-radius:8px; background:#fff; border:1px solid #eef2ff; min-width:92px; text-align:center;">
                                <div class="small">Not Done</div>
                                <div style="font-weight:700; color:#7a0f0f;">{{ $summary['not_completed'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Main pane (all tabs live here; we show/hide client-side) --}}
                <div class="pane">

                    {{-- === TAB: Daily Reports === --}}
                    <div id="panel-reports" class="tab-panel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 style="margin:0;">@if($selectedTeamId) Team:
                                {{ $sheet->team->team_name ?? 'Team ' . $selectedTeamId }} @else No team selected @endif
                                </h5>
                                <div class="small">Date: {{ \Carbon\Carbon::parse($date)->format('d M, Y') }}</div>
                            </div>

                            <div class="d-flex gap-2">
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.dashboard') }}">All
                                    teams</a>
                                <a href="{{ route('admin.dashboard', ['team_id' => $selectedTeamId, 'date' => $date]) }}"
                                    class="btn btn-primary btn-sm">Refresh</a>
                            </div>
                        </div>

                        {{-- Members card --}}
                        <div class="card-surface mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="font-weight:700;">Team Members</div>
                                <div class="small text-muted">{{ $members->count() }} members</div>
                            </div>

                            <div class="mt-3">
                                <div class="row g-2">
                                    @foreach($members as $m)
                                        @php $e = $m->employee ?? null;
                                        $name = $e->emp_name ?? $m->emp_id; @endphp
                                        <div class="col-12 col-md-6">
                                            <div class="member-card">
                                                <div class="icon-flat" aria-hidden="true">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                        fill="currentColor" viewBox="0 0 16 16" style="color:#0b5ed7">
                                                        <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z" />
                                                        <path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                                                    </svg>
                                                </div>

                                                <div style="margin-left:6px;">
                                                    <div style="font-weight:700;">{{ $name }} @if($m->is_leader) <span
                                                    class="small text-primary"> — Leader</span> @endif</div>
                                                    <div class="small text-muted">@if($m->designation ?? false)
                                                    {{ $m->designation }} @endif</div>
                                                </div>

                                                <div class="ms-auto text-end">
                                                    <div class="small">Assigned today:
                                                        <strong>{{ $assignments->where('member_emp_id', $m->emp_id)->count() }}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    @if($members->isEmpty())
                                        <div class="col-12">
                                            <div class="small text-muted">No members found for this team.</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Tasks grouped by member --}}
                        <div>
                            @if(!$sheet)
                                <div class="alert alert-warning">No sheet exists for this date for the selected team.</div>
                            @endif

                            @if($sheet && $assignments->isEmpty())
                                <div class="alert alert-info">Sheet exists but no assignments were added for this date.</div>
                            @endif

                            @if($sheet && $assignments->isNotEmpty())
                                @php $grouped = $assignments->groupBy('member_emp_id'); @endphp

                                @foreach($grouped as $memberId => $memberAssignments)
                                    @php
                                        $member = $members->firstWhere('emp_id', $memberId);
                                        $memberName = $member ? ($member->employee->emp_name ?? $member->emp_id) : ($memberId ?? 'Unknown');
                                    @endphp

                                    <div class="mb-2"
                                        style="font-weight:800; margin-top:18px; display:flex; align-items:center; gap:12px;">
                                        <div class="icon-flat" style="width:44px;height:44px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                                viewBox="0 0 16 16" style="color:#0b5ed7">
                                                <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z" />
                                                <path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                                            </svg>
                                        </div>

                                        <div>
                                            <div style="font-weight:800;">{{ $memberName }}</div>
                                            <div class="small text-muted">{{ $memberAssignments->count() }} task(s)</div>
                                        </div>
                                    </div>

                                    @foreach($memberAssignments as $a)
                                        <div class="task-card">
                                            <div class="task-left">
                                                <div class="task-title">
                                                    {{ optional($clients->firstWhere('client_id', $a->client_id))->client_company_name ?? '—' }}
                                                </div>
                                                <div class="task-desc" title="{{ $a->leader_remark ?? $a->task_description ?? '' }}">
                                                    {{-- trimmed view; click "View" to read full --}}
                                                    {{ \Illuminate\Support\Str::limit($a->leader_remark ?? $a->task_description ?? '-', 240) }}
                                                </div>

                                                <div class="meta mt-2">
                                                    <strong>Member remark:</strong>
                                                    <span class="small">
                                                        {{ \Illuminate\Support\Str::limit($a->member_remark ?? '-', 100) }}
                                                    </span>
                                                    @if(strlen($a->member_remark ?? '') > 100)
                                                        &nbsp; <a href="#" class="ms-2 view-remark" data-title="Member remark"
                                                            data-content="{{ e($a->member_remark) }}">View</a>
                                                    @endif
                                                    @if(strlen($a->leader_remark ?? '') > 240)
                                                        &nbsp; <a href="#" class="ms-2 view-remark" data-title="Leader remark"
                                                            data-content="{{ e($a->leader_remark) }}">View</a>
                                                    @endif
                                                </div>
                                            </div>

                                            <div style="min-width:150px; text-align:right;">
                                                <div>
                                                    <span
                                                        class="task-status {{ $a->status ?? 'not_completed' }}">{{ ucfirst($a->status ?? 'not_completed') }}</span>
                                                </div>
                                                <div class="small text-muted mt-2">Submitted: {{ $a->is_submitted ? 'Yes' : 'No' }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- === TAB: Teams (list + actions) === --}}
                    <div id="panel-teams" class="tab-panel" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div style="font-weight:700;">All Teams</div>
                            <div>
                                <a href="#" class="btn btn-primary btn-sm" onclick="showCreateTeam()">Create team</a>
                            </div>
                        </div>

                        <div class="row g-2">
                            @foreach($teams as $t)
                                <div class="col-12 col-md-6">
                                    <div class="card-surface">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div style="font-weight:800;">{{ $t->team_name ?? 'Team ' . $t->team_id }}</div>
                                                @if(!empty($t->description))
                                                    <div class="small text-muted mt-1">
                                                        {{ \Illuminate\Support\Str::limit($t->description, 160) }}</div>
                                                @endif
                                            </div>

                                            <div class="text-end">
                                                <a class="btn btn-outline-primary btn-sm mb-1"
                                                    href="{{ route('team.members', $t->id ?? $t->team_id) }}">Members</a>
                                                <button class="btn btn-ghost btn-sm mb-1"
                                                    onclick="editTeam({{ $t->id ?? $t->team_id }}, '{{ e($t->team_name) }}', '{{ e($t->description ?? '') }}')">Edit</button>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            @if($teams->isEmpty())
                                <div class="col-12">
                                    <div class="small text-muted">No teams available.</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- === TAB: Create Team (inline form) === --}}
                    <div id="panel-create-team" class="tab-panel" style="display:none;">
                        <div class="card-surface">
                            <div style="font-weight:700;">Create Team</div>
                            <form method="POST" action="{{ route('team.store') }}" class="mt-3" id="form-create-team">
                                @csrf
                                <div class="mb-2">
                                    <label class="small">Team Name</label>
                                    <input name="team_name" class="form-control" required>
                                </div>
                                <div class="mb-2">
                                    <label class="small">Description</label>
                                    <textarea name="description" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary" type="submit">Save</button>
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="clearCreateForm()">Reset</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- === TAB: Manage Members (select team then add/remove members) === --}}
                    <div id="panel-manage-members" class="tab-panel" style="display:none;">
                        <div class="card-surface mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="font-weight:700;">Manage Members</div>

                                <div>
                                    <form id="manageDateForm" method="GET" action="{{ route('admin.dashboard') }}"
                                        class="d-flex gap-2 align-items-center">
                                        <select id="manageTeamSelect" class="form-select form-select-sm"
                                            style="min-width:220px;">
                                            @foreach($teams as $t)
                                                <option value="{{ $t->id ?? $t->team_id }}" {{ ($t->id ?? $t->team_id) == $selectedTeamId ? 'selected' : '' }}>
                                                    {{ $t->team_name ?? 'Team ' . $t->team_id }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-primary btn-sm" type="button"
                                            onclick="loadMembersForTeam()">Load</button>
                                    </form>
                                </div>
                            </div>

                            <div class="mt-3" id="members-manage-area">
                                {{-- loaded content via server / was provided: show members and form to add --}}
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div style="font-weight:700;">Members</div>
                                        <div class="mt-2">
                                            @foreach($members as $m)
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <div>
                                                        <div style="font-weight:700;">{{ $m->employee->emp_name ?? $m->emp_id }}
                                                        </div>
                                                        <div class="small text-muted">@if($m->is_leader) Leader @endif</div>
                                                    </div>
                                                    <form method="POST"
                                                        action="{{ route('team.members.remove', [$selectedTeamId, $m->id ?? $m->emp_id]) }}"
                                                        onsubmit="return confirm('Remove member?');">
                                                        @csrf @method('DELETE')
                                                        <button class="btn btn-outline-danger btn-sm">Remove</button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div style="font-weight:700;">Add Member</div>
                                        <form method="POST" action="{{ route('team.members.add', $selectedTeamId) }}"
                                            class="mt-2">
                                            @csrf
                                            <div class="mb-2">
                                                <label class="small">Employee ID</label>
                                                <input name="emp_id" class="form-control" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="small">Is Leader?</label>
                                                <select name="is_leader" class="form-select">
                                                    <option value="0">No</option>
                                                    <option value="1">Yes</option>
                                                </select>
                                            </div>
                                            <button class="btn btn-primary btn-sm">Add</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> {{-- end pane --}}
            </div> {{-- end admin-grid --}}
        </div> {{-- end page shell --}}
    </div> {{-- end admin-wrap --}}

    {{-- Remark modal (view full text) --}}
    <div class="modal fade" id="remarkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="remarkModalTitle">Remark</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="remarkModalBody" style="white-space:pre-wrap;"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Simple edit team modal --}}
    <div class="modal fade" id="editTeamModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editTeamForm" method="POST" class="modal-content">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="small">Team Name</label>
                        <input name="team_name" id="edit_team_name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="small">Description</label>
                        <textarea name="description" id="edit_team_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>

                    <form method="POST" action="{{ route('team.destroy', $t->id ?? $t->team_id) }}"
                        style="display:inline-block;" onsubmit="return confirm('Delete team?');" >
                        @csrf @method('DELETE')
                        <button class="btn btn-danger ">Delete</button>
                    </form>
                </div>
            </form>
        </div>
    </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Tabs
            const tabs = document.querySelectorAll('.tab-btn');
            const panels = {
                'reports': document.getElementById('panel-reports'),
                'teams': document.getElementById('panel-teams'),
                'create-team': document.getElementById('panel-create-team'),
                'manage-members': document.getElementById('panel-manage-members'),
            };
            tabs.forEach(t => t.addEventListener('click', function () {
                tabs.forEach(x => x.classList.remove('active'));
                t.classList.add('active');
                const target = t.dataset.tab;
                Object.keys(panels).forEach(k => panels[k].style.display = (k === target) ? '' : 'none');
            }));

            // Remark view (modal)
            document.querySelectorAll('.view-remark').forEach(el => {
                el.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    const title = this.dataset.title || 'Remark';
                    const content = this.dataset.content || '';
                    document.getElementById('remarkModalTitle').innerText = title;
                    document.getElementById('remarkModalBody').innerText = content;
                    var remModal = new bootstrap.Modal(document.getElementById('remarkModal'));
                    remModal.show();
                });
            });

            // Edit team: fill modal and set action dynamically
            window.editTeam = function (id, name, description) {
                const form = document.getElementById('editTeamForm');
                form.action = '/team/' + id; // matches resource route team.update
                document.getElementById('edit_team_name').value = name;
                document.getElementById('edit_team_description').value = description;
                var editModal = new bootstrap.Modal(document.getElementById('editTeamModal'));
                editModal.show();
            };

            // Show create team tab quickly
            window.showCreateTeam = function () {
                document.getElementById('tab-create-team').click();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            // Clear create form
            window.clearCreateForm = function () {
                document.getElementById('form-create-team').reset();
            };

            // Load members for team (simple: navigate to admin dashboard with team_id)
            window.loadMembersForTeam = function () {
                const sel = document.getElementById('manageTeamSelect');
                const teamId = sel.value;
                const date = "{{ $date }}";
                // navigate to admin.dashboard with team_id + date
                const url = new URL(window.location.origin + "{{ route('admin.dashboard') }}");
                url.searchParams.set('team_id', teamId);
                url.searchParams.set('date', date);
                window.location.href = url.toString();
            };

            // keep selectedTeamInput updated when sidebar links clicked
            document.querySelectorAll('.team-item').forEach(function (el) {
                el.addEventListener('click', function () {
                    // anchor navigation will happen; keep hidden input sync if used
                    const href = el.getAttribute('href');
                    try {
                        const url = new URL(href, window.location.origin);
                        const id = url.searchParams.get('team_id') || url.searchParams.get('team') || null;
                        if (id) document.getElementById('selectedTeamInput').value = id;
                    } catch (e) { }
                });
            });

        }); // DOMContentLoaded
    </script>

@endsection