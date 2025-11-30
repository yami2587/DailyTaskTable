<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeamDailySheet;
use App\Models\TeamDailyAssignment;
use App\Models\TaskMain;
use App\Models\EmployeeTaskMain;
use App\Models\TeamMember;
use App\Models\Client;
use App\Models\Employee;
use Carbon\Carbon;

class DailySheetController extends Controller
{
    /* MAIN DASHBOARD (Leader + Member View) */
    public function dashboard(Request $r)
    {
        $empId         = session('emp_id');
        $employeeName  = session('employee_name') ?? session('emp_name') ?? null;
        $date          = $r->date ?? date('Y-m-d');

        // Check if user is a leader
        $teamLeader = TeamMember::where('emp_id', $empId)
            ->where('is_leader', true)
            ->first();

        // Load all clients
        $clients = Client::orderBy('client_company_name')->get();

        // MEMBER flow (non-leader)
        if (!$teamLeader) {

            $memberTeam = TeamMember::where('emp_id', $empId)->first();

            // Member not assigned to any team
            if (!$memberTeam) {
                return view('dashboard.sheet', [
                    'empId'        => $empId,
                    'employeeName' => $employeeName,
                    'date'         => $date,
                    'sheet'        => null,
                    'assignments'  => collect(),
                    'members'      => collect(),
                    'clients'      => $clients,
                    'isLeader'     => false,
                    // MEMBER should NOT be locked by leader finalization
                    'isFinalized'  => false, // <<< CHANGED: force false for members
                ]);
            }

            // Sheet for today (if any)
            $sheet = TeamDailySheet::where('team_id', $memberTeam->team_id)
                ->where('sheet_date', $date)
                ->first();

            // Member’s personal tasks
            $assignments = TeamDailyAssignment::where('member_emp_id', $empId)
                ->where('sheet_id', $sheet->id ?? 0)
                ->get();

            // Load ALL team members (FIX)
            $teamMembers = TeamMember::with('employee')
                ->where('team_id', $memberTeam->team_id)
                ->get();

            // NOTE: For members we DO NOT treat a created TaskMain as "locking" their UI.
            // We still keep TaskMain for history but members remain able to submit.
            $isFinalized = false; // <<< CHANGED: always false for members

            return view('dashboard.sheet', [
                'empId'        => $empId,
                'employeeName' => $employeeName,
                'date'         => $date,
                'sheet'        => $sheet,
                'assignments'  => $assignments,
                'members'      => $teamMembers,
                'clients'      => $clients,
                'isLeader'     => false,
                'isFinalized'  => $isFinalized
            ]);
        }

        /* LEADER FLOW: (team leader viewing team dashboard) */
        $sheet = TeamDailySheet::where('team_id', $teamLeader->team_id)
            ->where('sheet_date', $date)
            ->first();

        // Auto create sheet for leader
        if (!$sheet) {
            $sheet = TeamDailySheet::create([
                'team_id'       => $teamLeader->team_id,
                'leader_emp_id' => $empId,
                'sheet_date'    => $date,
                'target_text'   => ''
            ]);
        }

        // Load team members (with employee relation)
        $members = TeamMember::with('employee')
            ->where('team_id', $teamLeader->team_id)
            ->get();

        // Assignments for this sheet
        $assignments = TeamDailyAssignment::where('sheet_id', $sheet->id)->get();

        // Freeze check for leader view: only leader sees the sheet as finalized/locked.
        $isFinalized = TaskMain::where('team_id', $sheet->team_id)
            ->where('sheet_date', $sheet->sheet_date)
            ->exists(); // <<< CHANGED: unchanged logic but only applied for leader

        return view('dashboard.sheet', [
            'empId'        => $empId,
            'employeeName' => $employeeName,
            'date'         => $date,
            'sheet'        => $sheet,
            'members'      => $members,
            'assignments'  => $assignments,
            'clients'      => $clients,
            'isLeader'     => true,
            'isFinalized'  => $isFinalized
        ]);
    }


    /* LEADER PERSONAL DASHBOARD (MY TASKS) */
    public function myDashboard(Request $r)
    {
        $empId        = session('emp_id');
        $employeeName = session('employee_name') ?? session('emp_name') ?? null;
        $date         = $r->date ?? date('Y-m-d');

        $member = TeamMember::where('emp_id', $empId)->first();

        $sheet = null;
        if ($member) {
            $sheet = TeamDailySheet::where('team_id', $member->team_id)
                ->where('sheet_date', $date)
                ->first();
        }

        $clients = Client::orderBy('client_company_name')->get();

        // Load leader’s own assignments
        $assignments = TeamDailyAssignment::where('member_emp_id', $empId)
            ->where('sheet_id', $sheet->id ?? 0)
            ->get();

        // Load team members (FIXED)
        $teamMembers = TeamMember::with('employee')
            ->where('team_id', $member->team_id)
            ->get();

        // IMPORTANT: Leader's personal "myDashboard" should NOT be locked by final snapshot.
        // Finalization only locks the leader's team dashboard, not the leader's personal submissions view.
        $isFinalized = false; // <<< CHANGED

        return view('dashboard.sheet', [
            'empId'        => $empId,
            'employeeName' => $employeeName,
            'date'         => $date,
            'sheet'        => $sheet,
            'assignments'  => $assignments,
            'members'      => $teamMembers,
            'clients'      => $clients,
            'isLeader'     => false,
            'isFinalized'  => $isFinalized
        ]);
    }

    /* CREATE NEW SHEET (unchanged) */
    public function createSheet(Request $request)
    {
        $data = $request->validate([
            'team_id'       => 'required|integer',
            'sheet_date'    => 'required|date',
            'leader_emp_id' => 'required|string',
        ]);

        TeamDailySheet::firstOrCreate(
            ['team_id' => $data['team_id'], 'sheet_date' => $data['sheet_date']],
            ['leader_emp_id' => $data['leader_emp_id'], 'target_text' => '']
        );

        return back()->with('success', 'Sheet created.');
    }

    /* ASSIGN NEW TASK  (unchanged) */
    public function assign(Request $request)
    {
        $data = $request->validate([
            'sheet_id'       => 'required|integer',
            'member_emp_id'  => 'required|string',
            'client_id'      => 'nullable|string',
            'task_description' => 'nullable|string',
            'leader_remark'  => 'nullable|string'
        ]);

        $sheet = TeamDailySheet::findOrFail($data['sheet_id']);

        if ($sheet->sheet_date < Carbon::today()) {
            return response()->json(['error' => 'Past sheets locked'], 422);
        }

        $assign = TeamDailyAssignment::create([
            'sheet_id'       => $data['sheet_id'],
            'member_emp_id'  => $data['member_emp_id'],
            'client_id'      => $data['client_id'],
            'task_description' => $data['task_description'] ?? $data['leader_remark'],
            'leader_remark'  => $data['leader_remark'],
            'status'         => 'not_completed',
            'member_remark'  => null,
            'is_submitted'   => false
        ]);

        return response()->json(['ok' => true, 'id' => $assign->id], 200);
    }

    /* UPDATE ASSIGNMENT (unchanged) */
    public function updateAssignment(Request $request, TeamDailyAssignment $assign)
    {
        $sheet = $assign->sheet;

        if ($sheet->sheet_date < Carbon::today()) {
            return back()->with('error', 'Past sheets locked.');
        }

        $assign->update($request->only(['client_id', 'task_description', 'leader_remark', 'member_remark']));

        return back()->with('success', 'Updated.');
    }

    /* DELETE ASSIGNMENT (unchanged) */
    public function deleteAssignment(TeamDailyAssignment $assign)
    {
        $sheet = $assign->sheet;

        if ($sheet->sheet_date < Carbon::today()) {
            return back()->with('error', 'Past sheets locked.');
        }

        $assign->delete();

        return back()->with('success', 'Deleted.');
    }

    /* MEMBER SUBMIT TASK (unchanged) */
    public function memberSubmit(Request $request, TeamDailyAssignment $assign)
    {
        $sheet = $assign->sheet;

        if ($sheet->sheet_date < Carbon::today()) {
            return response()->json(['error' => 'Past sheets locked'], 422);
        }

        $data = $request->validate([
            'status'        => 'required|in:completed,not_completed,in_progress',
            'member_remark' => 'nullable|string'
        ]);

        $assign->update([
            'status'        => $data['status'],
            'member_remark' => $data['member_remark'],
            'is_submitted'  => true
        ]);

        return response()->json(['ok' => true], 200);
    }

    /* SAVE TARGET or FINAL SNAPSHOT */
    public function saveDayLog(Request $request, TeamDailySheet $sheet)
    {
        // Prevent updating past dates (unchanged)
        if ($sheet->sheet_date < Carbon::today()) {
            return response()->json(['error' => 'Past sheets locked'], 422);
        }

        // Only save today_target (AJAX)
        if ($request->has('today_target') && !$request->has('finalize')) {
            $sheet->update(['target_text' => $request->today_target]);
            return response()->json(['ok' => true], 200);
        }

        // FINAL SNAPSHOT: only leader can create the TaskMain snapshot
        $empId = session('emp_id');
        if ($sheet->leader_emp_id != $empId) {
            return response()->json(['error' => 'Forbidden - only leader can finalize'], 403);
        }

        // Prevent duplicate finalize for same team/date
        $exists = TaskMain::where('team_id', $sheet->team_id)
            ->where('sheet_date', $sheet->sheet_date)
            ->exists();
        if ($exists) {
            return response()->json(['error' => 'Already finalized'], 422);
        }

        // Create snapshot records
        $taskMain = TaskMain::create([
            'team_id'       => $sheet->team_id,
            'sheet_date'    => $sheet->sheet_date,
            'leader_emp_id' => $sheet->leader_emp_id,
            'today_target'  => $request->today_target ?? $sheet->target_text,
            'day_remark'    => null
        ]);

        foreach ($sheet->assignments as $a) {
            EmployeeTaskMain::create([
                'task_main_id'   => $taskMain->id,
                'member_emp_id'  => $a->member_emp_id,
                'client_id'      => $a->client_id,
                'task_description' => $a->task_description,
                'leader_remark'  => $a->leader_remark,
                'status'         => $a->status,
                'member_remark'  => $a->member_remark,
            ]);
        }

        return response()->json(['ok' => true], 200);
    }

    /* UNFREEZE (TESTING HELPER)  */
    public function unfreezeSheet(Request $request, TeamDailySheet $sheet)
    {
        $empId = session('emp_id');

        if ($sheet->leader_emp_id != $empId) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        TaskMain::where('team_id', $sheet->team_id)
            ->where('sheet_date', $sheet->sheet_date)
            ->delete();

        EmployeeTaskMain::whereHas('taskMain', function ($q) use ($sheet) {
            $q->where('team_id', $sheet->team_id)
                ->where('sheet_date', $sheet->sheet_date);
        })->delete();

        return response()->json(['ok' => true], 200);
    }
}
