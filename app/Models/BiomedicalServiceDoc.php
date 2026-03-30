<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiomedicalServiceDoc extends Model
{
    protected $fillable = [
        'receive_by',
        'performed_by',
        'date_receive',
        'date_performed',
        'estimated_no_days',
        'technician_date_received',
        'date_started',
        'date_finished',
        'date_returned',
        'receive_by_end_user',
        'check_verify_by_id',
        'remarks',
    ];
}
