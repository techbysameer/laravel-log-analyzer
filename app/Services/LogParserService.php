<?php
namespace App\Services;

use App\Models\LogEntry;
use App\Models\LogPattern;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LogParserService
{
    private $logPath;
    private $patternService;

    public function __construct(PatternRecognitionService $patternService)
    {
        $this->logPath = storage_path('logs');
        $this->patternService = $patternService;
    }

    public function parseLatestLogs($hours = 24)
    {
        $logFiles = $this->getRecentLogFiles($hours);
        $totalParsed = 0;

        foreach ($logFiles as $file) {
            $parsed = $this->parseLogFile($file);
            $totalParsed += $parsed;
        }

        return $totalParsed;
    }

    private function getRecentLogFiles($hours)
    {
        $files = glob($this->logPath . '/*.log');
        $recentFiles = [];

        foreach ($files as $file) {
            if (filemtime($file) >= (time() - ($hours * 3600))) {
                $recentFiles[] = $file;
            }
        }

        return $recentFiles;
    }

    private function parseLogFile($filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) return 0;

        $parsedCount = 0;
        $currentEntry = null;

        while (($line = fgets($handle)) !== false) {
            if ($this->isNewLogEntry($line)) {
                // Save previous entry if exists
                if ($currentEntry) {
                    $this->saveLogEntry($currentEntry);
                    $parsedCount++;
                }
                
                // Start new entry
                $currentEntry = $this->parseLogLine($line);
            } else {
                // Continuation of previous entry (stack trace, etc.)
                if ($currentEntry) {
                    $currentEntry['stack_trace'] .= $line;
                }
            }
        }

        // Save final entry
        if ($currentEntry) {
            $this->saveLogEntry($currentEntry);
            $parsedCount++;
        }

        fclose($handle);
        return $parsedCount;
    }

    private function isNewLogEntry($line)
    {
        // Laravel log format: [2024-01-15 10:30:45] local.ERROR: ...
        return preg_match('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $line);
    }

    private function parseLogLine($line)
    {
        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*)/';
        
        if (preg_match($pattern, $line, $matches)) {
            $timestamp = Carbon::createFromFormat('Y-m-d H:i:s', $matches[1]);
            $environment = $matches[2];
            $level = strtolower($matches[3]);
            $message = $matches[4];

            // Extract file path and line number if present
            $filePath = null;
            $lineNumber = null;
            
            if (preg_match('/in (\/[^\s]+):(\d+)/', $message, $fileMatches)) {
                $filePath = $fileMatches[1];
                $lineNumber = (int) $fileMatches[2];
            }

            return [
                'log_timestamp' => $timestamp,
                'environment' => $environment,
                'level' => $level,
                'message' => $message,
                'file_path' => $filePath,
                'line_number' => $lineNumber,
                'stack_trace' => '',
                'hash' => $this->generateHash($level, $message, $filePath, $lineNumber)
            ];
        }

        return null;
    }

    private function generateHash($level, $message, $filePath, $lineNumber)
    {
        // Create hash based on error signature for deduplication
        $signature = $level . '|' . $this->normalizeMessage($message) . '|' . $filePath . '|' . $lineNumber;
        return md5($signature);
    }

    private function normalizeMessage($message)
    {
        // Remove dynamic parts like IDs, timestamps, etc.
        $normalized = preg_replace('/\b\d+\b/', 'ID', $message);
        $normalized = preg_replace('/\b[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\b/', 'UUID', $normalized);
        return $normalized;
    }

    private function saveLogEntry($entry)
    {
        $existing = LogEntry::where('hash', $entry['hash'])->first();

        if ($existing) {
            $existing->increment('occurrence_count');
            $existing->touch();
        } else {
            LogEntry::create($entry);
        }

        // Update patterns
        $this->patternService->updatePatterns($entry);
    }
}