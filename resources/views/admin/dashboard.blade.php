{{-- resources/views/admin/dashboard.blade.php --}}
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        /* ===== micro animations (keep UI unchanged visually) ===== */
        .team-item,
        .task-card,
        .member-card,
        .card-surface {
            transition: all .22s ease;
        }

        .team-item:hover {
            transform: translateX(3px);
        }

        .task-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(16, 24, 40, 0.06);
        }

        .member-card:hover {
            transform: translateY(-2px);
        }

        /* soft fade */
        .fade-soft {
            animation: fadeSoft .28s ease;
        }

        @keyframes fadeSoft {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== keep your original look & layout as-is; only fixes for layout + responsive ===== */
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

        /* page shell + grid */
        .page-shell {
            border-radius: 14px;
            padding: 14px;
            background: linear-gradient(180deg, #ffffffcc, #fbfbffcc);
            box-shadow: 0 10px 30px rgba(16, 24, 40, 0.06);
        }

        .admin-grid {
            display: flex;
            gap: 18px;
            align-items: flex-start;
        }

        /* FIXED-SIDEBAR layout: occupy full height inside page-shell */
        .fixed-admin-layout {
            height: calc(100vh - 150px);
            /* approximate header + container */
            overflow: visible;
        }

        .admin-grid {
            height: 100%;
        }

        /* sidebar */
        .sidebar {
            width: 260px;
            position: sticky;
            top: 20px;
            height: calc(100vh - 150px);
            overflow-y: auto !important;
            overflow-y: auto;
            padding-right: 6px;
            background: transparent;
        }

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

        .team-item .icon-flat {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #EEF2FF, #F8FAFF);
            border: 1px solid #e6eefb;
            color: #0b5ed7;
            flex-shrink: 0;
        }

        .team-item.active {
            background: linear-gradient(90deg, #6366F1, #A78BFA);
            color: white;
            box-shadow: 0 6px 18px rgba(99, 102, 241, 0.12);
            border-color: rgba(255, 255, 255, 0.06);
        }

        /* pane */
        .pane {
            flex: 1;
            height: 100%;
            overflow-y: auto;
            padding-right: 8px;
        }

        /* top controls (date pills etc) - keep unchanged */
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

        /* cards / lists */
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

        /* status pills */
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

        /* remark modal body */
        .remark-full {
            white-space: pre-wrap;
            word-break: break-word;
            max-width: 78ch;
        }

        /* delete modal confirm input */
        #deleteConfirmInput {
            letter-spacing: 0.6px;
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
        /*  */
        /* -------------------------------------------------
   PREMIUM CARD + INTERACTION UPGRADE (no layout change)
--------------------------------------------------- */

/* Smooth hover glow for all cards */
.card-surface,
.member-card,
.task-card {
    transition: all .25s ease-in-out;
    border-radius: 14px !important;
    background: #ffffffee !important;
    backdrop-filter: blur(6px);
}

.card-surface:hover,
.member-card:hover,
.task-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 26px rgba(0,0,0,0.08);
}

/* Slight elevated shadow for main container */
.page-shell {
    border-radius: 20px !important;
    background: #ffffffcc;
    backdrop-filter: blur(8px);
    box-shadow: 0 14px 34px rgba(0,0,0,0.06) !important;
}


/* -------------------------------------------------
   ICON CARDS (Better interactive look)
--------------------------------------------------- */

.icon-flat {
    border-radius: 12px !important;
    transition: 0.2s ease;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
}

.icon-flat:hover {
    transform: scale(1.07);
    box-shadow: 0 6px 18px rgba(0,0,0,0.10);
}


/* -------------------------------------------------
   TEAM LIST ITEM UPGRADE
--------------------------------------------------- */

.team-item {
    transition: 0.25s ease;
    border-radius: 12px !important;
    background: #f8f8ff;
}

.team-item:hover {
    background: #eef0ff !important;
    transform: translateX(4px);
}

.team-item.active {
    background: linear-gradient(90deg, #6366F1, #8b5cf6) !important;
    box-shadow: 0 8px 20px rgba(99,102,241,0.35);
}


/* -------------------------------------------------
   SUMMARY CARDS — make uniform + premium
--------------------------------------------------- */

.summary-card {
    padding: 14px;
    border-radius: 12px;
    background: #ffffffdd;
    border: 1px solid #e7e7f9;
    text-align: center;
    transition: 0.25s ease;
}

.summary-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.07);
}


/* -------------------------------------------------
   Buttons — smooth, rounded, modern
--------------------------------------------------- */

.btn {
    transition: 0.25s ease;
    border-radius: 10px !important;
    font-weight: 600 !important;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0,0,0,0.15);
}

/* "Delete" button gets danger gradient */
.btn-danger {
    background: linear-gradient(90deg, #ef4444, #b91c1c) !important;
    border: none !important;
}


/* -------------------------------------------------
   Task Status – more punchy gradients
--------------------------------------------------- */

.task-status.completed {
    background: linear-gradient(135deg, #10B981, #06c16b) !important;
}

.task-status.in_progress {
    background: linear-gradient(135deg, #F59E0B, #e28a00) !important;
}

.task-status.not_completed {
    background: linear-gradient(135deg, #EF4444, #d42020) !important;
}


/* -------------------------------------------------
   Smooth fade for panels
--------------------------------------------------- */

.tab-panel {
    animation: fadeSlide 0.35s ease;
}

@keyframes fadeSlide {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

    </style>

    <div class="admin-wrap fade-soft">
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
                                <div style="font-weight:700; margin-bottom:6px;">Summary
                                    ({{ \Carbon\Carbon::parse($date)->format('d M, Y') }})</div>
                                <div class="d-flex gap-2 mb-2 fade-soft">
                                    <div class="p-2"
                                        style="border-radius:8px;background:#fff;border:1px solid #eef2ff;min-width:92px;text-align:center;">
                                        <div class="small">Tasks</div>
                                        <div style="font-weight:700">{{ $summary['total_tasks'] }}</div>
                                    </div>
                                    <div class="p-2"
                                        style="border-radius:8px;background:#fff;border:1px solid #eef2ff;min-width:92px;text-align:center;">
                                        <div class="small">Completed</div>
                                        <div style="font-weight:700;color:#065f46;">{{ $summary['completed'] }}</div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 fade-soft">
                                    <div class="p-2"
                                        style="border-radius:8px;background:#fff;border:1px solid #eef2ff;min-width:92px;text-align:center;">
                                        <div class="small">In Progress</div>
                                        <div style="font-weight:700;">{{ $summary['in_progress'] }}</div>
                                    </div>
                                    <div class="p-2"
                                        style="border-radius:8px;background:#fff;border:1px solid #eef2ff;min-width:92px;text-align:center;">
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

                                {{-- important: always include team id in route/url to avoid UrlGenerationException --}}
                                <a href="{{ route('admin.dashboard', ['team_id' => $tid, 'date' => $date]) }}"
                                    class="team-item {{ $active ? 'active' : '' }}">
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <div class="icon-flat" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z" />
                                                <path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                                            </svg>
                                        </div>
                                        <div style="font-weight:600;">{{ $t->team_name }}</div>
                                    </div>
                                    <div class="small text-muted">
                                        {{ \App\Models\TeamMember::where('team_id', $tid)->count() }}
                                        members</div>
                                </a>
                            @endforeach
                        </div>


                    </div>

                    {{-- MAIN PANE: Reports & Teams panels (show/hide via JS) --}}
                    <div class="pane fade-soft" id="panel-reports">
                        @if (!$selectedTeamId)
                            <div class="alert alert-secondary fade-soft">No team selected. Please choose a team from the
                                left.
                            </div>
                        @else
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 style="margin:0;">Team: {{ $sheet->team->team_name ?? 'Team ' . $selectedTeamId }}
                                    </h5>
                                    <div class="small">Date: {{ \Carbon\Carbon::parse($date)->format('d M, Y') }}</div>
                                </div>

                                <div class="d-flex gap-2">
                                    {{-- <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.dashboard') }}">All
                                        teams</a> --}}
                                    <a href="{{ route('admin.dashboard', ['team_id' => $selectedTeamId, 'date' => $date]) }}"
                                        class="btn btn-primary btn-sm">Refresh</a>
                                    {{-- Manage members inline: opens modal with selected team --}}
                                    <button class="btn btn-outline-primary btn-sm"
                                        onclick="openManageMembersModal({{ $selectedTeamId }})">Manage Members</button>
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
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                        fill="currentColor">
                                                        <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z" />
                                                        <path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                                                    </svg>
                                                </div>

                                                <div style="margin-left:6px;">
                                                    <div style="font-weight:700;">
                                                        {{ $m->employee->emp_name ?? $m->emp_id }}
                                                        @if ($m->is_leader)
                                                            <span class="small text-primary"> — Leader</span>
                                                        @endif
                                                    </div>
                                                    @if (!empty($m->employee->emp_designation ?? false))
                                                        <div class="small text-muted">{{ $m->employee->emp_designation }}
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="ms-auto small">
                                                    Assigned today:
                                                    <strong>{{ $assignments->where('member_emp_id', $m->emp_id)->count() }}</strong>
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

                                        <div class="mt-3"
                                            style="font-weight:800; display:flex; gap:8px; align-items:center;">
                                            <div class="icon-flat"
                                                style="width:40px;height:40px; display:flex; align-items:center; justify-content:center;">

                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                    fill="currentColor">
                                                    <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z" />
                                                    <path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                                                </svg>
                                            </div>
                                            <div>{{ $name }}
                                                <div class="small text-muted">{{ $rows->count() }} task(s)</div>
                                            </div>
                                        </div>

                                        @foreach ($rows as $a)
                                            <div class="task-card fade-soft">
                                                <div class="task-left">
                                                    <div class="task-title">
                                                        {{ optional($clients->firstWhere('client_id', $a->client_id))->client_company_name ?? '—' }}
                                                    </div>
                                                    <div class="task-desc"
                                                        title="{{ $a->leader_remark ?? ($a->task_description ?? '') }}">
                                                        {{ \Illuminate\Support\Str::limit($a->leader_remark ?? ($a->task_description ?? '-'), 240) }}
                                                    </div>

                                                    <div class="meta mt-2">
                                                        <strong>Member remark:</strong>
                                                        <span class="small">
                                                            {{ \Illuminate\Support\Str::limit($a->member_remark ?? '-', 100) }}
                                                        </span>
                                                        @if (strlen($a->member_remark ?? '') > 100)
                                                            &nbsp;<a href="#" class="ms-2 view-remark"
                                                                data-title="Member remark"
                                                                data-content="{{ e($a->member_remark) }}">View</a>
                                                        @endif
                                                        @if (strlen($a->leader_remark ?? '') > 240)
                                                            &nbsp;<a href="#" class="ms-2 view-remark"
                                                                data-title="Leader remark"
                                                                data-content="{{ e($a->leader_remark) }}">View</a>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div style="min-width:150px; text-align:right;">
                                                    <div><span
                                                            class="task-status {{ $a->status ?? 'not_completed' }}">{{ ucfirst($a->status ?? 'not_completed') }}</span>
                                                    </div>
                                                    <div class="small text-muted mt-2">Submitted:
                                                        {{ $a->is_submitted ? 'Yes' : 'No' }}
                                                    </div>
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
                                <button class="btn btn-primary btn-sm" onclick="openCreateTeamModal()">Create
                                    Team</button>
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
                                                    <div class="small text-muted mt-1">
                                                        {{ \Illuminate\Support\Str::limit($t->description, 160) }}</div>
                                                @endif
                                            </div>

                                            <div class="text-end">
                                                {{-- Members: open manage modal with team preselected --}}
                                                <button class="btn btn-outline-primary btn-sm mb-1"
                                                    onclick="openManageMembersModal({{ $t->id }}, true)">Members</button>

                                                {{-- Edit: opens modal (inline) --}}
                                                <button class="btn btn-outline-primary btn-sm mb-1"
                                                    onclick="openEditTeamModal({{ $t->id }}, '{{ e($t->team_name) }}', '{{ e($t->description ?? '') }}')">Edit</button>


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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
    {{-- 444444 --}}

    {{-- Edit Team Modal --}}
    <div class="modal fade" id="editTeamModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editTeamForm" method="POST" class="modal-content">
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
                    {{-- Delete: GitHub-style confirm modal --}}
                    <button class="btn btn-danger "
                        onclick="openDeleteTeamModal({{ $t->id }}, '{{ e($t->team_name) }}')">Delete</button>
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
                                {{-- dynamically filled via AJAX OR server-rendered html when page loads (fallback) --}}
                                @foreach ($members as $m)
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div>
                                            <div style="font-weight:700;">{{ $m->employee->emp_name ?? $m->emp_id }}</div>
                                            <div class="small text-muted">
                                                @if ($m->is_leader)
                                                    Leader
                                                @endif
                                            </div>
                                        </div>
                                        <form method="POST" class="remove-member-form"
                                            data-team-id="{{ $selectedTeamId }}" data-member-id="{{ $m->id }}"
                                            action="{{ route('team.members.remove', ['team' => $selectedTeamId, 'member' => $m->id]) }}">
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
                                    {{-- server side we may provide $employees if available; but we also fetch via AJAX if
                                    not --}}
                                    <select name="emp_id" id="addMemberSelect" class="form-select" required>
                                        <option value="">-- choose employee --</option>
                                        @php
                                            // If employees variable was provided by controller, show them (non-invasive)
                                            $employees = $employees ?? collect();
                                        @endphp
                                        @foreach ($employees as $e)
                                            <option value="{{ $e->emp_id }}">{{ $e->emp_name }}
                                                ({{ $e->emp_id }})</option>
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

    {{-- Delete Team Modal (GitHub-like confirm) --}}
    <div class="modal fade" id="deleteTeamModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="deleteTeamForm" class="modal-content">
                @csrf @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Delete Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p class="mb-2">This action <strong>cannot</strong> be undone. Deleting a team removes it and its
                        membership links. If tasks/sheets are linked to this team you will lose the group association.</p>
                    <p class="mb-2">To confirm deletion, type the team name exactly:</p>

                    <div class="mb-2">
                        <input type="text" id="deleteConfirmInput" class="form-control"
                            placeholder="Type team name to confirm">
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

    {{-- ----- JS: keep behavior inline; no external redirection for member actions ----- --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Tabs show/hide
            const tabs = document.querySelectorAll('.tab-btn');
            const panels = {
                'reports': document.getElementById('panel-reports'),
                'teams': document.getElementById('panel-teams')
            };
            tabs.forEach(t => t.addEventListener('click', function() {
                tabs.forEach(x => x.classList.remove('active'));
                t.classList.add('active');
                const target = t.dataset.tab;
                Object.keys(panels).forEach(k => panels[k].style.display = (k === target) ? '' :
                'none');
            }));

            // remark modal handler
            document.querySelectorAll('.view-remark').forEach(el => {
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('remarkModalTitle').innerText = this.dataset.title ||
                        'Remark';
                    document.getElementById('remarkModalBody').innerText = this.dataset.content ||
                        '';
                    new bootstrap.Modal(document.getElementById('remarkModal')).show();
                });
            });

            // Manage member form submission: route depends on selected team. We will handle add via normal POST to team.members.add (with team id)
            // To keep UX inline: after successful add/remove, controller redirects back to admin.dashboard with team_id/date so user stays on same page.
            // For forms in members modal that were rendered server-side, default behavior will work.
            // We'll intercept add member form to set correct action URL when modal is opened.

            // Remove member forms (server-rendered) - forms already have proper action attribute
            document.querySelectorAll('.remove-member-form').forEach(f => {
                f.addEventListener('submit', function(evt) {
                    // Allow default; server will redirect back to admin.dashboard -> stays on page
                });
            });

            // Add member form: action set dynamically when opening modal
            document.getElementById('addMemberForm').addEventListener('submit', function(evt) {
                // default submit will hit team.members.add route
            });

            // Edit team: form action set dynamically in function openEditTeamModal()
            document.getElementById('editTeamForm').addEventListener('submit', function() {
                /* default POST works */ });

            // Delete modal: enable delete button only when exact name typed
            const deleteConfirmInput = document.getElementById('deleteConfirmInput');
            const deleteTeamButton = document.getElementById('deleteTeamButton');
            deleteConfirmInput && deleteConfirmInput.addEventListener('input', function() {
                const expected = document.getElementById('deleteTeamName').innerText.trim();
                deleteTeamButton.disabled = (this.value.trim() !== expected);
            });

            // Intercept addMemberForm to set action based on team id in hidden input
            const addMemberForm = document.getElementById('addMemberForm');
            addMemberForm && addMemberForm.addEventListener('submit', function() {
                const teamId = document.getElementById('addMemberTeamId').value;
                if (!teamId) {
                    alert('Team not selected');
                    event.preventDefault();
                    return false;
                }
                // set action to route('team.members.add', teamId)
                this.action = '/team/' + teamId + '/members';
            });

            // Intercept delete form submit: ensure confirm match (additional safety)
            const deleteForm = document.getElementById('deleteTeamForm');
            deleteForm && deleteForm.addEventListener('submit', function(evt) {
                const typed = deleteConfirmInput.value.trim();
                const expected = document.getElementById('deleteTeamName').innerText.trim();
                if (typed !== expected) {
                    evt.preventDefault();
                    alert('Please type the exact team name to confirm deletion.');
                    return false;
                }
            });

        }); // DOMContentLoaded

        // Open create team modal
        function openCreateTeamModal() {
            new bootstrap.Modal(document.getElementById('createTeamModal')).show();
        }

        // Open edit team modal; sets form action to /team/{id}
        function openEditTeamModal(id, name, desc) {
            const form = document.getElementById('editTeamForm');
            form.action = '/team/' + id;
            document.getElementById('edit_team_name').value = name;
            document.getElementById('edit_team_description').value = desc;
            new bootstrap.Modal(document.getElementById('editTeamModal')).show();
        }

        // Open delete modal; set form action and display name
        function openDeleteTeamModal(id, name) {
            const form = document.getElementById('deleteTeamForm');
            form.action = '/team/' + id;
            document.getElementById('deleteTeamName').innerText = name;
            document.getElementById('deleteConfirmInput').value = '';
            document.getElementById('deleteTeamButton').disabled = true;
            new bootstrap.Modal(document.getElementById('deleteTeamModal')).show();
        }

        // Open manage members modal. If `focusMembersTab` true, switch to teams tab first (optional)
        function openManageMembersModal(teamId, focusMembersList = false) {
            // set modal team name via AJAX if possible, else use text from DOM
            fetch('/team/' + teamId + '/members', {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(res => {
                    if (res.ok) return res.json();
                    return res.text().then(txt => {
                        throw txt;
                    });
                })
                .then(json => {
                    // json: { team: {...}, employees: [...], members: [...] }
                    document.getElementById('manageModalTeamName').innerText = json.team.team_name;
                    // fill members list
                    const membersArea = document.getElementById('membersListArea');
                    membersArea.innerHTML = '';
                    json.members.forEach(m => {
                        const memberNode = document.createElement('div');
                        memberNode.className = 'd-flex align-items-center justify-content-between mb-2';
                        memberNode.innerHTML = `<div><div style="font-weight:700;">${m.employee_name ?? m.emp_id}</div><div class="small text-muted">${m.is_leader ? 'Leader' : ''}</div></div>
                <form method="POST" class="remove-member-form" data-team-id="${teamId}" data-member-id="${m.id}" action="/team/${teamId}/members/${m.id}">
                    <input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]').content}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button class="btn btn-outline-danger btn-sm">Remove</button>
                </form>`;
                        membersArea.appendChild(memberNode);
                    });

                    // fill employee select (if employees present)
                    const select = document.getElementById('addMemberSelect');
                    if (select) {
                        select.innerHTML = '<option value="">-- choose employee --</option>';
                        json.employees.forEach(e => {
                            const opt = document.createElement('option');
                            opt.value = e.emp_id;
                            opt.text = `${e.emp_name} (${e.emp_id})`;
                            select.appendChild(opt);
                        });
                    }

                    // set hidden team id
                    document.getElementById('addMemberTeamId').value = teamId;
                    // update add member form action (will be finalized on submit)
                    document.getElementById('addMemberForm').action = '/team/' + teamId + '/members';

                    new bootstrap.Modal(document.getElementById('manageMembersModal')).show();
                })
                .catch(err => {
                    // fallback: show modal with existing server-rendered content, set team id hidden
                    document.getElementById('manageModalTeamName').innerText = '';
                    document.getElementById('addMemberTeamId').value = teamId;
                    document.getElementById('addMemberForm').action = '/team/' + teamId + '/members';
                    new bootstrap.Modal(document.getElementById('manageMembersModal')).show();
                });
        }
    </script>

@endsection
