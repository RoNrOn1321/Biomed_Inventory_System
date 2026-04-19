<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'location', 'description', 'brand', 'model',
        'serial_number', 'tag_number', 'pm_date_done',
        'calibration', 'status',
        'pre_inspection_control_no', 'pre_inspectioned_at',
        'pre_inspection_form_data',
        'admin_approval', 'pending_action', 'admin_approval_notes',
        'admin_reviewed_at', 'admin_reviewed_by',
    ];

    protected $casts = [
        'pre_inspection_form_data' => 'array',
        'pre_inspectioned_at' => 'date',
    ];

    public function documents()
    {
        return $this->hasMany(EquipmentDocument::class);
    }

    public function calibrations()
    {
        return $this->hasMany(EquipmentCalibration::class)->orderBy('calibration_date', 'desc');
    }

    public function latestCalibration()
    {
        return $this->hasOne(EquipmentCalibration::class)->ofMany('calibration_date', 'max');
    }

    /**
     * Compute calibration status from the latest calibration date.
     * - No record or date >= 12 months ago: Uncalibrated
     * - 11–12 months ago: Due for Calibration
     * - < 11 months ago: Calibrated
     */
    public function computedCalibrationStatus(): string
    {
        $latest = $this->latestCalibration;
        if (!$latest || !$latest->calibration_date) {
            return 'Uncalibrated';
        }

        $date = $latest->calibration_date;
        $today = Carbon::today();
        $monthsAgo = $date->diffInMonths($today);

        if ($monthsAgo >= 12) {
            return 'Uncalibrated';
        }

        if ($monthsAgo >= 11) {
            return 'Due for Calibration';
        }

        return 'Calibrated';
    }
}
