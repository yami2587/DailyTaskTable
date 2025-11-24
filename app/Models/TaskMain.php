<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskMain extends Model
{
    protected $table = 'task_main_tbl';
    protected $fillable = ['team_id', 'sheet_date', 'leader_emp_id', 'today_target', 'day_remark'];
    protected $casts = ['sheet_date' => 'date'];
    public function employeeTasks()
    {
        return $this->hasMany(EmployeeTaskMain::class, 'task_main_id');
    }
}
