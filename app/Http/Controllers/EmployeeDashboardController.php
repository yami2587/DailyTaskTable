<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamDailySheet;
use App\Models\TeamDailyAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeDashboardController extends Controller
{
    // Show the sheet: either emp_id via route or ?emp_id=
    public function show(Request $request, $emp_id = null)
    {
        $empId = $emp_id ?: $request->query('emp_id');
        if (!$empId) abort(400, 'emp_id required');

        $date = $request->query('date', Carbon::today()->toDateString());

        // teams where employee is member
        $memberTeamIds = DB::table('team_members_tbl')->where('emp_id', $empId)->pluck('team_id')->toArray();

        // leader team ids for this emp
        $leaderTeamIds = DB::table('team_members_tbl')->where('emp_id', $empId)->where('is_leader', true)->pluck('team_id')->toArray();

        // Member assignments for this emp on that date
        $memberAssignments = TeamDailyAssignment::where('member_emp_id', $empId)
            ->whereHas('sheet', function ($q) use ($date) {
                $q->where('sheet_date', $date);
            })
            ->with('sheet')
            ->get();

        // Leader sheets for teams he leads (for that date)
        $leaderSheets = TeamDailySheet::with(['assignments'])->whereIn('team_id', $leaderTeamIds)->where('sheet_date', $date)->get();

        // Employees & clients for dropdowns
        $employees = DB::table('employee_tbl')->select('emp_id', 'emp_name')->orderBy('emp_name')->get();
        $clients = DB::table('m_client_tbl')->select('client_id', 'client_company_name')->orderBy('client_company_name')->get();

        return view('dashboard.sheet', compact(
            'empId',
            'date',
            'memberAssignments',
            'leaderSheets',
            'memberTeamIds',
            'leaderTeamIds',
            'employees',
            'clients'
        ));
    }

    // Leader creates/ensures a sheet for team & date and auto-adds empty assignment rows (idempotent)
    public function createSheet(Request $request)
    {
        $data = $request->validate([
            'team_id' => 'required|integer',
            'sheet_date' => 'required|date',
            'leader_emp_id' => 'required|string',
            'working_plan' => 'nullable|string',
            'targets' => 'nullable|array'
        ]);

        $sheet = TeamDailySheet::firstOrCreate(
            ['team_id' => $data['team_id'], 'sheet_date' => $data['sheet_date']],
            ['leader_emp_id' => $data['leader_emp_id'], 'working_plan' => $data['working_plan'] ?? null, 'targets' => $data['targets'] ?? []]
        );

        // Auto-add assignments for team members if none exist for this sheet
        $exists = TeamDailyAssignment::where('sheet_id', $sheet->id)->exists();
        if (!$exists) {
            $members = TeamMember::where('team_id', $data['team_id'])->get();
            foreach ($members as $m) {
                TeamDailyAssignment::create([
                    'sheet_id' => $sheet->id,
                    'member_emp_id' => $m->emp_id,
                ]);
            }
        }

        return back()->with('success', 'Sheet prepared.');
    }

    // Leader sets task & leader_remark for an assignment row
    public function assignTask(Request $request, TeamDailyAssignment $assignment)
    {
        $data = $request->validate([
            'client_id' => 'nullable|string',
            'task_description' => 'nullable|string',
            'leader_remark' => 'nullable|string',
        ]);

        // enforce only today editable
        if ($assignment->sheet->sheet_date->lt(Carbon::today())) {
            return back()->with('error', 'Cannot edit past sheets.');
        }

        $assignment->update([
            'client_id' => $data['client_id'] ?? $assignment->client_id,
            'task_description' => $data['task_description'] ?? $assignment->task_description,
            'leader_remark' => $data['leader_remark'] ?? $assignment->leader_remark,
        ]);

        return back()->with('success', 'Assignment updated.');
    }

    // Member updates their progress
    public function memberUpdate(Request $request, TeamDailyAssignment $assignment)
    {
        $data = $request->validate(['member_update' => 'nullable|string']);

        if ($assignment->sheet->sheet_date->lt(Carbon::today())) {
            return back()->with('error', 'Cannot modify past sheets.');
        }

        $assignment->update(['member_update' => $data['member_update'] ?? null]);

        return back()->with('success', 'Progress saved.');
    }

    // Member marks completed
    public function markComplete(Request $request, TeamDailyAssignment $assignment)
    {
        if ($assignment->sheet->sheet_date->lt(Carbon::today())) {
            return back()->with('error', 'Cannot modify past sheets.');
        }

        $assignment->update([
            'is_completed' => true,
            'completed_at' => Carbon::now()
        ]);

        return back()->with('success', 'Marked completed.');
    }

    // Leader updates sheet header (working plan + targets)
    public function updateSheetHeader(Request $request, TeamDailySheet $sheet)
    {
        $data = $request->validate([
            'working_plan' => 'nullable|string',
            'targets' => 'nullable|array'
        ]);

        if ($sheet->sheet_date->lt(Carbon::today())) {
            return back()->with('error', 'Cannot modify past sheets.');
        }

        $sheet->update([
            'working_plan' => $data['working_plan'] ?? $sheet->working_plan,
            'targets' => $data['targets'] ?? $sheet->targets,
        ]);

        return back()->with('success', 'Sheet updated.');
    }
}
