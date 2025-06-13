<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogAnalyzerController;

// Route::get('/', function () {
//     return view('welcome');
// });
Route::prefix('logs')->group(function () {
    Route::get('/', [LogAnalyzerController::class, 'dashboard'])->name('logs.dashboard');
    Route::post('/parse', [LogAnalyzerController::class, 'parseLogs'])->name('logs.parse');
    Route::post('/analyze/{id}', [LogAnalyzerController::class, 'analyzeError'])->name('logs.analyze');
    Route::post('/patterns/{id}/suggest', [LogAnalyzerController::class, 'generatePatternSuggestion'])->name('patterns.suggest');
});
Route::get('/test-errors', function () {
    // Generate different types of errors for testing
    
    // Database error
    try {
        DB::table('non_existent_table')->get();
    } catch (Exception $e) {
        Log::error('Database error: ' . $e->getMessage());
    }
    
    // Validation error
    Log::error('Validation failed: The email field is required.');
    
    // File not found
    Log::error('File not found: /path/to/missing/file.txt');
    
    // Authentication error
    Log::error('Authentication failed: Invalid credentials provided.');
    
    return 'Test errors generated! Check your logs.';
});
