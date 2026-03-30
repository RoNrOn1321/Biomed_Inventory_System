<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_name',
        'department',
        'equipment_name',
        'issue_summary',
        'priority',
        'status',
        'requested_at',
        'accepted_at',
        'accepted_by',

        // New ERD columns
        'user_id',
        'date',
        'control_no',
        'location',
        'request_detail_id',
        'repair_id',
        'bio_service_docs_id',
        'request_complaints',
        'job_report',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function biomedicalServiceDoc()
    {
        return $this->belongsTo(BiomedicalServiceDoc::class, 'bio_service_docs_id');
    }

    public function requestDetail(): BelongsTo
    {
        return $this->belongsTo(RequestDetail::class, 'request_detail_id');
    }

    public function repair(): BelongsTo
    {
        return $this->belongsTo(Repair::class, 'repair_id');
    }
}