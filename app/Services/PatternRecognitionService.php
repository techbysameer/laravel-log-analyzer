<?php
namespace App\Services;

use App\Models\LogPattern;
use Illuminate\Support\Str;

class PatternRecognitionService
{
    private $categories = [
        'database' => ['connection', 'query', 'duplicate', 'constraint', 'mysql', 'postgresql'],
        'validation' => ['validation', 'required', 'invalid', 'format'],
        'authentication' => ['auth', 'login', 'token', 'unauthorized', 'forbidden'],
        'file_system' => ['file', 'directory', 'permission', 'not found', 'upload'],
        'network' => ['curl', 'timeout', 'connection refused', 'dns'],
        'memory' => ['memory', 'limit', 'exhausted', 'allocation'],
        'general' => []
    ];

    public function updatePatterns($logEntry)
    {
        $category = $this->categorizeError($logEntry['message']);
        $patternHash = $this->generatePatternHash($logEntry['message'], $category);
        
        $pattern = LogPattern::where('pattern_hash', $patternHash)->first();

        if ($pattern) {
            $pattern->increment('occurrence_count');
            $pattern->update(['last_seen' => now()]);
        } else {
            LogPattern::create([
                'pattern_hash' => $patternHash,
                'category' => $category,
                'pattern_description' => $this->generatePatternDescription($logEntry['message']),
                'common_message' => $this->normalizeMessage($logEntry['message']),
                'occurrence_count' => 1,
                'last_seen' => now()
            ]);
        }
    }

    private function categorizeError($message)
    {
        $messageLower = strtolower($message);

        foreach ($this->categories as $category => $keywords) {
            if ($category === 'general') continue;
            
            foreach ($keywords as $keyword) {
                if (strpos($messageLower, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return 'general';
    }

    private function generatePatternHash($message, $category)
    {
        $normalized = $this->normalizeMessage($message);
        return md5($category . '|' . $normalized);
    }

    private function generatePatternDescription($message)
    {
        // Extract key components for pattern description
        if (preg_match('/Class \'([^\']+)\' not found/', $message, $matches)) {
            return "Missing class: {$matches[1]}";
        }

        if (preg_match('/Call to undefined method ([^(]+)/', $message, $matches)) {
            return "Undefined method: {$matches[1]}";
        }

        if (preg_match('/SQLSTATE\[(\d+)\]/', $message, $matches)) {
            return "Database error: SQLSTATE {$matches[1]}";
        }

        // Fallback: use first 100 chars
        return Str::limit($message, 100);
    }

    private function normalizeMessage($message)
    {
        // Same normalization as in LogParserService
        $normalized = preg_replace('/\b\d+\b/', 'ID', $message);
        $normalized = preg_replace('/\b[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\b/', 'UUID', $normalized);
        return $normalized;
    }

    public function getTopPatterns($limit = 10)
    {
        return LogPattern::orderBy('occurrence_count', 'desc')
            ->orderBy('last_seen', 'desc')
            ->limit($limit)
            ->get();
    }
}