<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmbeddingCache extends Model
{
    use HasFactory;

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
