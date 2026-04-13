<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function jobRequest(): BelongsTo
    {
        return $this->belongsTo(JobRequest::class);
    }
}
