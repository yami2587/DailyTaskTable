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
    $summary = $summary ?? ['total_tasks'=>0,'completed'=>0,'in_progress'=>0,'not_completed'=>0];
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Bootstrap 5 CDN (remove if already in layout) --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* ======= Admin Dashboard: Premium + Clean (Option B icons) ======= */
.admin-wrap { padding:18px; }
.header-row { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
.title { font-size:20px; font-weight:700; color:#111827; }
.sub { color:#6b7280; font-size:13px; }

.admin-grid { display:flex; gap:18px; align-items:flex-start; }

/* SIDEBAR fixed */
.sidebar {
    width: 260px;
    position: sticky;
    top: 20px;
    height: calc(100vh - 40px);
    overflow-y: auto;
    padding-right: 6px;
}

/* Team item */
.team-item {
    padding:10px 12px;
    border-radius:8px;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:8px;
    justify-content:space-between;
    color:#374151;
    text-decoration:none;
    border:1px solid transparent;
}
.team-item:hover { background:#f7f9ff; text-decoration:none; }
.team-item.active {
    background: linear-gradient(90deg,#6366F1,#A78BFA);
    color:white;
    box-shadow:0 6px 18px rgba(99,102,241,0.12);
    border-color: rgba(255,255,255,0.06);
}

/* member list */
.member-list { display:flex; flex-direction:column; gap:8px; }
.member-card { padding:10px; border-radius:10px; background:#fff; border:1px solid #eef2ff; display:flex; gap:12px; align-items:center; }
.member-info { display:flex; flex-direction:column; gap:2px; }
.member-name { font-weight:700; color:#111827; }

/* Option B: flat circle icon */
.icon-circle {
    width:36px;
    height:36px;
    border-radius:50%;
    background: linear-gradient(135deg,#EEF2FF,#F8FAFF);
    display:flex;
    align-items:center;
    justify-content:center;
    color: #0b5ed7;
    font-weight:700;
    border:1px solid #e6eefb;
    box-shadow: 0 3px 8px rgba(11,94,215,0.06);
}

/* task card */
.task-card {
    padding: 14px;
    border-radius: 12px;
    background: #ffffffdd;
    border: 1px solid #e5e7eb;
    backdrop-filter: blur(4px);
    box-shadow: 0 6px 18px rgba(99,102,241,0.08);
    transition: transform .12s ease, box-shadow .12s ease;
    margin-bottom:10px;
}
.task-card:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(99,102,241,0.14); }

.task-title { font-weight:700; color:#0b5ed7; }
.task-desc { color:#374151; margin-top:6px; }

/* status pill */
.task-status {
    padding: 6px 12px;
    border-radius: 999px;
    font-weight:700;
    font-size:12px;
    display:inline-block;
    text-transform:capitalize;
}
.task-status.completed { background: linear-gradient(135deg,#10B981,#059669); color:#fff; box-shadow:0 4px 12px rgba(16,185,129,0.12); }
.task-status.in_progress { background: linear-gradient(135deg,#F59E0B,#D97706); color:#fff; box-shadow:0 4px 12px rgba(245,158,11,0.12); }
.task-status.not_completed { background: linear-gradient(135deg,#EF4444,#B91C1C); color:#fff; box-shadow:0 4px 12px rgba(239,68,68,0.12); }

/* small meta */
.small { font-size:13px; color:#6b7280; }
.meta { font-size:13px; color:#6b7280; }

/* limit long paragraphs to readable block and single-line behavior where requested */
.task-desc { max-width: 72ch; white-space: normal; word-break: break-word; line-height:1.45; }
.member-small-desc { max-width: 60ch; white-space: normal; overflow-wrap: break-word; }

/* last-6-days strip (rounded pills) */
.day-pill { border-radius:999px; padding:6px 12px; font-weight:600; }

/* responsive */
@media(max-width:980px) {
    .admin-grid { flex-direction:column; }
    .sidebar { width:100%; order:2; height:auto; position:relative; }
    .pane { order:1; }
}
</style>

<div class="admin-wrap">
    <div class="header-row">
        <div>
            <div class="title">Admin: Team Daily Reports</div>
            <div class="sub">Super-admin view — browse teams and daily sheets</div>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <form id="adminDateForm" method="GET" action="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2">
                <input type="hidden" name="team_id" value="{{ $selectedTeamId }}" id="selectedTeamInput">
                <input type="date" name="date" value="{{ $date }}" onchange="document.getElementById('adminDateForm').submit()" class="form-control form-control-sm" />
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
            </form>

            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
    </div>

    <div class="admin-grid">
        {{-- SIDEBAR: teams --}}
        <div class="sidebar">
            <div style="margin-bottom:8px; font-weight:700;">Teams</div>
            <div class="list-group">
                @foreach($teams as $t)
                    @php
                        $tid = $t->team_id ?? $t->id ?? null;
                        $tname = $t->team_name ?? ('Team '.$tid);
                        $active = ($tid == $selectedTeamId);
                    @endphp
                    <a href="{{ route('admin.dashboard', ['team_id' => $tid, 'date' => $date]) }}"
                        class="team-item list-group-item list-group-item-action {{ $active ? 'active':'' }}">
                        <div style="display:flex; gap:10px; align-items:center;">
                            {{-- <div class="icon-circle" aria-hidden="true">
                              
                                 <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="color:#0b5ed7;">
                                  <path d="M9.828 4a.5.5 0 0 1 .354.146L11 5.965 13.8 9H2V4a1 1 0 0 1 1-1h6.828z"/>
                                  <path d="M2 4v9a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V6.5L11.5 4H3a1 1 0 0 0-1 0z"/>
                                </svg> 
                            </div> --}}
                            <div style="font-weight:600;">{{ $tname }}</div>
                        </div>
                        <div class="small text-muted">{{ \App\Models\TeamMember::where('team_id',$tid)->count() }} members</div>
                    </a>
                @endforeach
            </div>

            <div style="margin-top:14px;">
                <div style="font-weight:700; margin-bottom:6px;">Summary ({{ $date }})</div>
                <div class="d-flex gap-2">
                    <div class="p-2" style="border-radius:8px; background:#fff; border:1px solid #eef2ff; min-width:92px; text-align:center;">
                        <div class="small">Tasks</div>
                        <div style="font-weight:700">{{ $summary['total_tasks'] }}</div>
                    </div>

                    <div class="p-2" style="border-radius:8px; background:#fff; border:1px solid #eef2ff; min-width:92px; text-align:center;">
                        <div class="small">Completed</div>
                        <div style="font-weight:700; color: #065f46;">{{ $summary['completed'] }}</div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <div class="p-2" style="border-radius:8px; background:#fff; border:1px solid #eef2ff; min-width:92px; text-align:center;">
                        <div class="small">In Progress</div>
                        <div style="font-weight:700;">{{ $summary['in_progress'] }}</div>
                    </div>

                    <div class="p-2" style="border-radius:8px; background:#fff; border:1px solid #eef2ff; min-width:92px; text-align:center;">
                        <div class="small">Not Done</div>
                        <div style="font-weight:700; color:#7a0f0f;">{{ $summary['not_completed'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MAIN PANE --}}
        <div class="pane" style="flex:1;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 style="margin:0;">@if($selectedTeamId) Team: {{ $sheet->team->team_name ?? 'Team '.$selectedTeamId }} @else No team selected @endif</h5>
                    <div class="text-muted small">Date: {{ $date }}</div>
                </div>

                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.dashboard') }}">All teams</a>
                    <a href="{{ route('admin.dashboard', ['team_id' => $selectedTeamId, 'date' => $date]) }}" class="btn btn-primary btn-sm">Refresh</a>
                </div>
            </div>

            {{-- Members list --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div style="font-weight:700;">Team Members</div>
                        <div class="small text-muted">{{ $members->count() }} members</div>
                    </div>

                    <div class="member-list mt-3">
                        @foreach($members as $m)
                            @php
                                $e = $m->employee ?? null;
                                $name = $e->emp_name ?? $m->emp_id;
                                // small icon label - Option B: flat circle with user svg
                            @endphp
                            <div class="member-card">
                                <div class="icon-circle" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="color:#0b5ed7;">
                                      <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z"/>
                                      <path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                    </svg>
                                </div>

                                <div class="member-info">
                                    <div class="member-name">{{ $name }} @if($m->is_leader) <span class="small text-primary"> — Leader</span> @endif</div>
                                    {{-- <div class="small">ID: {{ $m->emp_id }}</div> --}}
                                </div>

                                <div class="ms-auto text-end">
                                    <div class="small">Assigned today: <strong>{{ $assignments->where('member_emp_id',$m->emp_id)->count() }}</strong></div>
                                </div>
                            </div>
                        @endforeach

                        @if($members->isEmpty())
                            <div class="small text-muted mt-2">No members found for this team.</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tasks area: grouped by member --}}
            <div>
                @if(!$sheet)
                    <div class="alert alert-warning">No sheet exists for this date for the selected team.</div>
                @endif

                @if($sheet && $assignments->isEmpty())
                    <div class="alert alert-info">Sheet exists but no assignments were added for this date.</div>
                @endif

                @if($sheet && $assignments->isNotEmpty())
                    @php
                        $grouped = $assignments->groupBy('member_emp_id');
                    @endphp

                    @foreach($grouped as $memberId => $memberAssignments)
                        @php
                            $member = $members->firstWhere('emp_id', $memberId);
                            $memberName = $member ? ($member->employee->emp_name ?? $member->emp_id) : ($memberId ?? 'Unknown');
                        @endphp

                        {{-- Heading: member --}}
                        <div class="mb-2" style="font-weight:700; margin-top:14px; display:flex; align-items:center; gap:12px;">
                            <div class="icon-circle" aria-hidden="true" style="width:40px;height:40px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" style="color:#0b5ed7;">
                                  <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z"/>
                                  <path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                </svg>
                            </div>
                            <div>
                                <div style="font-weight:800;">{{ $memberName }}</div>
                                <div class="small text-muted">{{ $memberAssignments->count() }} task(s) for {{ \Carbon\Carbon::parse($date)->format('d M, Y') }}</div>
                            </div>
                        </div>

                        @foreach($memberAssignments as $a)
                            <div class="task-card">
                                <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                                    <div style="flex:1;">
                                        <div class="task-title">{{ optional($clients->firstWhere('client_id',$a->client_id))->client_company_name ?? '—' }}</div>
                                        <div class="task-desc">{{ $a->leader_remark ?? $a->task_description ?? '-' }}</div>

                                        <div class="meta mt-2">
                                            <strong>Member remark:</strong>
                                            <span class="member-small-desc">{{ $a->member_remark ?? '-' }}</span>
                                        </div>
                                    </div>

                                    <div style="min-width:140px; text-align:right;">
                                        <div class="mb-2">
                                            <span class="task-status {{ $a->status ?? 'not_completed' }}">{{ ucfirst($a->status ?? 'not_completed') }}</span>
                                        </div>

                                        <div class="small text-muted">Submitted: {{ $a->is_submitted ? 'Yes' : 'No' }}</div>
                                        <div class="small text-muted mt-1">Assign ID: {{ $a->id }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Bootstrap JS (optional if layout already includes it) --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // keep selectedTeamInput in sync (for date form)
    document.querySelectorAll('.team-item').forEach(function(el){
        el.addEventListener('click', function(ev){
            // default anchor navigation will handle team selection; the input is left for the date form
        });
    });
</script>
@endsection
