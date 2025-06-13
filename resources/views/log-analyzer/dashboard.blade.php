<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Log Analyzer - Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.12.0/cdn.min.js" defer></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .header h1 {
            color: #2d3748;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            color: #718096;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(31, 38, 135, 0.5);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .error-stat { color: #e53e3e; }
        .warning-stat { color: #dd6b20; }
        .pattern-stat { color: #3182ce; }
        .critical-stat { color: #9f7aea; }

        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .card-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2d3748;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .error-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .error-item {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s ease;
        }

        .error-item:hover {
            background-color: #f7fafc;
        }

        .error-level {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .level-error { background: #fed7d7; color: #c53030; }
        .level-warning { background: #feebc8; color: #c05621; }
        .level-info { background: #bee3f8; color: #2b6cb0; }

        .error-message {
            font-size: 0.9rem;
            color: #4a5568;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .error-meta {
            font-size: 0.8rem;
            color: #718096;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pattern-item {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .pattern-item:hover {
            background-color: #f7fafc;
            border-left: 4px solid #667eea;
        }

        .pattern-category {
            display: inline-block;
            padding: 4px 12px;
            background: #667eea;
            color: white;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .pattern-count {
            font-weight: 700;
            color: #e53e3e;
            font-size: 1.1rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 16px;
            width: 80%;
            max-width: 600px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .ai-analysis {
            background: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .ai-analysis h4 {
            color: #2d3748;
            margin-bottom: 10px;
        }

        .ai-analysis p {
            line-height: 1.6;
            color: #4a5568;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #feb2b2;
        }

        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container" x-data="logAnalyzer()">
        <!-- Header -->
        <div class="header">
            <h1>🔍 Laravel Log Analyzer</h1>
            <p>Intelligent error tracking and pattern recognition for your Laravel applications</p>
        </div>

        <!-- Alert Messages -->
        <div x-show="alert.show" x-transition class="alert" :class="alert.type === 'success' ? 'alert-success' : 'alert-error'">
            <span x-text="alert.message"></span>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number error-stat">{{ $stats['total_errors'] ?? 0 }}</div>
                <div class="stat-label">Total Errors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number warning-stat">{{ $stats['recent_errors'] ?? 0 }}</div>
                <div class="stat-label">Recent (24h)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number pattern-stat">{{ $stats['total_patterns'] ?? 0 }}</div>
                <div class="stat-label">Error Patterns</div>
            </div>
            <div class="stat-card">
                <div class="stat-number critical-stat">{{ $stats['critical_patterns'] ?? 0 }}</div>
                <div class="stat-label">Critical Patterns</div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="main-grid">
            <!-- Recent Errors -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Errors</h3>
                    <button class="btn btn-primary" @click="parseLogs()" :disabled="parsing">
                        <span x-show="!parsing">🔄 Parse Logs</span>
                        <span x-show="parsing" class="loading"></span>
                    </button>
                </div>
                
                <div class="error-list">
                    @forelse($recentErrors ?? [] as $error)
                    <div class="error-item">
                        <div class="error-level level-{{ $error->level }}">{{ $error->level }}</div>
                        <div class="error-message">{{ Str::limit($error->message, 200) }}</div>
                        <div class="error-meta">
                            <span>{{ $error->created_at->diffForHumans() }}</span>
                            <button class="btn btn-secondary" @click="analyzeError({{ $error->id }})">
                                🤖 AI Analysis
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="error-item">
                        <p>No recent errors found. Try parsing your logs first.</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Error Patterns -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Top Error Patterns</h3>
                </div>
                
                @forelse($topPatterns ?? [] as $pattern)
                <div class="pattern-item">
                    <div class="pattern-category">{{ $pattern->category }}</div>
                    <div class="pattern-count">{{ $pattern->occurrence_count }}x occurrences</div>
                    <div class="error-message">{{ Str::limit($pattern->pattern_description, 100) }}</div>
                    <div class="error-meta">
                        <span>Last seen: {{ $pattern->last_seen->diffForHumans() }}</span>
                        <button class="btn btn-secondary" @click="getPatternSuggestion({{ $pattern->id }})">
                            💡 Get Solution
                        </button>
                    </div>
                    
                    <div x-show="patternSuggestions[{{ $pattern->id }}]" class="ai-analysis" style="margin-top: 10px;">
                        <h4>AI Suggestion:</h4>
                        <p x-text="patternSuggestions[{{ $pattern->id }}]"></p>
                    </div>
                </div>
                @empty
                <div class="pattern-item">
                    <p>No patterns detected yet. Parse some logs to see patterns emerge.</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Error Trends Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📈 Error Trends (Last 7 Days)</h3>
            </div>
            <div class="chart-container">
                <canvas id="errorTrendsChart"></canvas>
            </div>
        </div>

        <!-- Analysis Modal -->
        <div id="analysisModal" class="modal">
            <div class="modal-content">
                <span class="close" @click="closeModal()">&times;</span>
                <h2>🤖 AI Error Analysis</h2>
                <div id="analysisContent">
                    <div class="loading" x-show="analyzing"></div>
                    <div x-show="!analyzing && currentAnalysis" class="ai-analysis">
                        <div x-html="currentAnalysis"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Alpine.js component
        function logAnalyzer() {
            return {
                parsing: false,
                analyzing: false,
                currentAnalysis: '',
                patternSuggestions: {},
                alert: {
                    show: false,
                    message: '',
                    type: 'success'
                },

                init() {
                    this.initChart();
                    this.setupCSRF();
                },

                setupCSRF() {
                    const token = document.querySelector('meta[name="csrf-token"]');
                    if (token) {
                        window.axios = window.axios || {};
                        window.axios.defaults = window.axios.defaults || {};
                        window.axios.defaults.headers = window.axios.defaults.headers || {};
                        window.axios.defaults.headers.common = window.axios.defaults.headers.common || {};
                        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
                    }
                },

                async parseLogs() {
                    this.parsing = true;
                    
                    try {
                        const response = await fetch('/logs/parse', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ hours: 24 })
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            this.showAlert(data.message, 'success');
                            // Refresh page after 2 seconds
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            this.showAlert('Failed to parse logs', 'error');
                        }
                    } catch (error) {
                        this.showAlert('Error occurred while parsing logs', 'error');
                        console.error('Parse error:', error);
                    } finally {
                        this.parsing = false;
                    }
                },

                async analyzeError(errorId) {
                    this.analyzing = true;
                    this.currentAnalysis = '';
                    document.getElementById('analysisModal').style.display = 'block';

                    try {
                        const response = await fetch(`/logs/analyze/${errorId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        const data = await response.json();
                        this.currentAnalysis = this.formatAnalysis(data.analysis);
                    } catch (error) {
                        this.currentAnalysis = 'Failed to get AI analysis. Please try again.';
                        console.error('Analysis error:', error);
                    } finally {
                        this.analyzing = false;
                    }
                },

                async getPatternSuggestion(patternId) {
                    try {
                        const response = await fetch(`/logs/patterns/${patternId}/suggest`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        const data = await response.json();
                        this.patternSuggestions[patternId] = data.suggestion;
                    } catch (error) {
                        console.error('Pattern suggestion error:', error);
                        this.patternSuggestions[patternId] = 'Failed to get suggestion. Please try again.';
                    }
                },

                formatAnalysis(analysis) {
                    if (!analysis) return '';
                    
                    // Convert newlines to HTML breaks and format lists
                    return analysis
                        .replace(/\n\n/g, '<br><br>')
                        .replace(/\n/g, '<br>')
                        .replace(/(\d+\.\s)/g, '<strong>$1</strong>')
                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                },

                closeModal() {
                    document.getElementById('analysisModal').style.display = 'none';
                },

                showAlert(message, type) {
                    this.alert = { show: true, message, type };
                    setTimeout(() => {
                        this.alert.show = false;
                    }, 5000);
                },

                initChart() {
                    const ctx = document.getElementById('errorTrendsChart').getContext('2d');
                    const errorTrends = @json($errorTrends ?? []);
                    
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: errorTrends.map(item => item.date),
                            datasets: [{
                                label: 'Errors',
                                data: errorTrends.map(item => item.count),
                                borderColor: '#e53e3e',
                                backgroundColor: 'rgba(229, 62, 62, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#e53e3e',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0,0,0,0.1)'
                                    }
                                },
                                x: {
                                    grid: {
                                        color: 'rgba(0,0,0,0.1)'
                                    }
                                }
                            },
                            elements: {
                                point: {
                                    hoverRadius: 8
                                }
                            }
                        }
                    });
                }
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('analysisModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>