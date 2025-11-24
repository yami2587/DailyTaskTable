<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeamDailyLog;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeamDailyLogController extends Controller
{
    public function index(Request $request)
    {
        $q = TeamDailyLog::query();

        if ($request->filled('team_id')) {
            $q->where('team_id', $request->team_id);
        }
        if ($request->filled('log_date')) {
            $q->where('log_date', $request->log_date);
        }

        $logs = $q->orderBy('log_date', 'desc')->paginate(30);
        $teams = Team::orderBy('team_name')->get();

        return view('daily-logs.index', compact('logs', 'teams'));
    }

    // Show a prefilled form (template) for creating daily logs for a team/date
    public function create(Request $request)
    {
        $teams = Team::orderBy('team_name')->get();
        $team = null;
        $members = collect();
        $leader = null;
        $task = null;

        $date = $request->input('date', Carbon::today()->toDateString());

        if ($request->filled('team_id')) {
            $team = Team::find($request->team_id);
            $members = TeamMember::where('team_id', $team->id)->get();
            $leaderMember = TeamMember::where('team_id', $team->id)->where('is_leader', true)->first();
            if ($leaderMember) {
                $leader = DB::table('employee_tbl')->where('emp_id', $leaderMember->emp_id)->first();
            }
        }

        $tasks = Task::where('assigned_team_id', optional($team)->id)->get();

        // 7 plan rows + 7 target rows will be rendered in view
        return view('daily-logs.create', compact('teams', 'team', 'members', 'leader', 'date', 'tasks'));
    }

    // Store daily log rows: expected to receive array of entries (one per member)
    public function store(Request $request)
    {
        $data = $request->validate([
            'task_id' => 'nullable|integer',
            'team_id' => 'required|integer',
            'leader_emp_id' => 'required|string',
            'log_date' => 'required|date',
            'notes' => 'nullable|string',
            'member_emp_id' => 'required|array',
            'member_emp_id.*' => 'required|string',
        ]);

        // Save one record per member
        foreach ($data['member_emp_id'] as $memberEmpId) {
            TeamDailyLog::create([
                'task_id' => $data['task_id'] ?? null,
                'team_id' => $data['team_id'],
                'leader_emp_id' => $data['leader_emp_id'],
                'member_emp_id' => $memberEmpId,
                'log_date' => $data['log_date'],
                'notes' => $data['notes'] ?? null,
            ]);
        }

        return redirect()->route('daily-logs.index')->with('success', 'Daily log saved.');
    }

    public function destroy(TeamDailyLog $teamDailyLog)
    {
        $teamDailyLog->delete();
        return back()->with('success', 'Log removed.');
    }

    // Utility: auto-generate logs for all teams and their members for a specific date
    public function autoGenerateForDate($date = null)
    {
        $date = $date ?: Carbon::today()->toDateString();

        $teams = Team::all();
        $created = 0;

        foreach ($teams as $team) {
            $members = TeamMember::where('team_id', $team->id)->get();
            $leader = TeamMember::where('team_id', $team->id)->where('is_leader', true)->first();

            foreach ($members as $m) {
                // avoid duplicates for same date & member & team
                $exists = TeamDailyLog::where('team_id', $team->id)
                    ->where('member_emp_id', $m->emp_id)
                    ->where('log_date', $date)
                    ->exists();

                if (!$exists) {
                    TeamDailyLog::create([
                        'task_id' => null,
                        'team_id' => $team->id,
                        'leader_emp_id' => $leader ? $leader->emp_id : $m->emp_id,
                        'member_emp_id' => $m->emp_id,
                        'log_date' => $date,
                        'notes' => null,
                    ]);
                    $created++;
                }
            }
        }

        return "Auto-generated {$created} log rows for date {$date}";
    }
}
