<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::orderBy('id', 'desc')->paginate(15);
        return view('team.index', compact('teams'));
    }

    public function create()
    {
        return view('team.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'team_name' => 'required|string|max:191',
            'description' => 'nullable|string',
        ]);

        Team::create($data);
        return redirect()->route('team.index')->with('success', 'Team created.');
    }

    public function edit(Team $team)
    {
        return view('team.edit', compact('team'));
    }

    public function update(Request $request, Team $team)
    {
        $data = $request->validate([
            'team_name' => 'required|string|max:191',
            'description' => 'nullable|string',
        ]);

        $team->update($data);
        return redirect()->route('team.index')->with('success', 'Team updated.');
    }

    public function destroy(Team $team)
    {
        $team->delete();
        return redirect()->route('team.index')->with('success', 'Team deleted.');
    }

    // Members management UI for a single team
    public function members(Team $team)
    {
        // employees list from existing employee_tbl
        $employees = DB::table('employee_tbl')->select('emp_id', 'emp_name')->orderBy('emp_name')->get();

        // current members
        $members = TeamMember::where('team_id', $team->id)->get();

        return view('team.members', compact('team', 'employees', 'members'));
    }

    // add a member to the team
    public function addMember(Request $request, Team $team)
    {
        $data = $request->validate([
            'emp_id' => 'required|string',
            'is_leader' => 'nullable|boolean',
        ]);

        // if is_leader then clear other leaders
        if (!empty($data['is_leader'])) {
            TeamMember::where('team_id', $team->id)->update(['is_leader' => false]);
        }

        TeamMember::create([
            'team_id' => $team->id,
            'emp_id' => $data['emp_id'],
            'is_leader' => !empty($data['is_leader']),
        ]);

        return back()->with('success', 'Member added.');
    }

    public function removeMember(Team $team, $memberId)
    {
        $m = TeamMember::where('team_id', $team->id)->where('id', $memberId)->first();
        if ($m) $m->delete();
        return back()->with('success', 'Member removed.');
    }
}
