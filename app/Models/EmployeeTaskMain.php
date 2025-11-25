<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeTaskMain extends Model
{
    protected $table = 'employee_task_main_tbl';
    protected $fillable = [
        'task_main_id',
        'member_emp_id',
        'client_id',
        'task_description',
        'leader_remark',
        'status',
        'member_remark'
    ];

    
    public function taskMain()
    {
        return $this->belongsTo(TaskMain::class, 'task_main_id');
    }
}
