<div x-data="faqTab()" x-cloak>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold">FAQ 관리</h2>
        <button @click="showCreate = !showCreate" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
            <span x-text="showCreate ? '취소' : '+ 새 FAQ'"></span>
        </button>
    </div>

    <div x-show="showCreate" x-cloak class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <h3 class="font-medium mb-3">FAQ 생성</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">테넌트 ID <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    x-model="form.tenant_id"
                    placeholder="테넌트 ID"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">키워드 <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    x-model="form.keyword"
                    placeholder="키워드"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">답변 <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    x-model="form.answer"
                    placeholder="답변 내용"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <button
                @click="createFaq()"
                :disabled="!form.tenant_id || !form.keyword || !form.answer"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white text-sm rounded-lg transition-colors"
            >생성</button>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">키워드</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">답변</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">액션</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-for="faq in faqs" :key="faq.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 font-medium" x-text="faq.keyword"></td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400" x-text="faq.answer"></td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    @click="deleteFaq(faq.id)"
                                    class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition-colors"
                                >삭제</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div x-show="faqs.length === 0" class="p-8 text-center text-gray-400">
            등록된 FAQ가 없습니다
        </div>
    </div>
</div>

<script>
function faqTab() {
    return {
        faqs: [],
        showCreate: false,
        form: { tenant_id: '', keyword: '', answer: '' },

        get authHeaders() {
            return {
                'Authorization': 'Bearer ' + document.cookie.match(/shaul_access_token=([^;]+)/)?.[1],
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            };
        },

        async init() {
            try {
                const res = await fetch('/api/admin/faq', { headers: this.authHeaders });
                const json = await res.json();
                if (json.success) this.faqs = json.data;
            } catch (e) {}
        },

        async createFaq() {
            if (!this.form.tenant_id || !this.form.keyword || !this.form.answer) return;
            try {
                const res = await fetch('/api/admin/faq', {
                    method: 'POST',
                    headers: this.authHeaders,
                    body: JSON.stringify(this.form),
                });
                const json = await res.json();
                if (json.success) {
                    this.faqs.push(json.data);
                    this.form = { tenant_id: '', keyword: '', answer: '' };
                    this.showCreate = false;
                }
            } catch (e) {}
        },

        async deleteFaq(id) {
            if (!confirm('이 FAQ를 삭제하시겠습니까?')) return;
            try {
                const res = await fetch(`/api/admin/faq/${id}`, {
                    method: 'DELETE',
                    headers: this.authHeaders,
                });
                const json = await res.json();
                if (json.success) {
                    this.faqs = this.faqs.filter(f => f.id !== id);
                }
            } catch (e) {}
        },
    };
}
</script>
