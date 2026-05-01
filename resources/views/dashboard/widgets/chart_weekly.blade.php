{{-- 週次完了数グラフウィジェット --}}
<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📈 週次完了数（過去4週間）</h3>
    <div style="height: 16rem;">
        <canvas id="weeklyChart"></canvas>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const weeklyCtx = document.getElementById('weeklyChart');
            if (weeklyCtx) {
                // 既存のチャートインスタンスがあれば破棄
                const existingChart = Chart.getChart(weeklyCtx);
                if (existingChart) {
                    existingChart.destroy();
                }

                new Chart(weeklyCtx, {
                    type: 'line',
                    data: {
                        labels: @json($weeklyData['labels']),
                        datasets: [{
                            label: '完了数',
                            data: @json($weeklyData['data']),
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#f3f4f6'
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#f3f4f6'
                                },
                                grid: {
                                    color: '#374151'
                                }
                            },
                            y: {
                                ticks: {
                                    color: '#f3f4f6'
                                },
                                grid: {
                                    color: '#374151'
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
