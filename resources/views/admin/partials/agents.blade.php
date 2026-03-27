<div x-data="agentsTab()" x-cloak>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold">상담원 관리</h2>
        <button @click="showCreate = !showCreate" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
            <span x-text="showCreate ? '취소' : '+ 새 상담원'"></span>
        </button>
    </div>

    <div x-show="showCreate" x-cloak class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <h3 class="font-medium mb-3">상담원 생성</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
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
                <label class="block text-sm font-medium mb-1">이름</label>
                <input
                    type="text"
                    x-model="form.name"
                    placeholder="상담원 이름"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">이메일</label>
                <input
                    type="email"
                    x-model="form.email"
                    placeholder="email@example.com"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">역할</label>
                <select
                    x-model="form.role"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="agent">agent</option>
                    <option value="admin">admin</option>
                </select>
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <button
                @click="createAgent()"
                :disabled="!form.tenant_id"
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
                                    class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition-colors"
                                >삭제</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div x-show="agents.length === 0" class="p-8 text-center text-gray-400">
            등록된 상담원이 없습니다
        </div>
    </div>
</div>

<script>
function agentsTab() {
    return {
        agents: [],
        showCreate: false,
        form: { tenant_id: '', name: '', email: '', role: 'agent' },

        get authHeaders() {
            return {
                'Authorization': 'Bearer ' + document.cookie.match(/shaul_access_token=([^;]+)/)?.[1],
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            };
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
            try {
                const res = await fetch('/api/admin/agents', {
                    method: 'POST',
                    headers: this.authHeaders,
                    body: JSON.stringify(this.form),
                });
                const json = await res.json();
                if (json.success) {
                    this.agents.push(json.data);
                    this.form = { tenant_id: '', name: '', email: '', role: 'agent' };
                    this.showCreate = false;
                }
            } catch (e) {}
        },

        async deleteAgent(id) {
            if (!confirm('이 상담원을 삭제하시겠습니까?')) return;
            try {
                const res = await fetch(`/api/admin/agents/${id}`, {
                    method: 'DELETE',
                    headers: this.authHeaders,
                });
                const json = await res.json();
                if (json.success) {
                    this.agents = this.agents.filter(a => a.id !== id);
                }
            } catch (e) {}
        },
    };
}
</script>
