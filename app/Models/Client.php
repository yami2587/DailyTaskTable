<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'm_client_tbl'; // your actual table name

    protected $primaryKey = 'client_id';

    public $timestamps = false; // your table likely has no created_at/updated_at

    protected $fillable = [
        'client_company_name'
    ];
}
