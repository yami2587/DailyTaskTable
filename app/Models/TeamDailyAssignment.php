<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamDailyAssignment extends Model
{
    protected $table = 'team_daily_assignment_tbl';

    protected $fillable = [
        'sheet_id',
        'member_emp_id',
        'client_id',
        'task_description',
        'leader_remark',
        'member_update',
        'is_completed',
        'completed_at'
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime'
    ];

    public function sheet()
    {
        return $this->belongsTo(TeamDailySheet::class, 'sheet_id');
    }
}
