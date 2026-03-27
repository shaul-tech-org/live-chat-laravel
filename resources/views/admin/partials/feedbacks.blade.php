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
        <div x-show="feedbacks.length === 0" class="p-8 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            <p class="text-gray-400 dark:text-gray-500 mb-1">아직 피드백이 없습니다</p>
            <p class="text-xs text-gray-300 dark:text-gray-600">방문자가 상담 후 평가를 남기면 여기에 표시됩니다</p>
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
