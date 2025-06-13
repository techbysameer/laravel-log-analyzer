<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogPattern extends Model
{
    protected $fillable = [
        'pattern_hash', 'category', 'pattern_description', 
        'common_message', 'occurrence_count', 'ai_suggestion', 'last_seen'
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];
}
