<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TodayTarget;
use App\Models\Task;

class TodayTargetController extends Controller
{
    public function index()
    {
        $targets = TodayTarget::with('task')->orderBy('target_date', 'desc')->paginate(25);
        return view('targets.index', compact('targets'));
    }

    public function create()
    {
        $tasks = Task::orderBy('task_title')->get();
        return view('targets.create', compact('tasks'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'task_id' => 'required|integer',
            'title' => 'required|string|max:191',
            'remark' => 'nullable|string',
            'is_done' => 'nullable|boolean',
            'target_date' => 'required|date',
        ]);

        TodayTarget::create($data);
        return redirect()->route('targets.index')->with('success', 'Target saved.');
    }

    public function edit(TodayTarget $target)
    {
        $tasks = Task::orderBy('task_title')->get();
        return view('targets.edit', compact('target', 'tasks'));
    }

    public function update(Request $request, TodayTarget $target)
    {
        $data = $request->validate([
            'task_id' => 'required|integer',
            'title' => 'required|string|max:191',
            'remark' => 'nullable|string',
            'is_done' => 'nullable|boolean',
            'target_date' => 'required|date',
        ]);

        $target->update($data);
        return redirect()->route('targets.index')->with('success', 'Target updated.');
    }

    public function destroy(TodayTarget $target)
    {
        $target->delete();
        return redirect()->route('targets.index')->with('success', 'Target removed.');
    }

    // quick toggle
    public function toggleDone(TodayTarget $target)
    {
        $target->is_done = !$target->is_done;
        $target->save();
        return back()->with('success', 'Target status updated.');
    }
}
