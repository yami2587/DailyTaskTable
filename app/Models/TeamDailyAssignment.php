<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamDailyAssignment extends Model
{
    protected $table = 'team_daily_assignment_tbl';

    protected $fillable = [
        'sheet_id',
        'member_emp_id',
        'member_name',           // backup name
        'member_designation',
        'client_id',
        'task_description',
        'leader_remark',
        'member_remark',
        'status',
        'is_submitted',
        'is_completed',
        'completed_at'
    ];

    protected $casts = [
        'is_submitted' => 'boolean',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime'
    ];

    public function sheet()
    {
        return $this->belongsTo(TeamDailySheet::class, 'sheet_id');
    }
    public function employee()
{
    return $this->belongsTo(Employee::class, 'member_emp_id', 'emp_id');
}


    public function client()
    {
        return $this->belongsTo(\App\Models\Client::class, 'client_id', 'client_id');
    }
}
