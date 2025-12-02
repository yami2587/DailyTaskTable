<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamDailySheet;
use App\Models\TeamDailyAssignment;
use App\Models\Client;
use App\Models\Employee;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function index(Request $r)
    {
        if (!session('is_admin')) {
            abort(403, 'Forbidden – Admin only');
        }

        $date = $r->date ?? now()->toDateString();

        /* ----------------------------------------------------
         * 1. Fetch all teams (Eloquent)
         * ---------------------------------------------------- */
        $teams = Team::orderBy('team_name')
            ->select('id', 'team_name')
            ->get();

        /* ----------------------------------------------------
         * 2. If NO TEAMS exist — dashboard must load cleanly
         * ---------------------------------------------------- */
        if ($teams->isEmpty()) {

            return view('admin.dashboard', [
                'teams'          => collect(), // empty
                'selectedTeamId' => null,
                'date'           => $date,
                'sheet'          => null,
                'members'        => collect(),
                'assignments'    => collect(),
                'clients'        => Client::orderBy('client_company_name')->get(),
                'employees'      => Employee::orderBy('emp_name')->get(),
                'summary'        => [
                    'total_tasks'   => 0,
                    'completed'     => 0,
                    'in_progress'   => 0,
                    'not_completed' => 0
                ]
            ]);
        }

        /*
         * 3. Selected team (fallback to first team)
        */
        $selectedTeamId = $r->team_id ?? $teams->first()->id;

        /* ----------------------------------------------------
         * 4. Members of this team
         * ---------------------------------------------------- */
        $members = TeamMember::with('employee')
            ->where('team_id', $selectedTeamId)
            ->orderByDesc('is_leader')
            ->get();

        /* ----------------------------------------------------
         * 5. Today’s sheet for that team
         * ---------------------------------------------------- */
        $sheet = TeamDailySheet::where('team_id', $selectedTeamId)
            ->where('sheet_date', $date)
            ->first();

        $assignments = collect();
        $summary = [
            'total_tasks'   => 0,
            'completed'     => 0,
            'in_progress'   => 0,
            'not_completed' => 0
        ];

        /* ----------------------------------------------------
         * 6. If sheet exists → load assignments
         * ---------------------------------------------------- */
        if ($sheet) {
            $assignments = TeamDailyAssignment::where('sheet_id', $sheet->id)
                ->orderBy('member_emp_id')
                ->get();

            $summary = [
                'total_tasks'   => $assignments->count(),
                'completed'     => $assignments->where('status', 'completed')->count(),
                'in_progress'   => $assignments->where('status', 'in_progress')->count(),
                'not_completed' => $assignments->where('status', 'not_completed')->count()
            ];
        }

        /* ----------------------------------------------------
         * 7. Load clients & employees for modals
         * ---------------------------------------------------- */
        $clients   = Client::orderBy('client_company_name')->get();
        $employees = Employee::orderBy('emp_name')->get();

        /* ----------------------------------------------------
         * 8. Return safe dashboard view
         * ---------------------------------------------------- */
        return view('admin.dashboard', [
            'teams'          => $teams,
            'selectedTeamId' => $selectedTeamId,
            'date'           => $date,
            'sheet'          => $sheet,
            'members'        => $members,
            'assignments'    => $assignments,
            'clients'        => $clients,
            'employees'      => $employees,
            'summary'        => $summary
        ]);
    }
}
