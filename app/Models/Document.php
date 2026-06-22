<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DocumentChunk;

class Document extends Model
{
    protected $fillable = ['content', 'embedding'];

    protected $casts = ['embedding' => 'array'];

    public function chunks()
    {
        return $this->hasMany(DocumentChunk::class);
    }
}
