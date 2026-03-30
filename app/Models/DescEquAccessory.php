<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DescEquAccessory extends Model
{
    protected $fillable = [
        'job_request_id',
        'name',
        'brand',
        'model',
        'serial_number',
        'end_user',
    ];
}
