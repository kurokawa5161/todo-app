{{-- 月次完了数グラフウィジェット --}}
<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📊 月次完了数（過去6ヶ月）</h3>
    <div style="height: 16rem;">
        <canvas id="monthlyChart"></canvas>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const monthlyCtx = document.getElementById('monthlyChart');
            if (monthlyCtx) {
                // 既存のチャートインスタンスがあれば破棄
                const existingChart = Chart.getChart(monthlyCtx);
                if (existingChart) {
                    existingChart.destroy();
                }

                new Chart(monthlyCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($monthlyData['labels']),
                        datasets: [{
                            label: '完了数',
                            data: @json($monthlyData['data']),
                            backgroundColor: '#10b981'
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
