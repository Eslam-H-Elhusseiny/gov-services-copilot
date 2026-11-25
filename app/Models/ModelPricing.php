<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelPricing extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'model_pricing';

    protected $fillable = [
        'provider',
        'model',
        'input_price_per_1k',
        'output_price_per_1k',
        'currency',
        'effective_from',
        'effective_to',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'input_price_per_1k' => 'decimal:6',
            'output_price_per_1k' => 'decimal:6',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function llmCalls(): HasMany
    {
        return $this->hasMany(LlmCall::class, 'model_pricing_id');
    }
}
