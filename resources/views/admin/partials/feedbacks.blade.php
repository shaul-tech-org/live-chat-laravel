<div x-data="feedbacksTab()" x-cloak>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold">피드백</h2>
        <div x-show="feedbacks.length > 0" class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="text-sm text-gray-500 dark:text-gray-400">평균 평점</span>
            <div class="flex items-center gap-1">
                <template x-for="i in 5" :key="i">
                    <span :class="i <= Math.round(avgRating) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'" class="text-lg">★</span>
                </template>
            </div>
            <span class="font-bold text-lg" x-text="avgRating.toFixed(1)"></span>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">방문자</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">평점</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">코멘트</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">날짜</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-for="fb in feedbacks" :key="fb.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 font-medium" x-text="fb.visitor_name || '방문자'"></td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-0.5">
                                    <template x-for="i in 5" :key="i">
                                        <span :class="i <= fb.rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'" class="text-sm">★</span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400" x-text="fb.comment || '-'"></td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs" x-text="formatDate(fb.created_at)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div x-show="feedbacks.length === 0" class="p-8 text-center text-gray-400">
            피드백이 없습니다
        </div>
    </div>
</div>

<script>
function feedbacksTab() {
    return {
        feedbacks: [],
        avgRating: 0,

        get authHeaders() {
            return {
                'Authorization': 'Bearer ' + window.__ADMIN_TOKEN,
                'Accept': 'application/json',
            };
        },

        async init() {
            try {
                const res = await fetch('/api/admin/feedbacks', { headers: this.authHeaders });
                const json = await res.json();
                if (json.success) {
                    this.feedbacks = json.data;
                    if (this.feedbacks.length > 0) {
                        const sum = this.feedbacks.reduce((acc, fb) => acc + (fb.rating || 0), 0);
                        this.avgRating = sum / this.feedbacks.length;
                    }
                }
            } catch (e) {}
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleDateString('ko-KR');
        },
    };
}
</script>
