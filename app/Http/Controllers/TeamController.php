<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    /**
     * ALWAYS go to admin.dashboard.
     */
    public function index()
    {
        return redirect()->route('admin.dashboard');
    }

    public function create()
    {
        return redirect()->route('admin.dashboard');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'team_name' => 'required|string|max:191',
            'description' => 'nullable|string',
        ]);

        $team = Team::create($data);

        return redirect()
            ->route('admin.dashboard', ['team_id' => $team->id])
            ->with('success', 'Team created successfully.');
    }

    public function edit(Team $team)
    {
        // We do not render any edit view.
        // Dashboard handles editing via modal/same-page component.
        return redirect()
            ->route('admin.dashboard', ['team_id' => $team->id]);
    }

    public function update(Request $request, Team $team)
    {
        $data = $request->validate([
            'team_name' => 'required|string|max:191',
            'description' => 'nullable|string',
        ]);

        $team->update($data);

        return redirect()
            ->route('admin.dashboard', ['team_id' => $team->id])
            ->with('success', 'Team updated successfully.');
    }

    public function destroy(Team $team)
    {
        $teamId = $team->id;
        $team->delete();

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Team deleted successfully.");
    }

    /**
     * MEMBERS SCREEN — but no blade rendering.
     *
     * Admin dashboard needs members + employees data (AJAX).
     */
    public function members(Request $request, Team $team)
    {
        // Full employee list
        $employees = DB::table('employee_tbl')
            ->select('emp_id', 'emp_name')
            ->orderBy('emp_name')
            ->get();

        // Team members
        $members = TeamMember::where('team_id', $team->id)->get();

        // AJAX JSON response for dashboard
        return response()->json([
            'team' => [
                'id' => $team->id,
                'team_name' => $team->team_name,
                'description' => $team->description,
            ],
            'employees' => $employees,
            'members' => $members->map(function ($m) {
                $emp = DB::table('employee_tbl')->where('emp_id', $m->emp_id)->first();
                return [
                    'id' => $m->id,
                    'emp_id' => $m->emp_id,
                    'employee_name' => $emp ? $emp->emp_name : null,
                    'is_leader' => (bool)$m->is_leader,
                ];
            }),
        ]);
    }

    /**
     * ADD MEMBER — always redirect to dashboard.
     */
    public function addMember(Request $request, Team $team)
    {
        $data = $request->validate([
            'emp_id' => 'required|string',
            'is_leader' => 'nullable|boolean',
        ]);

        // If leader is selected — reset others
        if (!empty($data['is_leader'])) {
            TeamMember::where('team_id', $team->id)->update(['is_leader' => false]);
        }

        TeamMember::create([
            'team_id' => $team->id,
            'emp_id' => $data['emp_id'],
            'is_leader' => !empty($data['is_leader']),
        ]);

        return redirect()
            ->route('admin.dashboard', ['team_id' => $team->id])
            ->with('success', 'Member added successfully.');
    }

    /**
     * REMOVE MEMBER — always redirect to dashboard.
     */
    public function removeMember(Request $request, Team $team, $memberId)
    {
        TeamMember::where('team_id', $team->id)->where('id', $memberId)->delete();

        return redirect()
            ->route('admin.dashboard', ['team_id' => $team->id])
            ->with('success', 'Member removed successfully.');
    }
}
