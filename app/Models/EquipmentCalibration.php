<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentCalibration extends Model
{
    protected $fillable = ['equipment_id', 'file_name', 'file_path', 'calibration_date'];

    protected $casts = [
        'calibration_date' => 'date',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
