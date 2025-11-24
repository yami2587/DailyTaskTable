<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::with('team')->orderBy('id', 'desc')->paginate(20);
        return view('tasks.index', compact('tasks'));
    }

    public function create()
    {
        $teams = Team::orderBy('team_name')->get();
        $employees = DB::table('employee_tbl')->select('emp_id', 'emp_name')->orderBy('emp_name')->get();
        $clients = DB::table('m_client_tbl')->select('client_id', 'client_company_name')->orderBy('client_company_name')->get();

        return view('tasks.create', compact('teams', 'employees', 'clients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'task_title' => 'required|string|max:191',
            'task_description' => 'nullable|string',
            'client_id' => 'nullable|string',
            'assigned_team_id' => 'nullable|integer',
            'assigned_member_id' => 'nullable|string',
            'task_type' => 'required|in:main,other',
            'status' => 'required|in:pending,in-progress,completed',
            'due_date' => 'nullable|date',
        ]);

        Task::create($data);
        return redirect()->route('tasks.index')->with('success', 'Task created.');
    }

    public function edit(Task $task)
    {
        $teams = Team::orderBy('team_name')->get();
        $employees = DB::table('employee_tbl')->select('emp_id', 'emp_name')->orderBy('emp_name')->get();
        $clients = DB::table('m_client_tbl')->select('client_id', 'client_company_name')->orderBy('client_company_name')->get();

        return view('tasks.edit', compact('task', 'teams', 'employees', 'clients'));
    }

    public function update(Request $request, Task $task)
    {
        $data = $request->validate([
            'task_title' => 'required|string|max:191',
            'task_description' => 'nullable|string',
            'client_id' => 'nullable|string',
            'assigned_team_id' => 'nullable|integer',
            'assigned_member_id' => 'nullable|string',
            'task_type' => 'required|in:main,other',
            'status' => 'required|in:pending,in-progress,completed',
            'due_date' => 'nullable|date',
        ]);

        $task->update($data);
        return redirect()->route('tasks.index')->with('success', 'Task updated.');
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return redirect()->route('tasks.index')->with('success', 'Task removed.');
    }
}
