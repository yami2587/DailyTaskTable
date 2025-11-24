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
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class DailySheetController extends Controller
{
    // dashboard (protected by employee.auth middleware on routes)
    public function dashboard(Request $r)
    {
        $empId = session('emp_id');
        $employeeName = session('employee_name') ?? null;
        $date = $r->date ?? now()->toDateString();

        // always make clients available
        $clients = Client::orderBy('client_company_name')->get();

        // find if this emp is a leader in any team
        $teamLeader = TeamMember::where('emp_id', $empId)
            ->where('is_leader', true)
            ->first();

        // ---------- MEMBER VIEW ----------
        if (!$teamLeader) {
            $memberRecord = TeamMember::where('emp_id', $empId)->first();

            // no team membership at all
            if (!$memberRecord) {
                return view('dashboard.sheet', [
                    'empId' => $empId,
                    'employeeName' => $employeeName,
                    'date' => $date,
                    'sheet' => null,
                    'assignments' => collect(),
                    'members' => collect(),
                    'clients' => $clients,
                    'isLeader' => false
                ]);
            }

            // find sheet for the member's team
            $sheet = TeamDailySheet::where('team_id', $memberRecord->team_id)
                ->where('sheet_date', $date)
                ->with(['assignments'])
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

        // ---------- LEADER VIEW ----------
        $sheet = TeamDailySheet::where('team_id', $teamLeader->team_id)
            ->where('sheet_date', $date)
            ->with(['assignments'])
            ->first();

        // auto-create today's sheet if missing
        if (!$sheet) {
            $sheet = TeamDailySheet::create([
                'team_id' => $teamLeader->team_id,
                'leader_emp_id' => $empId,
                'sheet_date' => $date,
                'target_text' => ''
            ]);
            // reload with assignments relation
            $sheet->load('assignments');
        }

        // members of the team (with employee relation if defined)
        $members = TeamMember::with(['employee'])->where('team_id', $teamLeader->team_id)->get();

        // assignments for the sheet
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

    // create a sheet (called by leader)
    public function createSheet(Request $request)
    {
        $data = $request->validate([
            'team_id' => 'required|integer',
            'sheet_date' => 'required|date',
            'leader_emp_id' => 'required'
        ]);

        $sheet = TeamDailySheet::firstOrCreate(
            ['team_id' => $data['team_id'], 'sheet_date' => $data['sheet_date']],
            ['leader_emp_id' => $data['leader_emp_id'], 'target_text' => '']
        );

        return back()->with('success', 'Sheet created.');
    }

    // assign (leader) - creates a single assignment row
    public function assign(Request $request)
    {
        $data = $request->validate([
            'sheet_id' => 'required|integer',
            'member_emp_id' => 'required',
            'client_id' => 'nullable',
            'task_description' => 'nullable|string',
            'leader_remark' => 'nullable|string'
        ]);

        $sheet = TeamDailySheet::findOrFail($data['sheet_id']);

        // lock guard if sheet is older than today OR is_locked column exists and is true
        if ($sheet->sheet_date < Carbon::today() || (isset($sheet->is_locked) && $sheet->is_locked)) {
            return back()->with('error', 'Cannot modify past/locked sheets.');
        }

        TeamDailyAssignment::create([
            'sheet_id' => $data['sheet_id'],
            'member_emp_id' => $data['member_emp_id'],
            'client_id' => $data['client_id'] ?: null,
            'task_description' => $data['task_description'] ?: $data['leader_remark'] ?: null,
            'leader_remark' => $data['leader_remark'] ?: null,
            'status' => 'not_completed',
            'is_submitted' => false
        ]);

        return back()->with('success', 'Task assigned.');
    }

    // update assignment (leader)
    public function updateAssignment(Request $request, TeamDailyAssignment $assign)
    {
        $sheet = $assign->sheet;
        if ($sheet->sheet_date < Carbon::today() || (isset($sheet->is_locked) && $sheet->is_locked)) {
            return back()->with('error', 'Past/locked sheets cannot be modified.');
        }

        $data = $request->validate([
            'client_id' => 'nullable',
            'task_description' => 'nullable|string',
            'leader_remark' => 'nullable|string'
        ]);

        $assign->update($data);

        return back()->with('success', 'Assignment updated.');
    }

    public function deleteAssignment(TeamDailyAssignment $assign)
    {
        $sheet = $assign->sheet;
        if ($sheet->sheet_date < Carbon::today() || (isset($sheet->is_locked) && $sheet->is_locked)) {
            return back()->with('error', 'Past/locked sheets cannot be modified.');
        }

        $assign->delete();
        return back()->with('success', 'Assignment deleted.');
    }

    // member submits status + optional remark
    public function memberSubmit(Request $request, TeamDailyAssignment $assign)
    {
        $sheet = $assign->sheet;
        if ($sheet->sheet_date < Carbon::today() || (isset($sheet->is_locked) && $sheet->is_locked)) {
            return back()->with('error', 'Past/locked sheets cannot be updated.');
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['completed', 'not_completed', 'in_progress'])],
            'member_remark' => 'nullable|string'
        ]);

        $assign->update([
            'status' => $data['status'],
            'member_remark' => $data['member_remark'] ?? null,
            'is_submitted' => true
        ]);

        return back()->with('success', 'Submitted.');
    }

    // final save of the day's sheet (leader) -> creates snapshot entries and optionally locks the sheet
    public function saveDayLog(Request $request, TeamDailySheet $sheet)
    {
        if ($sheet->sheet_date < Carbon::today() || (isset($sheet->is_locked) && $sheet->is_locked)) {
            return back()->with('error', 'Past/locked sheets cannot be re-saved.');
        }

        $request->validate([
            'today_target' => 'nullable|string'
        ]);

        // create TaskMain snapshot
        $taskMain = TaskMain::create([
            'team_id' => $sheet->team_id,
            'sheet_date' => $sheet->sheet_date,
            'leader_emp_id' => $sheet->leader_emp_id,
            'today_target' => $request->input('today_target') ?? $sheet->target_text,
            'day_remark' => null
        ]);

        // copy assignments
        $assignments = $sheet->assignments()->get();
        foreach ($assignments as $a) {
            EmployeeTaskMain::create([
                'task_main_id' => $taskMain->id,
                'member_emp_id' => $a->member_emp_id,
                'client_id' => $a->client_id,
                'task_description' => $a->task_description,
                'leader_remark' => $a->leader_remark,
                'status' => $a->status ?? 'not_completed',
                'member_remark' => $a->member_remark ?? null
            ]);
        }

        // update sheet target_text and attempt to lock (only if column exists)
        $sheet->target_text = $request->input('today_target') ?? $sheet->target_text;
        if (array_key_exists('is_locked', $sheet->getAttributes())) {
            $sheet->is_locked = true;
        }
        $sheet->save();

        return back()->with('success', 'Day log saved and snapshot created.');
    }
}
