<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $table = 'admin';   // your table
    protected $primaryKey = 'userid';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'password',
        'email',
        'role'
    ];
}
