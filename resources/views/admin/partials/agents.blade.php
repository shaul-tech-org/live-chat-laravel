<div x-data="agentsTab()" x-cloak>
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
        <h2 class="text-lg font-bold">상담원 관리</h2>
        <button @click="showCreate = !showCreate" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
            <span x-text="showCreate ? '취소' : '+ 새 상담원'"></span>
        </button>
    </div>

    <div x-show="showCreate" x-cloak class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <h3 class="font-medium mb-3">상담원 생성</h3>
        {{-- General Error --}}
        <div x-show="errors.general" class="mb-3 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-red-600 dark:text-red-400 text-sm" x-text="errors.general"></p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
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
                <label class="block text-sm font-medium mb-1">이름</label>
                <input
                    type="text"
                    x-model="form.name"
                    placeholder="상담원 이름"
                    :class="errors.name ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500'"
                    class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2"
                >
                <div x-show="errors.name" class="text-red-500 text-xs mt-1" x-text="errors.name"></div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">이메일</label>
                <input
                    type="email"
                    x-model="form.email"
                    placeholder="email@example.com"
                    :class="errors.email ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500'"
                    class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2"
                >
                <div x-show="errors.email" class="text-red-500 text-xs mt-1" x-text="errors.email"></div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">역할</label>
                <select
                    x-model="form.role"
                    :class="errors.role ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500'"
                    class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2"
                >
                    <option value="agent">agent</option>
                    <option value="admin">admin</option>
                </select>
                <div x-show="errors.role" class="text-red-500 text-xs mt-1" x-text="errors.role"></div>
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <button
                @click="createAgent()"
                :disabled="!form.tenant_id || loading"
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
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">이메일</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">역할</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">온라인 상태</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">액션</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-for="agent in agents" :key="agent.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 font-medium" x-text="agent.name || '-'"></td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400" x-text="agent.email || '-'"></td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    :class="agent.role === 'admin' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'"
                                    class="px-2 py-0.5 text-xs rounded-full"
                                    x-text="agent.role"
                                ></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    :class="agent.is_online ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                                    class="px-2 py-0.5 text-xs rounded-full"
                                    x-text="agent.is_online ? '온라인' : '오프라인'"
                                ></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    @click="deleteAgent(agent.id)"
                                    :disabled="deletingId === agent.id"
                                    class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white rounded transition-colors inline-flex items-center gap-1"
                                >
                                    <svg x-show="deletingId === agent.id" class="animate-spin h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="deletingId === agent.id ? '삭제중...' : '삭제'"></span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div x-show="agents.length === 0" class="p-8 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-gray-400 dark:text-gray-500 mb-1">등록된 상담원이 없습니다</p>
            <p class="text-xs text-gray-300 dark:text-gray-600">우측 상단의 '+ 새 상담원' 버튼으로 추가하세요</p>
        </div>
    </div>
</div>

<script>
function agentsTab() {
    return {
        agents: [],
        showCreate: false,
        loading: false,
        deletingId: null,
        errors: {},
        toast: '',
        form: { tenant_id: '', name: '', email: '', role: 'agent' },

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
                const res = await fetch('/api/admin/agents', { headers: this.authHeaders });
                const json = await res.json();
                if (json.success) this.agents = json.data;
            } catch (e) {}
        },

        async createAgent() {
            if (!this.form.tenant_id) return;
            this.loading = true;
            this.errors = {};
            try {
                const res = await fetch('/api/admin/agents', {
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
                    this.agents.push(json.data);
                    this.form = { tenant_id: '', name: '', email: '', role: 'agent' };
                    this.showCreate = false;
                    this.showToast('저장됨');
                }
            } catch (e) {
                this.errors = { general: '저장에 실패했습니다.' };
            } finally {
                this.loading = false;
            }
        },

        async deleteAgent(id) {
            if (!confirm('이 상담원을 삭제하시겠습니까?')) return;
            this.deletingId = id;
            try {
                const res = await fetch(`/api/admin/agents/${id}`, {
                    method: 'DELETE',
                    headers: this.authHeaders,
                });
                const json = await res.json();
                if (!res.ok) {
                    alert(json.message || '삭제에 실패했습니다.');
                    return;
                }
                if (json.success) {
                    this.agents = this.agents.filter(a => a.id !== id);
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
