<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TodayTarget extends Model
{
    protected $table = 'today_targets_tbl';

    protected $fillable = [
        'task_id',
        'title',
        'remark',
        'is_done',
        'target_date'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
