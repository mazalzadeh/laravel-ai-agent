<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmbeddingCache extends Model
{
    protected $fillable = [
        'hash',
        'text',
        'embedding',
        'model'
    ];

    protected $casts = [
        'embedding' => 'array',
    ];
}
