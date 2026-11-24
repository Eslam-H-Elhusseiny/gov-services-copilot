<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseMessage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'case_messages';

    protected $fillable = [
        'case_id',
        'sender_type',
        'content',
        'citations',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'citations' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function serviceCase(): BelongsTo
    {
        return $this->belongsTo(ServiceCase::class, 'case_id');
    }
}
