{{-- ガントチャートウィジェット --}}
<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📊 タスクタイムライン</h3>

    @if (count($gantData) > 0)
        <div id="gantt" style="overflow: visible;"></div>
    @else
        <p class="text-gray-500 dark:text-gray-400 text-center py-8">表示するタスクがありません</p>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.css">

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ganttEl = document.getElementById('gantt');
        if (ganttEl && @json(count($gantData)) > 0) {
            const tasks = @json($gantData);
            new Gantt('#gantt', tasks, {
                view_mode: 'Week',
                bar_height: 30,
                padding: 18,
            });
        }
    });
</script>
@endpush
