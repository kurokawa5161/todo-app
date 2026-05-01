{{-- 年間完了数グラフウィジェット --}}
<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📅 年間完了数（過去12ヶ月）</h3>
    <div style="height: 20rem;">
        <canvas id="yearlyChart"></canvas>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const yearlyCtx = document.getElementById('yearlyChart');
            if (yearlyCtx) {
                // 既存のチャートインスタンスがあれば破棄
                const existingChart = Chart.getChart(yearlyCtx);
                if (existingChart) {
                    existingChart.destroy();
                }

                new Chart(yearlyCtx, {
                    type: 'line',
                    data: {
                        labels: @json($yearlyData['labels']),
                        datasets: [{
                            label: '完了数',
                            data: @json($yearlyData['data']),
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
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
