<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCase extends Model
{
    use HasFactory;

    protected $table = 'cases';

    protected $fillable = [
        'user_id',
        'title',
        'situation',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CaseMessage::class, 'case_id')->orderBy('created_at', 'asc');
    }

    public function workflowRuns(): HasMany
    {
        return $this->hasMany(WorkflowRun::class, 'case_id')->orderBy('created_at', 'desc');
    }

    public function latestWorkflowRun()
    {
        return $this->hasOne(WorkflowRun::class, 'case_id')->latestOfMany();
    }
}
