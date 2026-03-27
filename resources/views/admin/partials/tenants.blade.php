<div x-data="tenantsTab()" x-cloak>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold">테넌트 관리</h2>
        <button @click="showCreate = !showCreate" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
            <span x-text="showCreate ? '취소' : '+ 새 테넌트'"></span>
        </button>
    </div>

    <div x-show="showCreate" x-cloak class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <h3 class="font-medium mb-3">테넌트 생성</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">이름 <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    x-model="form.name"
                    placeholder="테넌트 이름"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">도메인</label>
                <input
                    type="text"
                    x-model="form.domain"
                    placeholder="example.com"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">소유자 ID <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    x-model="form.owner_id"
                    placeholder="소유자 ID"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <button
                @click="createTenant()"
                :disabled="!form.name || !form.owner_id"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white text-sm rounded-lg transition-colors"
            >생성</button>
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
                                    class="px-3 py-1 text-xs bg-yellow-500 hover:bg-yellow-600 text-white rounded transition-colors"
                                >키 재발급</button>
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
        form: { name: '', domain: '', owner_id: '' },

        get authHeaders() {
            return {
                'Authorization': 'Bearer ' + window.__ADMIN_TOKEN,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            };
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
            try {
                const res = await fetch('/api/admin/tenants', {
                    method: 'POST',
                    headers: this.authHeaders,
                    body: JSON.stringify(this.form),
                });
                const json = await res.json();
                if (json.success) {
                    this.tenants.push(json.data);
                    this.form = { name: '', domain: '', owner_id: '' };
                    this.showCreate = false;
                }
            } catch (e) {}
        },

        async rotateKey(id) {
            if (!confirm('API 키를 재발급하시겠습니까? 기존 키는 더 이상 사용할 수 없습니다.')) return;
            try {
                const res = await fetch(`/api/admin/tenants/${id}/rotate-key`, {
                    method: 'POST',
                    headers: this.authHeaders,
                });
                const json = await res.json();
                if (json.success) {
                    const idx = this.tenants.findIndex(t => t.id === id);
                    if (idx !== -1) this.tenants[idx] = json.data;
                }
            } catch (e) {}
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
