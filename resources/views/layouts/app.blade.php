{{-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskTable</title>
    
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background-color: #222;
            color: #fff;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            margin: 0;
            font-size: 20px;
            letter-spacing: 0.5px;
        }


        .card {
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .btn {
            padding: 8px 14px;
            background: #3498db;
            color: white;
            border: none;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: #2980b9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table, th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background: #f0f0f0;
            font-weight: bold;
        }

        .flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .input-field {
            padding: 8px;
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-top: 5px;
            margin-bottom: 10px;
        }

        .danger {
            background: #e74c3c;
        }
        .danger:hover {
            background: #c0392b;
        }
        /* FIXES SIDEBAR JUMPING + WHITE GAP + FULL HEIGHT PAGE */
html, body {
    height: 100%;
}

/* Removes container padding influence on our layout */
.container {
    padding: 0 !important;
}

/* Ensure our admin layout fills entire viewport consistently */
.admin-wrap {
    min-height: calc(100vh - 70px); /* your navbar height = ~60-70px */
    overflow: hidden;
}

/* Fix sidebar sizing permanently */
.sidebar {
    height: calc(100vh - 90px) !important;
    overflow-y: auto !important;
    padding-bottom: 40px;
}

/* Fix main pane scroll consistency */
.pane {
    height: calc(100vh - 90px);
    overflow-y: auto;
    padding-right: 8px;
}

/* Fix page-shell full height always */
.page-shell {
    min-height: calc(100vh - 120px);
}

    </style>
</head>

<body>

    <div class="navbar">
        <h1>Task Manger</h1>
        <div>
            <a href="/dashboard" class="btn">Dashboard</a>
            <a href="/team" class="btn">Teams</a>
            <a href="/tasks" class="btn">Tasks</a>
            <a href="/daily-logs" class="btn">Daily Logs</a>
            <a href="/targets" class="btn">Targets</a>
        </div>
    </div>

    <div class="container">
        @yield('content')
    </div>

</body>
</html> --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskTable</title>

    {{-- GLOBAL CLEAN CSS JUST FOR LAYOUT --}}
    <style>
        * {
            box-sizing: border-box;
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f4f4;
        }

        /* HEADER */
        .app-header {
            background: #222;
            color: white;
            padding: 14px 20px;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: .5px;
        }

        /* MAIN APP LAYOUT */
        .app-wrapper {
            display: flex;
            height: calc(100vh - 55px); /* header = fixed height */
        }

        /* SIDEBAR */
        .app-sidebar {
            width: 240px;
            background: #ffffff;
            border-right: 1px solid #ddd;
            padding: 16px;
            overflow-y: auto;
        }

        .sidebar-title {
            font-size: 13px;
            font-weight: 700;
            color: #555;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .sidebar-link {
            display: block;
            padding: 10px 12px;
            margin-bottom: 6px;
            border-radius: 6px;
            background: #f7f7f7;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: .2s ease;
        }

        .sidebar-link:hover {
            background: #e3e7ff;
            color: #2734ff;
            transform: translateX(3px);
        }

        .sidebar-link.active {
            background: #6366f1;
            color: white !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* CONTENT SECTION */
        .app-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        @media(max-width: 900px) {
            .app-wrapper {
                flex-direction: column;
            }
            .app-sidebar {
                width: 100%;
                height: auto;
                border-right: none;
                border-bottom: 1px solid #ddd;
            }
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="app-header">
        Task Manager
    </div>

    <div class="app-wrapper">

        {{-- SIDEBAR (ROLE BASED) --}}
        <div class="app-sidebar">
            <div class="sidebar-title">Navigation</div>

            {{-- ADMIN SIDEBAR --}}
            @if(session('is_admin'))
                <a href="{{ route('admin.dashboard') }}"
                   class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    Daily Logs
                </a>

                {{-- <a
                 href="{{ route('admin.dashboard', ['tab' => 'teams']) }}"
                   class="sidebar-link {{ request()->routeIs('team.*') ? 'active' : '' }}">
                    Teams
                </a> --}}

                <a onclick="openCreateTeamModal()"
                   class="sidebar-link {{ request()->routeIs('team.create') ? 'active' : '' }}">
                    Create Team
                </a>

                {{-- <a href="/admin/logout" class="sidebar-link danger">Logout</a> --}}
            @endif


            {{-- LEADER SIDEBAR --}}
            @if(!session('is_admin') && session('emp_id'))
                @php
                    $isLeader = \App\Models\TeamMember::where('emp_id', session('emp_id'))->where('is_leader',1)->exists();
                @endphp

                @if($isLeader)
                    <a href="{{ route('tasktable') }}"
                       class="sidebar-link {{ request()->routeIs('tasktable') ? 'active' : '' }}">
                        Main Dashboard
                    </a>

                    <a href="{{ route('dashboard.mine') }}"
                       class="sidebar-link {{ request()->routeIs('dashboard.mine') ? 'active' : '' }}">
                       My Assigned Tasks
                    </a>
                    <a href="javascript:void(0)" onclick="openLeaderTutorial()" class="sidebar-link">
    How to Assign Tasks
</a>

                @endif

            @endif


            {{-- MEMBER SIDEBAR (non-leader) --}}
            @if(!session('is_admin') && session('emp_id') && empty($isLeader))
                <a href="{{ route('tasktable') }}"
                   class="sidebar-link {{ request()->routeIs('tasktable') ? 'active' : '' }}">
                    My Tasks
                </a>

                <a href="javascript:void(0)" onclick="openMemberTutorial()" class="sidebar-link">
    How to Submit Tasks
</a>


            @endif

        </div>

        {{-- MAIN PAGE CONTENT --}}
        <div class="app-content">
            @yield('content')
        </div>

    </div> {{-- wrapper --}}
    {{-- UNIVERSAL TUTORIAL MODALS --}}
<div class="modal fade" id="leaderTutorialModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">How Leaders Assign Tasks</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" style="line-height:1.6;">
                <h4>1. Open Your Dashboard</h4>
                <p>Go to your main dashboard where you see today's date and team members.</p>

                <hr>

                <h4>2. Create The Daily Sheet</h4>
                <p>If no sheet exists yet, click <strong>Create Today's Sheet</strong>.</p>

                <hr>

                <h4>3. Select a Member</h4>
                <p>Expand any member block in your team list.</p>

                <hr>

                <h4>4. Fill Task Details</h4>
                <ul>
                    <li>Select Project</li>
                    <li>Enter Task Description</li>
                    <li>Write optional Leader Notes</li>
                </ul>

                <hr>

                <h4>5. Click “Assign Task”</h4>
                <p>The task will appear instantly in their list.</p>

                <hr>

                <h4>6. Review Submissions</h4>
                <p>Come back anytime to check member submissions and update status.</p>

                <hr>
                <p><em>This modal is auto-updated with your system, nothing to maintain!</em></p>
            </div>
        </div>
    </div>
</div>



<div class="modal fade" id="memberTutorialModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">How Members Submit Tasks</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" style="line-height:1.6;">
                <h4>1. Open Your Dashboard</h4>
                <p>Here you will see the tasks assigned to you.</p>

                <hr>

                <h4>2. Update Your Task</h4>
                <ul>
                    <li>Click on your task</li>
                    <li>Write your progress</li>
                    <li>Add any remarks if required</li>
                </ul>

                <hr>

                <h4>3. Click “Submit Response”</h4>
                <p>Your leader will now see your update.</p>

                <hr>

                <h4>4. Submit Final Status</h4>
                <p>Choose: Completed, In Progress, or Not Completed.</p>

                <hr>

                <h4>5. You’re Done</h4>
                <p>Your submission is locked for the day unless your leader unfreezes it.</p>

                <hr>
                {{-- <p><em>Simple, clean, and fool-proof for any employee.</em></p> --}}
            </div>
        </div>
    </div>
</div>
<script>
    function openLeaderTutorial() {
        new bootstrap.Modal(document.getElementById('leaderTutorialModal')).show();
    }

    function openMemberTutorial() {
        new bootstrap.Modal(document.getElementById('memberTutorialModal')).show();
    }
</script>


</body>
</html>
