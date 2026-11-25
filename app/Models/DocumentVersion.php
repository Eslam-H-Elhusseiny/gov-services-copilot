<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'document_versions';

    protected $fillable = [
        'document_id',
        'version_label',
        'content_hash',
        'file_path',
        'uploaded_by',
        'created_at',
    ];

    protected $guarded = [
        'status',
        'failure_details',
        'chunk_count',
        'indexed_at',
    ];

    protected function casts(): array
    {
        return [
            'chunk_count' => 'integer',
            'indexed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class, 'document_version_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
