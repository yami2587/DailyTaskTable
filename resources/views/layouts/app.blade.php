<!DOCTYPE html>
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
            {{-- <a href="/dashboard" class="btn">Dashboard</a>
            <a href="/team" class="btn">Teams</a>
            <a href="/tasks" class="btn">Tasks</a>
            <a href="/daily-logs" class="btn">Daily Logs</a> --}}
            {{-- <a href="/targets" class="btn">Targets</a> --}}
        </div>
    </div>

    <div class="container">
        @yield('content')
    </div>

</body>
</html>
