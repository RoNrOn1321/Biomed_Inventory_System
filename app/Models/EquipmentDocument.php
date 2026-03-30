<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentDocument extends Model
{
    protected $fillable = ['equipment_id', 'file_name', 'file_path'];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
