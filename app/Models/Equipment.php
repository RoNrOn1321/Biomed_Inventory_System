<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'location', 'description', 'brand', 'model',
        'serial_number', 'tag_number', 'pm_date_done',
        'calibration', 'status',
    ];
}
