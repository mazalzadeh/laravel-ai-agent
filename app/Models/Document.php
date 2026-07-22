<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DocumentChunk;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Document extends Model
{
    use HasFactory;

    protected $fillable = ['content', 'embedding'];

    protected $casts = ['embedding' => 'array'];

    public function chunks()
    {
        return $this->hasMany(DocumentChunk::class);
    }
}
