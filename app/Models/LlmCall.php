<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlmCall extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'llm_calls';

    protected $fillable = [
        'workflow_run_id',
        'user_id',
        'agent',
        'provider',
        'model',
        'input_tokens',
        'output_tokens',
        'input_price_per_1k',
        'output_price_per_1k',
        'currency',
        'model_pricing_id',
        'cost',
        'overage_tokens',
        'duration_ms',
        'status',
        'error_message',
        'created_at',
    ];

    protected $guarded = [
        'reconciled',
    ];

    protected function casts(): array
    {
        return [
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'overage_tokens' => 'integer',
            'duration_ms' => 'integer',
            'input_price_per_1k' => 'decimal:6',
            'output_price_per_1k' => 'decimal:6',
            'cost' => 'decimal:6',
            'reconciled' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function workflowRun(): BelongsTo
    {
        return $this->belongsTo(WorkflowRun::class, 'workflow_run_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function modelPricing(): BelongsTo
    {
        return $this->belongsTo(ModelPricing::class, 'model_pricing_id');
    }
}
