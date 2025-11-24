<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employee_tbl';
    protected $primaryKey = 'emp_id';
    public $timestamps = false;

    protected $fillable = [
        'emp_name'
    ];
}
