<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'level', 'log_timestamp', 'environment', 'message', 
        'context', 'stack_trace', 'file_path', 'line_number', 
        'hash', 'occurrence_count'
    ];

    protected $casts = [
        'log_timestamp' => 'datetime',
        'context' => 'array',
    ];

    public function scopeErrors($query)
    {
        return $query->where('level', 'error');
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
