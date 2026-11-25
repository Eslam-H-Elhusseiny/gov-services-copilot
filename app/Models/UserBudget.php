<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBudget extends Model
{
    use HasFactory;

    protected $table = 'user_budgets';

    protected $fillable = [
        'user_id',
        'period_start',
        'period_end',
        'budget_limit',
        'consumed_tokens',
        'reserved_tokens',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'budget_limit' => 'integer',
            'consumed_tokens' => 'integer',
            'reserved_tokens' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAvailableTokensAttribute(): int
    {
        return max(0, $this->budget_limit - $this->consumed_tokens - $this->reserved_tokens);
    }

    public function getPercentageUsedAttribute(): float
    {
        if ($this->budget_limit <= 0) {
            return 0;
        }

        return min(100, round(($this->consumed_tokens / $this->budget_limit) * 100, 1));
    }
}
