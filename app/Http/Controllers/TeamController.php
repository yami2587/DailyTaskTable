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
        // redirect back to admin dashboard (if that is the primary place), else team.index
        if(request()->has('from_admin')) {
            return redirect()->route('admin.dashboard')->with('success', 'Team created.');
        }
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
        // if request originates from admin dashboard we redirect back there with selected team
        if($request->get('from_admin')){
            return redirect()->route('admin.dashboard', ['team_id' => $team->id])->with('success','Team updated.');
        }

        return redirect()->route('team.index')->with('success', 'Team updated.');
    }

    public function destroy(Team $team)
    {
        // Optional: check for associations before delete (safe deletion)
        $team->delete();
        // if delete triggered from admin dashboard, redirect back there
        if(request()->get('from_admin')){
            return redirect()->route('admin.dashboard')->with('success','Team deleted.');
        }
        return redirect()->route('team.index')->with('success', 'Team deleted.');
    }

    /**
     * Members management UI for a single team.
     * If request expects JSON, return structured members + employees for AJAX.
     * Otherwise return full blade (legacy).
     */
    public function members(Request $request, Team $team)
    {
        // employees list from existing employee_tbl
        $employees = DB::table('employee_tbl')->select('emp_id', 'emp_name')->orderBy('emp_name')->get();

        // current members (with employee relation if available)
        $members = TeamMember::where('team_id', $team->id)->get();

        // If client requests JSON (AJAX fetching), return structured JSON
        if ($request->wantsJson() || $request->ajax()) {
            // prepare member list with friendly name
            $membersJson = $members->map(function($m){
                $emp = DB::table('employee_tbl')->where('emp_id', $m->emp_id)->first();
                return [
                    'id' => $m->id,
                    'emp_id' => $m->emp_id,
                    'employee_name' => $emp ? $emp->emp_name : null,
                    'is_leader' => (bool)$m->is_leader,
                ];
            })->values();

            return response()->json([
                'team' => [
                    'id' => $team->id,
                    'team_name' => $team->team_name,
                    'description' => $team->description,
                ],
                'employees' => $employees,
                'members' => $membersJson,
            ]);
        }

        // Legacy: return blade (if you still use the separate team.members view elsewhere)
        return view('team.members', compact('team', 'employees', 'members'));
    }

    /**
     * addMember: POST /team/{team}/members
     * Redirects back to admin.dashboard with team context so dashboard remains active.
     */
    public function addMember(Request $request, Team $team)
    {
        $data = $request->validate([
            'emp_id' => 'required|string',
            'is_leader' => 'nullable|boolean',
        ]);

        if (!empty($data['is_leader'])) {
            TeamMember::where('team_id', $team->id)->update(['is_leader' => false]);
        }

        TeamMember::create([
            'team_id' => $team->id,
            'emp_id' => $data['emp_id'],
            'is_leader' => !empty($data['is_leader']),
        ]);

        // On success, redirect back to admin dashboard keeping the team selected
        return redirect()->route('admin.dashboard', ['team_id' => $team->id])->with('success', 'Member added.');
    }

    /**
     * removeMember: DELETE /team/{team}/members/{member}
     * Redirects back to admin.dashboard to keep user on same page.
     */
    public function removeMember(Request $request, Team $team, $memberId)
    {
        $m = TeamMember::where('team_id', $team->id)->where('id', $memberId)->first();
        if ($m) $m->delete();

        return redirect()->route('admin.dashboard', ['team_id' => $team->id])->with('success', 'Member removed.');
    }
}
