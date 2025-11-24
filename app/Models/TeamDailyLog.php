<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamDailyLog extends Model
{
    protected $table = 'team_daily_log_tbl';

    protected $fillable = [
        'task_id',
        'team_id',
        'leader_emp_id',
        'member_emp_id',
        'log_date',
        'notes'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
