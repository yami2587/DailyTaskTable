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
    public function dashboard(Request $r)
    {
        $empId = session('emp_id');
        $employeeName = session('employee_name') ?? null;
        $date = $r->date ?? date('Y-m-d');

        // is leader?
        $teamLeader = TeamMember::where('emp_id', $empId)->where('is_leader', true)->first();

        // clients list for selects
        $clients = Client::orderBy('client_company_name')->get();

        // MEMBER FLOW
        if (!$teamLeader) {
            $memberTeam = TeamMember::where('emp_id', $empId)->first();
            if (!$memberTeam) {
                return view('dashboard.sheet', [
                    'empId' => $empId,
                    'employeeName' => $employeeName,
                    'date' => $date,
                    'sheet' => null,
                    'assignments' => collect(),
                    'members' => collect(),
                    'clients' => $clients,
                    'isLeader' => false,
                    'isFinalized' => false
                ]);
            }

            $sheet = TeamDailySheet::where('team_id', $memberTeam->team_id)
                ->where('sheet_date', $date)
                ->first();

            // assignments for this member for that sheet
            $assignments = TeamDailyAssignment::where('member_emp_id', $empId)
                ->where('sheet_id', $sheet->id ?? 0)
                ->get();

            // determine finalized: TaskMain exists for this team & date
            $isFinalized = $sheet
                ? TaskMain::where('team_id', $sheet->team_id)->where('sheet_date', $sheet->sheet_date)->exists()
                : false;

            return view('dashboard.sheet', [
                'empId' => $empId,
                'employeeName' => $employeeName,
                'date' => $date,
                'sheet' => $sheet,
                'assignments' => $assignments,
                'members' => collect(),
                'clients' => $clients,
                'isLeader' => false,
                'isFinalized' => $isFinalized
            ]);
        }

        // LEADER FLOW
        $sheet = TeamDailySheet::where('team_id', $teamLeader->team_id)
            ->where('sheet_date', $date)
            ->first();

        if (!$sheet) {
            $sheet = TeamDailySheet::create([
                'team_id' => $teamLeader->team_id,
                'leader_emp_id' => $empId,
                'sheet_date' => $date,
                'target_text' => ''
            ]);
        }

        // load members with employee relation
        $members = TeamMember::with('employee')->where('team_id', $teamLeader->team_id)->get();

        // assignments for this sheet
        $assignments = TeamDailyAssignment::where('sheet_id', $sheet->id)->get();

        // finalized check (TaskMain exists)
        $isFinalized = TaskMain::where('team_id', $sheet->team_id)
            ->where('sheet_date', $sheet->sheet_date)
            ->exists();

        return view('dashboard.sheet', [
            'empId' => $empId,
            'employeeName' => $employeeName,
            'date' => $date,
            'sheet' => $sheet,
            'members' => $members,
            'assignments' => $assignments,
            'clients' => $clients,
            'isLeader' => true,
            'isFinalized' => $isFinalized
        ]);
    }

    public function createSheet(Request $request)
    {
        $data = $request->validate([
            'team_id' => 'required|integer',
            'sheet_date' => 'required|date',
            'leader_emp_id' => 'required|string',
        ]);

        $sheet = TeamDailySheet::firstOrCreate(
            ['team_id' => $data['team_id'], 'sheet_date' => $data['sheet_date']],
            ['leader_emp_id' => $data['leader_emp_id'], 'target_text' => '']
        );

        return back()->with('success', 'Sheet created.');
    }

    // assign: allows fetch POST. returns JSON for AJAX, or redirect for normal requests
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
            return $request->wantsJson() ? response()->json(['error' => 'Past sheets locked'], 422) : back()->with('error', 'Past sheets locked.');
        }

        $assign = TeamDailyAssignment::create([
            'sheet_id' => $data['sheet_id'],
            'member_emp_id' => $data['member_emp_id'],
            'client_id' => $data['client_id'] ?? null,
            'task_description' => $data['task_description'] ?? ($data['leader_remark'] ?? null),
            'leader_remark' => $data['leader_remark'] ?? null,
            'status' => 'not_completed',
            'member_remark' => null,
            'is_submitted' => false
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'id' => $assign->id], 200);
        }

        return back()->with('success', 'Assigned.');
    }

    public function updateAssignment(Request $request, TeamDailyAssignment $assign)
    {
        $sheet = $assign->sheet;
        if ($sheet->sheet_date < Carbon::today()) {
            return back()->with('error', 'Past sheets locked.');
        }

        $assign->update($request->only(['client_id', 'task_description', 'leader_remark']));
        return back()->with('success', 'Updated.');
    }

    public function deleteAssignment(TeamDailyAssignment $assign)
    {
        $sheet = $assign->sheet;
        if ($sheet->sheet_date < Carbon::today()) {
            return back()->with('error', 'Past sheets locked.');
        }
        $assign->delete();
        return back()->with('success', 'Deleted.');
    }

    // Member submit - supports AJAX; returns JSON when AJAX to let client convert UI to static.
    public function memberSubmit(Request $request, TeamDailyAssignment $assign)
    {
        $sheet = $assign->sheet;
        if ($sheet->sheet_date < Carbon::today()) {
            return $request->wantsJson() ? response()->json(['error' => 'Past sheets locked'], 422) : back()->with('error', 'Past sheets locked.');
        }

        $data = $request->validate([
            'status' => 'required|in:completed,not_completed,in_progress',
            'member_remark' => 'nullable|string'
        ]);

        $assign->update([
            'status' => $data['status'],
            'member_remark' => $data['member_remark'] ?? null,
            'is_submitted' => true
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true], 200);
        }

        return back()->with('success', 'Submitted.');
    }

    // Save final day log: create TaskMain and EmployeeTaskMain. After this TaskMain exists => finalized.
    public function saveDayLog(Request $request, TeamDailySheet $sheet)
    {
        if ($sheet->sheet_date < Carbon::today()) {
            return $request->wantsJson() ? response()->json(['error' => 'Past sheets locked'], 422) : back()->with('error', 'Past sheets locked.');
        }

        $taskMain = TaskMain::create([
            'team_id' => $sheet->team_id,
            'sheet_date' => $sheet->sheet_date,
            'leader_emp_id' => $sheet->leader_emp_id,
            'today_target' => $request->input('today_target') ?? $sheet->target_text,
            'day_remark' => null
        ]);

        // copy assignments snapshot
        foreach ($sheet->assignments as $a) {
            EmployeeTaskMain::create([
                'task_main_id' => $taskMain->id,
                'member_emp_id' => $a->member_emp_id,
                'client_id' => $a->client_id,
                'task_description' => $a->task_description,
                'leader_remark' => $a->leader_remark,
                'status' => $a->status ?? 'not_completed',
                'member_remark' => $a->member_remark ?? null,
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true], 200);
        }

        return back()->with('success', 'Day log saved.');
    }
}
