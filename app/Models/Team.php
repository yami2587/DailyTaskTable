<?php

namespace App\Models;
use SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{ 

    protected $table = 'team_tbl';
use SoftDeletes;
    protected $fillable = [
        'team_name',
        'description'
    ];

    public function members()
    {
        return $this->hasMany(TeamMember::class, 'team_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_team_id');
    }

    public function dailyLogs()
    {
        return $this->hasMany(TeamDailyLog::class, 'team_id');
    }
}
