<div x-data="tenantsTab()" x-cloak>
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
        <h2 class="text-lg font-bold">테넌트 관리</h2>
        <button @click="showCreate = !showCreate" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
            <span x-text="showCreate ? '취소' : '+ 새 테넌트'"></span>
        </button>
    </div>

    <div x-show="showCreate" x-cloak class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <h3 class="font-medium mb-3">테넌트 생성</h3>
        {{-- General Error --}}
        <div x-show="errors.general" class="mb-3 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-red-600 dark:text-red-400 text-sm" x-text="errors.general"></p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">이름 <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    x-model="form.name"
                    placeholder="테넌트 이름"
                    :class="errors.name ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500'"
                    class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2"
                >
                <div x-show="errors.name" class="text-red-500 text-xs mt-1" x-text="errors.name"></div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">도메인</label>
                <input
                    type="text"
                    x-model="form.domain"
                    placeholder="example.com"
                    :class="errors.domain ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500'"
                    class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2"
                >
                <div x-show="errors.domain" class="text-red-500 text-xs mt-1" x-text="errors.domain"></div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">소유자 ID <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    x-model="form.owner_id"
                    placeholder="소유자 ID"
                    :class="errors.owner_id ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500'"
                    class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2"
                >
                <div x-show="errors.owner_id" class="text-red-500 text-xs mt-1" x-text="errors.owner_id"></div>
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <button
                @click="createTenant()"
                :disabled="!form.name || !form.owner_id || loading"
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
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">이름</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">도메인</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">API Key</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">활성</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">생성일</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">액션</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-for="tenant in tenants" :key="tenant.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 font-medium" x-text="tenant.name"></td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400" x-text="tenant.domain || '-'"></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded" x-text="maskKey(tenant.api_key)"></code>
                                    <button
                                        @click="copyKey(tenant.api_key)"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-xs"
                                        title="복사"
                                    >📋</button>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    :class="tenant.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                    class="px-2 py-0.5 text-xs rounded-full"
                                    x-text="tenant.is_active ? '활성' : '비활성'"
                                ></span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs" x-text="formatDate(tenant.created_at)"></td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    @click="rotateKey(tenant.id)"
                                    :disabled="rotatingKey === tenant.id"
                                    class="px-3 py-1 text-xs bg-yellow-500 hover:bg-yellow-600 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white rounded transition-colors inline-flex items-center gap-1"
                                >
                                    <svg x-show="rotatingKey === tenant.id" class="animate-spin h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="rotatingKey === tenant.id ? '처리중...' : '키 재발급'"></span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div x-show="tenants.length === 0" class="p-8 text-center text-gray-400">
            등록된 테넌트가 없습니다
        </div>
    </div>
</div>

<script>
function tenantsTab() {
    return {
        tenants: [],
        showCreate: false,
        loading: false,
        rotatingKey: null,
        errors: {},
        toast: '',
        form: { name: '', domain: '', owner_id: '' },

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
                const res = await fetch('/api/admin/tenants', { headers: this.authHeaders });
                const json = await res.json();
                if (json.success) this.tenants = json.data;
            } catch (e) {}
        },

        async createTenant() {
            if (!this.form.name || !this.form.owner_id) return;
            this.loading = true;
            this.errors = {};
            try {
                const res = await fetch('/api/admin/tenants', {
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
                    this.tenants.push(json.data);
                    this.form = { name: '', domain: '', owner_id: '' };
                    this.showCreate = false;
                    this.showToast('저장됨');
                }
            } catch (e) {
                this.errors = { general: '저장에 실패했습니다.' };
            } finally {
                this.loading = false;
            }
        },

        async rotateKey(id) {
            if (!confirm('API 키를 재발급하시겠습니까? 기존 키는 더 이상 사용할 수 없습니다.')) return;
            this.rotatingKey = id;
            try {
                const res = await fetch(`/api/admin/tenants/${id}/rotate-key`, {
                    method: 'POST',
                    headers: this.authHeaders,
                });
                const json = await res.json();
                if (!res.ok) {
                    alert(json.message || '키 재발급에 실패했습니다.');
                    return;
                }
                if (json.success) {
                    const idx = this.tenants.findIndex(t => t.id === id);
                    if (idx !== -1) this.tenants[idx] = json.data;
                    this.showToast('키가 재발급되었습니다');
                }
            } catch (e) {
                alert('키 재발급에 실패했습니다.');
            } finally {
                this.rotatingKey = null;
            }
        },

        maskKey(key) {
            if (!key) return '-';
            return key.substring(0, 8) + '...' + key.substring(key.length - 4);
        },

        copyKey(key) {
            if (key) navigator.clipboard.writeText(key);
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleDateString('ko-KR');
        },
    };
}
</script>
