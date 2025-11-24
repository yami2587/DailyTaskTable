<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $table = 'task_tbl';

    protected $fillable = [
        'task_title',
        'task_description',

        'client_id',
        'assigned_team_id',
        'assigned_member_id',

        'task_type',
        'status',
        'due_date'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'assigned_team_id');
    }

    public function targets()
    {
        return $this->hasMany(TodayTarget::class, 'task_id');
    }

    public function logs()
    {
        return $this->hasMany(TeamDailyLog::class, 'task_id');
    }
}
