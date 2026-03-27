<div x-data="faqTab()" x-cloak>
    {{-- Success Toast --}}
    <div
        x-show="toast"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-4 right-4 z-50 px-4 py-2 bg-green-600 text-white text-sm rounded-lg shadow-lg"
        x-text="toast"
    ></div>

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold">FAQ 관리</h2>
        <button @click="showCreate = !showCreate" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
            <span x-text="showCreate ? '취소' : '+ 새 FAQ'"></span>
        </button>
    </div>

    <div x-show="showCreate" x-cloak class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <h3 class="font-medium mb-3">FAQ 생성</h3>
        {{-- General Error --}}
        <div x-show="errors.general" class="mb-3 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-red-600 dark:text-red-400 text-sm" x-text="errors.general"></p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">테넌트 ID <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    x-model="form.tenant_id"
                    placeholder="테넌트 ID"
                    :class="errors.tenant_id ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500'"
                    class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2"
                >
                <div x-show="errors.tenant_id" class="text-red-500 text-xs mt-1" x-text="errors.tenant_id"></div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">키워드 <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    x-model="form.keyword"
                    placeholder="키워드"
                    :class="errors.keyword ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500'"
                    class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2"
                >
                <div x-show="errors.keyword" class="text-red-500 text-xs mt-1" x-text="errors.keyword"></div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">답변 <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    x-model="form.answer"
                    placeholder="답변 내용"
                    :class="errors.answer ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500'"
                    class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2"
                >
                <div x-show="errors.answer" class="text-red-500 text-xs mt-1" x-text="errors.answer"></div>
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <button
                @click="createFaq()"
                :disabled="!form.tenant_id || !form.keyword || !form.answer || loading"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white text-sm rounded-lg transition-colors inline-flex items-center gap-2"
            >
                <svg x-show="loading" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-show="!loading">생성</span>
                <span x-show="loading">처리중...</span>
            </button>
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
                                    :disabled="deletingId === faq.id"
                                    class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white rounded transition-colors inline-flex items-center gap-1"
                                >
                                    <svg x-show="deletingId === faq.id" class="animate-spin h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="deletingId === faq.id ? '삭제중...' : '삭제'"></span>
                                </button>
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
        loading: false,
        deletingId: null,
        errors: {},
        toast: '',
        form: { tenant_id: '', keyword: '', answer: '' },

        get authHeaders() {
            return {
                'Authorization': 'Bearer ' + window.__ADMIN_TOKEN,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            };
        },

        showToast(message) {
            this.toast = message;
            setTimeout(() => { this.toast = ''; }, 2000);
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
            this.loading = true;
            this.errors = {};
            try {
                const res = await fetch('/api/admin/faq', {
                    method: 'POST',
                    headers: this.authHeaders,
                    body: JSON.stringify(this.form),
                });
                const json = await res.json();
                if (!res.ok) {
                    this.errors = json.errors || { general: json.message || '생성에 실패했습니다.' };
                    return;
                }
                if (json.success) {
                    this.faqs.push(json.data);
                    this.form = { tenant_id: '', keyword: '', answer: '' };
                    this.showCreate = false;
                    this.showToast('저장됨');
                }
            } catch (e) {
                this.errors = { general: '저장에 실패했습니다.' };
            } finally {
                this.loading = false;
            }
        },

        async deleteFaq(id) {
            if (!confirm('이 FAQ를 삭제하시겠습니까?')) return;
            this.deletingId = id;
            try {
                const res = await fetch(`/api/admin/faq/${id}`, {
                    method: 'DELETE',
                    headers: this.authHeaders,
                });
                const json = await res.json();
                if (!res.ok) {
                    alert(json.message || '삭제에 실패했습니다.');
                    return;
                }
                if (json.success) {
                    this.faqs = this.faqs.filter(f => f.id !== id);
                    this.showToast('삭제됨');
                }
            } catch (e) {
                alert('삭제에 실패했습니다.');
            } finally {
                this.deletingId = null;
            }
        },
    };
}
</script>
