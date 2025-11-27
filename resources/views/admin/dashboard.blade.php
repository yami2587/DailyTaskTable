@extends('layouts.app')

@section('content')
@php
    $teams = $teams ?? collect();
    $selectedTeamId = $selectedTeamId ?? ($teams->first()->team_id ?? null);
    $date = $date ?? date('Y-m-d');
    $sheet = $sheet ?? null;
    $members = $members ?? collect();
    $assignments = $assignments ?? collect();
    $clients = $clients ?? collect();
    $summary = $summary ?? ['total_tasks'=>0,'completed'=>0,'in_progress'=>0,'not_completed'=>0];
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* ========= Premium Admin UI ========== */
.admin-wrap { padding:18px; }
.title { font-size:20px; font-weight:700; color:#111827; }

/* Top nav menu */
.top-menu {
    display:flex;
    gap:14px;
    background:#fff;
    padding:10px 14px;
    border-radius:12px;
    box-shadow:0 4px 10px rgba(0,0,0,0.06);
    margin-bottom:18px;
}
.top-menu a {
    font-weight:600;
    text-decoration:none;
    padding:6px 12px;
    border-radius:8px;
    color:#374151;
}
.top-menu a:hover {
    background:#eef2ff;
}

/* Sidebar */
.admin-grid { display:flex; gap:18px; align-items:flex-start; }
.sidebar {
    width: 260px;
    position: sticky;
    top: 90px;
    height: calc(100vh - 120px);
    overflow-y: auto;
    padding-right: 6px;
}

/* Team list */
.team-item {
    padding:10px 12px;
    border-radius:8px;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:space-between;
    color:#374151;
    border:1px solid transparent;
}
.team-item:hover { background:#f4f6ff; text-decoration:none; }
.team-item.active {
    background: linear-gradient(90deg,#6366F1,#A78BFA);
    color:white;
}

/* Member list */
.member-card {
    padding:10px;
    border-radius:10px;
    background:#fff;
    border:1px solid #eef2ff;
    display:flex;
    gap:10px;
    align-items:center;
}
.icon-circle {
    width:36px;
    height:36px;
    border-radius:50%;
    background:#eef2ff;
    display:flex;
    align-items:center;
    justify-content:center;
}

/* Task Cards */
.task-card {
    padding:14px;
    border-radius:12px;
    background:#ffffffee;
    border:1px solid #e5e7eb;
    box-shadow:0 4px 14px rgba(99,102,241,0.12);
    margin-bottom:12px;
    transition:.15s ease;
}
.task-card:hover { transform:translateY(-2px); }

/* Status Pill */
.task-status {
    padding:5px 12px;
    border-radius:999px;
    color:white;
    font-weight:700;
    font-size:12px;
}
.task-status.completed { background:#10b981; }
.task-status.in_progress { background:#f59e0b; }
.task-status.not_completed { background:#ef4444; }

/* Text preview */
.preview-text {
    max-width: 65ch;
    white-space: normal;
    overflow-wrap: break-word;
    color:#374151;
}

/* Tooltip */
.tooltip-inner {
    max-width: 300px;
    white-space: pre-wrap;
}

@media(max-width:980px){
    .admin-grid { flex-direction:column; }
    .sidebar { width:100%; height:auto; position:relative; }
}
</style>

<div class="admin-wrap">

    <!-- TOP NAV MENU -->
    <div class="top-menu">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a href="{{ route('team.index') }}">Team Management</a>
        <a href="{{ route('team.members', $selectedTeamId ?? 1) }}">Manage Members</a>
    </div>
    <div class="header-row d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="title">
                @if($selectedTeamId)
                    Team: {{ $sheet->team->team_name ?? 'Team '.$selectedTeamId }}
                @else
                    No team selected
                @endif
            </div>
            <div class="small text-muted">Date: {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</div>
        </div>

        <!-- Date + Last 6 days -->
        <div class="d-flex align-items-center gap-2">

            <form id="adminDateForm" method="GET" action="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2">
                <input type="hidden" name="team_id" value="{{ $selectedTeamId }}" id="selectedTeamInput">
                <input type="date" name="date" value="{{ $date }}" onchange="document.getElementById('adminDateForm').submit()" class="form-control form-control-sm" />
            </form>

            @php
                $days = collect();
                for($i=0;$i<6;$i++){
                    $days->push(now()->subDays($i)->format('Y-m-d'));
                }
            @endphp

            <div class="d-flex gap-2 ms-2">
                @foreach($days as $d)
                    <a href="{{ route('admin.dashboard',['team_id'=>$selectedTeamId,'date'=>$d]) }}"
                       class="btn btn-sm {{ $d == $date ? 'btn-primary' : 'btn-outline-primary' }}"
                       style="border-radius:50px; font-weight:600;">
                       {{ \Carbon\Carbon::parse($d)->format('d M') }}
                    </a>
                @endforeach
            </div>

        </div>
    </div>


<div class="admin-grid">

    <!-- ========================= SIDEBAR (already in part-1) ========================= -->
    <div class="sidebar">
        <div style="margin-bottom:8px; font-weight:700;">Teams</div>

        @foreach($teams as $t)
            @php
                $tid = $t->team_id ?? $t->id;
                $tname = $t->team_name ?? ('Team '.$tid);
                $active = ($tid == $selectedTeamId);
            @endphp

            <a href="{{ route('admin.dashboard',['team_id'=>$tid,'date'=>$date]) }}"
               class="team-item {{ $active ? 'active' : '' }}">

                <div class="d-flex align-items-center gap-2">
                    <div class="icon-circle">
                        <!-- Clean SVG icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                             fill="#6366F1" viewBox="0 0 16 16">
                            <path d="M3 14s-1 0-1-1 1-4 6-4 
                                     6 3 6 4-1 1-1 1H3z"/>
                            <path fill-rule="evenodd"
                                  d="M8 8a3 3 0 1 0 0-6 
                                     3 3 0 0 0 0 6z"/>
                        </svg>
                    </div>
                    <div style="font-weight:600;">{{ $tname }}</div>
                </div>

                <div class="small text-muted">
                    {{ \App\Models\TeamMember::where('team_id', $tid)->count() }} members
                </div>
            </a>
        @endforeach

        <!-- SUMMARY BOX -->
        <div style="margin-top:18px;">
            <div style="font-weight:700; margin-bottom:6px;">Summary</div>

            <div class="d-flex gap-2 mb-2">
                <div class="p-2 text-center" style="min-width:90px; border-radius:10px; background:#fff; border:1px solid #eef2ff;">
                    <div class="small">Tasks</div>
                    <div style="font-weight:700;">{{ $summary['total_tasks'] }}</div>
                </div>

                <div class="p-2 text-center" style="min-width:90px; border-radius:10px; background:#fff; border:1px solid #eef2ff;">
                    <div class="small">Completed</div>
                    <div style="font-weight:700; color:#059669;">{{ $summary['completed'] }}</div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <div class="p-2 text-center" style="min-width:90px; border-radius:10px; background:#fff; border:1px solid #eef2ff;">
                    <div class="small">In Progress</div>
                    <div style="font-weight:700;">{{ $summary['in_progress'] }}</div>
                </div>

                <div class="p-2 text-center" style="min-width:90px; border-radius:10px; background:#fff; border:1px solid #eef2ff;">
                    <div class="small">Not Done</div>
                    <div style="font-weight:700; color:#b91c1c;">{{ $summary['not_completed'] }}</div>
                </div>
            </div>
        </div>
    </div>


    <!-- ========================= MAIN CONTENT ========================= -->
    <div class="pane">

        <!-- TEAM MEMBERS LIST -->
        <div class="card mb-3">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">
                    <div style="font-weight:700;">Team Members</div>
                    <div class="small text-muted">{{ $members->count() }} members</div>
                </div>

                <div class="member-list mt-3">

                    @foreach($members as $m)
                        @php
                            $empName = $m->employee->emp_name ?? 'Unknown';
                        @endphp

                        <div class="member-card">

                            <div class="icon-circle">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                     fill="#6366F1" viewBox="0 0 16 16">
                                    <path d="M3 14s-1 0-1-1 1-4 6-4 
                                             6 3 6 4-1 1-1 1H3z"/>
                                    <path fill-rule="evenodd"
                                          d="M8 8a3 3 0 1 0 0-6 
                                             3 3 0 0 0 0 6z"/>
                                </svg>
                            </div>

                            <div class="d-flex flex-column">
                                <span class="member-name">{{ $empName }}</span>

                                @if($m->is_leader)
                                    <span class="small text-primary">Leader</span>
                                @endif
                            </div>

                            <div class="ms-auto text-end small text-muted">
                                Tasks today:
                                <strong>{{ $assignments->where('member_emp_id', $m->emp_id)->count() }}</strong>
                            </div>

                        </div>
                    @endforeach

                    @if($members->isEmpty())
                        <div class="small text-muted mt-2">No members in this team.</div>
                    @endif

                </div>
            </div>
        </div>


        <!-- TASKS GROUPED BY MEMBER -->
        <div>
            @if(!$sheet)
                <div class="alert alert-warning">No sheet exists for this date.</div>
            @endif

            @if($sheet && $assignments->isNotEmpty())
                @php
                    $grouped = $assignments->groupBy('member_emp_id');
                @endphp

                @foreach($grouped as $memberId => $tasks)
                    @php
                        $member = $members->firstWhere('emp_id', $memberId);
                        $name = $member->employee->emp_name ?? 'Unknown';
                    @endphp

                    <div class="d-flex align-items-center gap-2 mb-2" style="margin-top:16px;">
                        <div class="icon-circle" style="width:40px; height:40px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                 fill="#6366F1" viewBox="0 0 16 16">
                                <path d="M3 14s-1 0-1-1 1-4 6-4 
                                         6 3 6 4-1 1-1 1H3z"/>
                                <path fill-rule="evenodd"
                                      d="M8 8a3 3 0 1 0 0-6 
                                         3 3 0 0 0 0 6z"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-weight:800;">{{ $name }}</div>
                            <div class="small text-muted">{{ $tasks->count() }} task(s)</div>
                        </div>
                    </div>


                    @foreach($tasks as $task)
                        <div class="task-card">

                            <div class="d-flex justify-content-between gap-3">

                                <div style="flex:1">

                                    <!-- Project -->
                                    <div class="task-title">
                                        {{ optional($clients->firstWhere('client_id',$task->client_id))->client_company_name ?? 'No Project' }}
                                    </div>

                                    <!-- Leader Remark (Preview + "View More") -->
                                    <div class="preview-text mt-1">
                                        {!! Str::limit(e($task->leader_remark ?? '-'), 120) !!}
                                    </div>

                                    @if(strlen($task->leader_remark) > 120)
                                        <a href="javascript:void(0)" class="small text-primary"
                                           onclick="openRemarkModal('{{ addslashes($task->leader_remark) }}','Leader Remark')">
                                           View More
                                        </a>
                                    @endif


                                    <!-- Member Remark (Preview + modal) -->
                                    <div class="mt-2 small">
                                        <strong>Member remark:</strong>
                                        <div class="preview-text">
                                            {!! Str::limit(e($task->member_remark ?? '-'), 120) !!}
                                        </div>

                                        @if(strlen($task->member_remark) > 120)
                                            <a href="javascript:void(0)" class="small text-primary"
                                               onclick="openRemarkModal('{{ addslashes($task->member_remark) }}','Member Remark')">
                                               View More
                                            </a>
                                        @endif
                                    </div>
                                </div>


                                <!-- Status -->
                                <div style="min-width:130px; text-align:right;">
                                    <div class="task-status {{ $task->status }}">
                                        {{ ucfirst($task->status) }}
                                    </div>

                                    <div class="small text-muted mt-2">
                                        {{ $task->is_submitted ? 'Submitted' : 'Not submitted' }}
                                    </div>

                                </div>

                            </div>

                        </div>
                    @endforeach

                @endforeach

            @endif
        </div>

    </div> <!-- end pane -->

</div> <!-- end grid -->
<!-- ======================= MODAL FOR FULL REMARK VIEW ======================= -->

<!-- BACKDROP -->
<div id="remarkModalBackdrop"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); backdrop-filter:blur(3px); z-index:9998;">
</div>

<!-- MODAL -->
<div id="remarkModal"
     style="
        display:none;
        position:fixed;
        top:50%; left:50%;
        transform:translate(-50%, -50%);
        width:90%; max-width:650px;
        background:white;
        border-radius:14px;
        padding:22px 26px;
        box-shadow:0 10px 40px rgba(0,0,0,0.25);
        z-index:9999;
     ">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 id="remarkModalTitle" style="margin:0; font-weight:700; color:#111827;"></h5>

        <button onclick="closeRemarkModal()"
                style="border:none; background:transparent; font-size:22px; cursor:pointer;">
            &times;
        </button>
    </div>

    <div id="remarkModalContent"
         style="white-space:pre-wrap; font-size:14px; color:#374151; max-height:70vh; overflow-y:auto;">
    </div>

    <div class="text-end mt-3">
        <button onclick="closeRemarkModal()"
                class="btn btn-sm btn-primary">
            Close
        </button>
    </div>
</div>

<!-- ======================= JAVASCRIPT HELPERS ======================= -->
<script>

    function openRemarkModal(text, title = "Full Text") {
        document.getElementById('remarkModalTitle').innerText = title;
        document.getElementById('remarkModalContent').innerText = text;

        document.getElementById('remarkModalBackdrop').style.display = 'block';
        document.getElementById('remarkModal').style.display = 'block';
    }

    function closeRemarkModal() {
        document.getElementById('remarkModalBackdrop').style.display = 'none';
        document.getElementById('remarkModal').style.display = 'none';
    }

    // Close modal when clicking outside
    document.getElementById('remarkModalBackdrop').addEventListener('click', closeRemarkModal);

    // Close modal on ESC
    document.addEventListener('keydown', function(e){
        if(e.key === "Escape") closeRemarkModal();
    });

    // (Optional) Tooltip activation if needed
    if (window.bootstrap) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    }

</script>

