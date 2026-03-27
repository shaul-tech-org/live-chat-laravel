<div x-data="statsTab()" x-cloak>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold">통계</h2>
        <div class="flex gap-1 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-1">
            <button
                @click="changePeriod('7d')"
                :class="period === '7d' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="px-3 py-1 text-sm rounded transition-colors"
            >7일</button>
            <button
                @click="changePeriod('30d')"
                :class="period === '30d' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="px-3 py-1 text-sm rounded transition-colors"
            >30일</button>
            <button
                @click="changePeriod('90d')"
                :class="period === '90d' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="px-3 py-1 text-sm rounded transition-colors"
            >90일</button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">총 채팅 수</div>
            <div class="text-3xl font-bold" x-text="stats.total_chats"></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">평균 평점</div>
            <div class="flex items-center gap-2">
                <span class="text-3xl font-bold" x-text="Number(stats.avg_rating).toFixed(1)"></span>
                <div class="flex items-center gap-0.5">
                    <template x-for="i in 5" :key="i">
                        <span :class="i <= Math.round(stats.avg_rating) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'" class="text-xl">★</span>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-medium">일별 통계</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">날짜</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">채팅 수</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">평균 평점</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-for="day in stats.daily" :key="day.date">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 font-medium" x-text="day.date"></td>
                            <td class="px-4 py-3 text-center" x-text="day.chats"></td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <template x-for="i in 5" :key="i">
                                        <span :class="i <= Math.round(day.avg_rating) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'" class="text-xs">★</span>
                                    </template>
                                    <span class="ml-1 text-gray-500 dark:text-gray-400" x-text="Number(day.avg_rating).toFixed(1)"></span>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div x-show="!stats.daily || stats.daily.length === 0" class="p-8 text-center text-gray-400">
            통계 데이터가 없습니다
        </div>
    </div>
</div>

<script>
function statsTab() {
    return {
        stats: { total_chats: 0, avg_rating: 0, daily: [] },
        period: '7d',

        get authHeaders() {
            return {
                'Authorization': 'Bearer ' + document.cookie.match(/shaul_access_token=([^;]+)/)?.[1],
                'Accept': 'application/json',
            };
        },

        async init() {
            await this.fetchStats();
        },

        async changePeriod(p) {
            this.period = p;
            await this.fetchStats();
        },

        async fetchStats() {
            try {
                const res = await fetch(`/api/admin/stats?period=${this.period}`, { headers: this.authHeaders });
                const json = await res.json();
                if (json.success) this.stats = json.data;
            } catch (e) {}
        },
    };
}
</script>
