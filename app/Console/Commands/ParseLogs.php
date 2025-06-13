<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LogParserService;

class ParseLogs extends Command
{
    protected $signature = 'logs:parse {--hours=24 : Hours to look back}';
    protected $description = 'Parse Laravel logs and extract patterns';

    private $logParser;

    public function __construct(LogParserService $logParser)
    {
        parent::__construct();
        $this->logParser = $logParser;
    }

    public function handle()
    {
        $hours = $this->option('hours');
        $this->info("Parsing logs from the last {$hours} hours...");

        $parsed = $this->logParser->parseLatestLogs($hours);

        $this->info("Successfully parsed {$parsed} log entries.");
        
        return 0;
    }
}
