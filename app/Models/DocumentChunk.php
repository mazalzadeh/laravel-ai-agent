<?php

namespace App\Models;

use App\Models\Document;
use Illuminate\Database\Eloquent\Model;

class DocumentChunk extends Model
{
    protected $fillable = [
        'document_id',
        'chunk_index',
        'content',
        'embedding'
    ];

    protected $casts = ['embedding' => 'array'];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
