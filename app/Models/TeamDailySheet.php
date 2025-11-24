<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TeamDailySheet extends Model
{
    protected $table = 'team_daily_sheet_tbl';

    protected $fillable = [
        'team_id',
        'leader_emp_id',
        'sheet_date',
        'target_text',
        'day_remark'
    ];

    protected $casts = [
        'sheet_date' => 'date:Y-m-d',   // important
    ];

    public function getSheetDateAttribute($value)
    {
        return Carbon::parse($value);
    }

    public function assignments()
    {
        return $this->hasMany(TeamDailyAssignment::class, 'sheet_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
