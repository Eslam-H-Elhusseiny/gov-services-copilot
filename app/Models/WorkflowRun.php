<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowRun extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'workflow_runs';

    protected $fillable = [
        'case_id',
        'run_uuid',
        'correlation_id',
        'run_type',
        'current_state',
        'draft_response',
        'final_response',
        'estimated_tokens',
        'reserved_tokens',
        'cancellation_requested_at',
        'started_at',
        'completed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancellation_requested_at' => 'datetime',
        ];
    }

    public function serviceCase(): BelongsTo
    {
        return $this->belongsTo(ServiceCase::class, 'case_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'workflow_run_id');
    }

    public function latestApproval()
    {
        return $this->hasOne(Approval::class, 'workflow_run_id')->latestOfMany();
    }
}
