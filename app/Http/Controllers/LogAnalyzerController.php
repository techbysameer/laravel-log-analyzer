<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\LogEntry;
use App\Models\LogPattern;
use Illuminate\Http\Request;
use App\Services\OpenAIService;
use App\Services\LogParserService;
use App\Services\PatternRecognitionService;

class LogAnalyzerController extends Controller
{
    private $logParser;
    private $patternService;
    private $openAI;

    public function __construct(
        LogParserService $logParser,
        PatternRecognitionService $patternService,
        OpenAIService $openAI
    ) {
        $this->logParser = $logParser;
        $this->patternService = $patternService;
        $this->openAI = $openAI;
    }

    public function dashboard()
    {
        $stats = [
            'total_errors' => LogEntry::errors()->count(),
            'recent_errors' => LogEntry::errors()->recent(24)->count(),
            'total_patterns' => LogPattern::count(),
            'critical_patterns' => LogPattern::where('occurrence_count', '>', 10)->count()
        ];

        $recentErrors = LogEntry::errors()
            ->recent(24)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $topPatterns = $this->patternService->getTopPatterns(5);

        $errorTrends = $this->getErrorTrends();

        return view('log-analyzer.dashboard', compact(
            'stats', 'recentErrors', 'topPatterns', 'errorTrends'
        ));
    }

    public function parseLogs(Request $request)
    {
        $hours = $request->input('hours', 24);
        $parsed = $this->logParser->parseLatestLogs($hours);

        return response()->json([
            'success' => true,
            'parsed_entries' => $parsed,
            'message' => "Parsed {$parsed} log entries from the last {$hours} hours"
        ]);
    }

    public function analyzeError(Request $request, $id)
    {
        $logEntry = LogEntry::findOrFail($id);
        
        $analysis = $this->openAI->analyzeError(
            $logEntry->message,
            $logEntry->stack_trace,
            $logEntry->context
        );

        return response()->json([
            'analysis' => $analysis
        ]);
    }

    public function generatePatternSuggestion($id)
    {
        $pattern = LogPattern::findOrFail($id);
        
        if (!$pattern->ai_suggestion) {
            $suggestion = $this->openAI->generatePatternSuggestion($pattern);
            
            if ($suggestion) {
                $pattern->update(['ai_suggestion' => $suggestion]);
            }
        }

        return response()->json([
            'suggestion' => $pattern->ai_suggestion
        ]);
    }

    private function getErrorTrends()
    {
        $trends = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = LogEntry::errors()
                ->whereDate('created_at', $date)
                ->count();
                
            $trends[] = [
                'date' => $date->format('M j'),
                'count' => $count
            ];
        }

        return $trends;
    }
}
