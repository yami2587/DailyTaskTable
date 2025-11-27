<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TeamMember;
use App\Models\TeamDailySheet;
use App\Models\TeamDailyAssignment;
use App\Models\Client;
use App\Models\TaskMain;
use App\Models\EmployeeTaskMain;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Admin dashboard index.
     * Shows list of teams (sidebar). When a team is selected it shows:
     *  - team members
     *  - sheet for selected date (if exists)
     *  - assignments for that sheet grouped by member
     */
    public function index(Request $r)
    {
        // Simple admin guard: adapt to your auth if needed
        if (!session('is_admin')) {
            abort(403, 'Forbidden - admin only');
        }

        $date = $r->date ?? date('Y-m-d');

        // Try to load Teams via Team model if it exists, otherwise derive from TeamMember
        $teams = [];
        try {
            $teamModel = app()->make(\App\Models\Team::class);
            $teams = \App\Models\Team::orderBy('team_name')->get();
        } catch (\Throwable $e) {
            // fallback: distinct team ids from TeamMember
            $teamIds = TeamMember::select('team_id')->distinct()->pluck('team_id');
            $teams = collect();
            foreach ($teamIds as $tid) {
                $teams->push((object)[
                    'team_id' => $tid,
                    'team_name' => 'Team ' . $tid
                ]);
            }
        }

        // choose selected team id (query param or first)
        $selectedTeamId = $r->team_id ?? ($teams->first()->team_id ?? null);

        $clients = Client::orderBy('client_company_name')->get();

        $sheet = null;
        $members = collect();
        $assignments = collect();
        $summary = [
            'total_tasks' => 0,
            'completed' => 0,
            'in_progress' => 0,
            'not_completed' => 0
        ];

        if ($selectedTeamId) {
            // members
            $members = TeamMember::with('employee')
                ->where('team_id', $selectedTeamId)
                ->orderBy('is_leader', 'desc')
                ->get();

            // sheet for date
            $sheet = TeamDailySheet::where('team_id', $selectedTeamId)
                ->where('sheet_date', $date)
                ->first();

            if ($sheet) {
                // assignments for this sheet (group by member)
                $assignments = TeamDailyAssignment::where('sheet_id', $sheet->id)
                    ->orderBy('member_emp_id')
                    ->get();

                // summary counts
                $summary['total_tasks'] = $assignments->count();
                $summary['completed'] = $assignments->where('status', 'completed')->count();
                $summary['in_progress'] = $assignments->where('status', 'in_progress')->count();
                $summary['not_completed'] = $assignments->where('status', 'not_completed')->count();
            } else {
                // no sheet found for date
                $assignments = collect();
            }
        }

        return view('admin.dashboard', [
            'teams' => $teams,
            'selectedTeamId' => $selectedTeamId,
            'date' => $date,
            'sheet' => $sheet,
            'members' => $members,
            'assignments' => $assignments,
            'clients' => $clients,
            'summary' => $summary
        ]);
    }

    /**
     * Optional: convenience route that directly loads a specific team/day
     * Redirects to index with team_id & date query params.
     */
    public function team(Request $r, $teamId)
    {
        if (!session('is_admin')) {
            abort(403, 'Forbidden - admin only');
        }
        $date = $r->date ?? date('Y-m-d');
        return redirect()->route('admin.dashboard', ['team_id' => $teamId, 'date' => $date]);
    }
}
    