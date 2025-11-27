<?php

namespace App\Models;
use SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{ use SoftDeletes;
    protected $table = 'team_members_tbl';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'team_id',
        'emp_id',
        'is_leader'
    ];

    // RELATION: Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    // RELATION: Team
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }
    
}
