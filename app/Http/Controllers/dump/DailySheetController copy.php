<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeamDailySheet;
use App\Models\TeamDailyAssignment;
use App\Models\TaskMain;
use App\Models\EmployeeTaskMain;
use App\Models\TeamMember;
use App\Models\Client; 
use Carbon\Carbon;

class DailySheetController extends Controller
{
    public function dashboard(Request $r)
    {
        $empId = session('emp_id');
        $date  = $r->date ?? date('Y-m-d');

        // fetch employee name
        $employeeName = session('employee_name') ?? null;

        // 1 check if leader
        $teamLeader = TeamMember::where('emp_id', $empId)
            ->where('is_leader', true)
            ->first();

        // always send clients list
        $clients = Client::orderBy('client_company_name')->get();

        //----------------------------------------------------------------------
        // MEMBER VIEW
        //----------------------------------------------------------------------
        if (!$teamLeader) {

            // find team this member belongs to
            $memberTeam = TeamMember::where('emp_id', $empId)->first();

            if (!$memberTeam) {
                return view('dashboard.sheet', [
                    'empId' => $empId,
                    'employeeName' => $employeeName,
                    'date' => $date,
                    'sheet' => null,
                    'assignments' => collect(),
                    'clients' => $clients,
                    'members' => collect(),
                    'isLeader' => false
                ]);
            }

            $sheet = TeamDailySheet::where('team_id', $memberTeam->team_id)
                ->where('sheet_date', $date)
                ->first();

            $assignments = TeamDailyAssignment::where('member_emp_id', $empId)
                ->where('sheet_id', $sheet->id ?? 0)
                ->get();

            return view('dashboard.sheet', [
                'empId' => $empId,
                'employeeName' => $employeeName,
                'date' => $date,
                'sheet' => $sheet,
                'assignments' => $assignments,
                'members' => collect(),
                'clients' => $clients,
                'isLeader' => false
            ]);
        }

        //----------------------------------------------------------------------
        // LEADER VIEW
        //----------------------------------------------------------------------
        $sheet = TeamDailySheet::where('team_id', $teamLeader->team_id)
            ->where('sheet_date', $date)
            ->first();

        // auto-create sheet
        if (!$sheet) {
            $sheet = TeamDailySheet::create([
                'team_id' => $teamLeader->team_id,
                'leader_emp_id' => $empId,
                'sheet_date' => $date,
                'target_text' => ''
            ]);
        }

        // load members
        $members = TeamMember::with('employee')
            ->where('team_id', $teamLeader->team_id)
            ->get();

        // load assignments
        $assignments = TeamDailyAssignment::where('sheet_id', $sheet->id)->get();

        return view('dashboard.sheet', [
            'empId' => $empId,
            'employeeName' => $employeeName,
            'date' => $date,
            'sheet' => $sheet,
            'members' => $members,
            'assignments' => $assignments,
            'clients' => $clients,
            'isLeader' => true
        ]);
    }

    //----------------------------------------------------------------------
    // CREATE SHEET
    //----------------------------------------------------------------------
    public function createSheet(Request $request)
    {
        $data = $request->validate([
            'team_id' => 'required|integer',
            'sheet_date' => 'required|date',
            'leader_emp_id' => 'required|string',
        ]);

        $sheet = TeamDailySheet::firstOrCreate(
            [
                'team_id' => $data['team_id'],
                'sheet_date' => $data['sheet_date']
            ],
            [
                'leader_emp_id' => $data['leader_emp_id'],
                'target_text' => ''
            ]
        );

        return back()->with('success', 'Sheet created.');
    }

    //----------------------------------------------------------------------
    // ASSIGN TASK (LEADER)
    //----------------------------------------------------------------------
    public function assign(Request $request)
    {
        $data = $request->validate([
            'sheet_id' => 'required|integer',
            'member_emp_id' => 'required|string',
            'client_id' => 'nullable|string',
            'task_description' => 'nullable|string',
            'leader_remark' => 'nullable|string'
        ]);

        $sheet = TeamDailySheet::findOrFail($data['sheet_id']);

        if ($sheet->sheet_date < Carbon::today()) {
            return back()->with('error', 'Past sheets are locked.');
        }

        TeamDailyAssignment::create([
            'sheet_id' => $data['sheet_id'],
            'member_emp_id' => $data['member_emp_id'],
            'client_id' => $data['client_id'],
            'task_description' => $data['leader_remark'], // same as your UI
            'leader_remark' => $data['leader_remark'],
            'status' => 'not_completed',
            'is_submitted' => false
        ]);

        return back()->with('success', 'Task added.');
    }

    //----------------------------------------------------------------------
    // UPDATE ASSIGNMENT
    //----------------------------------------------------------------------
    public function updateAssignment(Request $request, TeamDailyAssignment $assign)
    {
        $sheet = $assign->sheet;
        if ($sheet->sheet_date < Carbon::today()) {
            return back()->with('error', 'Past sheets locked.');
        }

        $assign->update([
            'client_id' => $request->client_id,
            'task_description' => $request->task_description,
            'leader_remark' => $request->leader_remark,
        ]);

        return back()->with('success', 'Updated.');
    }

    //----------------------------------------------------------------------
    // DELETE
    //----------------------------------------------------------------------
    public function deleteAssignment(TeamDailyAssignment $assign)
    {
        $sheet = $assign->sheet;

        if ($sheet->sheet_date < Carbon::today()) {
            return back()->with('error', 'Past sheets locked.');
        }

        $assign->delete();
        return back()->with('success', 'Deleted.');
    }

    //----------------------------------------------------------------------
    // MEMBER SUBMIT
    //----------------------------------------------------------------------
    public function memberSubmit(Request $request, TeamDailyAssignment $assign)
    {
        $sheet = $assign->sheet;
        if ($sheet->sheet_date < Carbon::today()) {
            return back()->with('error', 'Past sheets locked.');
        }

        $data = $request->validate([
            'status' => 'required|in:completed,not_completed,in_progress',
            'member_remark' => 'nullable|string'
        ]);

        $assign->update([
            'status' => $data['status'],
            'member_remark' => $data['member_remark'],
            'is_submitted' => true
        ]);

        return back()->with('success', 'Submitted.');
    }

    //----------------------------------------------------------------------
    // SAVE FINAL DAY LOG
    //----------------------------------------------------------------------
    public function saveDayLog(Request $request, TeamDailySheet $sheet)
    {
        if ($sheet->sheet_date < Carbon::today()) {
            return back()->with('error', 'Past sheets locked.');
        }

        $taskMain = TaskMain::create([
            'team_id' => $sheet->team_id,
            'sheet_date' => $sheet->sheet_date,
            'leader_emp_id' => $sheet->leader_emp_id,
            'today_target' => $request->today_target,
            'day_remark' => $request->day_remark,
        ]);

        foreach ($sheet->assignments as $a) {
            EmployeeTaskMain::create([
                'task_main_id' => $taskMain->id,
                'member_emp_id' => $a->member_emp_id,
                'client_id' => $a->client_id,
                'task_description' => $a->task_description,
                'leader_remark' => $a->leader_remark,
                'status' => $a->status,
                'member_remark' => $a->member_remark,
            ]);
        }

        return back()->with('success', 'Day log saved.');
    }
}
