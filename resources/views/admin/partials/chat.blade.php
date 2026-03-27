<div x-data="chatTab()" class="flex h-full">
    <div class="w-80 border-r border-gray-200 dark:border-gray-700 flex flex-col bg-white dark:bg-gray-800 shrink-0">
        <div class="p-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    채팅 목록 <span class="text-xs text-gray-400" x-text="'(' + totalRooms + '개)'"></span>
                </span>
                <select
                    x-model="sort"
                    @change="changeSort()"
                    class="text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:ring-1 focus:ring-blue-500"
                >
                    <option value="newest">최신순</option>
                    <option value="activity">최근 활동순</option>
                </select>
            </div>
            <input
                type="text"
                x-model="search"
                placeholder="검색..."
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-[16px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
            <div class="flex gap-1 mt-2">
                <button
                    @click="filter = 'all'"
                    :class="filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="px-3 py-1 text-xs rounded-full transition-colors"
                >전체</button>
                <button
                    @click="filter = 'active'"
                    :class="filter === 'active' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="px-3 py-1 text-xs rounded-full transition-colors"
                >진행중</button>
                <button
                    @click="filter = 'closed'"
                    :class="filter === 'closed' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="px-3 py-1 text-xs rounded-full transition-colors"
                >종료</button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto">
            <template x-for="room in filteredRooms" :key="room.id">
                <div
                    @click="selectRoom(room)"
                    :class="selectedRoom && selectedRoom.id === room.id ? 'bg-blue-50 dark:bg-blue-900/20 border-l-2 border-blue-500' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                    class="p-3 cursor-pointer border-b border-gray-100 dark:border-gray-700 transition-colors"
                >
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-medium text-sm truncate" x-text="room.visitor_name || '방문자'"></span>
                        <span class="text-xs text-gray-400" x-text="formatTime(room.last_activity_at)"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400 truncate flex-1" x-text="room.last_message || ''"></span>
                        <span
                            x-show="room.unread_count > 0"
                            x-text="room.unread_count"
                            class="ml-2 px-1.5 py-0.5 bg-red-500 text-white text-xs rounded-full min-w-[20px] text-center"
                        ></span>
                    </div>
                    <div class="mt-1">
                        <span
                            :class="room.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                            class="text-xs px-1.5 py-0.5 rounded"
                            x-text="room.status === 'active' ? '진행중' : '종료'"
                        ></span>
                    </div>
                </div>
            </template>
            <div x-show="filteredRooms.length === 0 && !loadingMore" class="p-4 text-center text-sm text-gray-400">
                채팅방이 없습니다
            </div>
            <div x-show="loadingMore" class="p-3 text-center">
                <span class="text-xs text-gray-400">불러오는 중...</span>
            </div>
            <div x-show="hasMorePages && !loadingMore" class="p-3">
                <button
                    @click="loadMoreRooms()"
                    class="w-full py-2 text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 rounded-lg transition-colors"
                >
                    더 보기 (<span x-text="rooms.length"></span> / <span x-text="totalRooms"></span>)
                </button>
            </div>
        </div>
    </div>

    <div class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-900">
        <div x-show="!selectedRoom" x-cloak class="flex-1 flex items-center justify-center">
            <p class="text-gray-400 dark:text-gray-500 text-lg">채팅방을 선택하세요</p>
        </div>

        <template x-if="selectedRoom">
            <div class="flex-1 flex flex-col">
                <div class="px-4 py-3 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shrink-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-medium" x-text="selectedRoom.visitor_name || '방문자'"></span>
                            <span
                                :class="selectedRoom.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                                class="ml-2 text-xs px-1.5 py-0.5 rounded"
                                x-text="selectedRoom.status === 'active' ? '진행중' : '종료'"
                            ></span>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-4 space-y-3" x-ref="messageContainer">
                    <template x-for="msg in messages" :key="msg.id">
                        <div>
                            <div x-show="msg.sender_type === 'system'" class="text-center">
                                <span class="text-xs text-gray-400 italic" x-text="msg.content"></span>
                            </div>
                            <div x-show="msg.sender_type === 'visitor'" class="flex justify-start">
                                <div class="max-w-[70%]">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1" x-text="msg.sender_name || '방문자'"></div>
                                    <div class="bg-gray-200 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm">
                                        <span x-text="msg.content"></span>
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1" x-text="formatMessageTime(msg.created_at)"></div>
                                </div>
                            </div>
                            <div x-show="msg.sender_type === 'agent'" class="flex justify-end">
                                <div class="max-w-[70%]">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1 text-right" x-text="msg.sender_name || '상담사'"></div>
                                    <div class="bg-blue-600 text-white rounded-lg px-3 py-2 text-sm">
                                        <span x-text="msg.content"></span>
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1 text-right" x-text="formatMessageTime(msg.created_at)"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="typingUser" x-cloak class="px-4 py-1 text-xs text-gray-400 italic" x-text="typingUser + ' 입력 중...'"></div>

                <div class="p-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shrink-0">
                    <div class="flex gap-2">
                        <textarea
                            x-model="newMessage"
                            @keydown.enter.prevent="sendMessage()"
                            @input="emitTyping()"
                            placeholder="메시지를 입력하세요..."
                            rows="1"
                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-[16px] resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        ></textarea>
                        <button
                            @click="sendMessage()"
                            :disabled="!newMessage.trim()"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white rounded-lg transition-colors shrink-0"
                        >전송</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function chatTab() {
    return {
        rooms: [],
        selectedRoom: null,
        messages: [],
        newMessage: '',
        filter: 'all',
        search: '',
        sort: 'newest',
        currentPage: 1,
        lastPage: 1,
        totalRooms: 0,
        loadingMore: false,
        perPage: 20,

        get hasMorePages() {
            return this.currentPage < this.lastPage;
        },

        get authHeaders() {
            return {
                'Authorization': 'Bearer ' + (window.__ADMIN_TOKEN || ''),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            };
        },

        get filteredRooms() {
            return this.rooms.filter(room => {
                if (this.filter === 'active' && room.status !== 'active') return false;
                if (this.filter === 'closed' && room.status !== 'closed') return false;
                if (this.search) {
                    const q = this.search.toLowerCase();
                    const name = (room.visitor_name || '').toLowerCase();
                    const msg = (room.last_message || '').toLowerCase();
                    if (!name.includes(q) && !msg.includes(q)) return false;
                }
                return true;
            });
        },

        typingUser: null,
        typingTimeout: null,
        adminTypingTimeout: null,
        lastAdminTypingSent: 0,

        async init() {
            await this.fetchRooms(1);
            this.initEcho();
        },

        async fetchRooms(page) {
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.perPage,
                    sort: this.sort,
                });
                const res = await fetch(`/api/admin/rooms?${params}`, { headers: this.authHeaders });
                const json = await res.json();
                if (json.success) {
                    if (page === 1) {
                        this.rooms = json.data;
                    } else {
                        const existingIds = new Set(this.rooms.map(r => r.id));
                        const newRooms = json.data.filter(r => !existingIds.has(r.id));
                        this.rooms = [...this.rooms, ...newRooms];
                    }
                    if (json.meta) {
                        this.currentPage = json.meta.current_page || page;
                        this.lastPage = json.meta.last_page || 1;
                        this.totalRooms = json.meta.total || this.rooms.length;
                    }
                }
            } catch (e) {}
        },

        async loadMoreRooms() {
            if (this.loadingMore || !this.hasMorePages) return;
            this.loadingMore = true;
            await this.fetchRooms(this.currentPage + 1);
            this.loadingMore = false;
            this.subscribeNewRooms();
        },

        async changeSort() {
            this.currentPage = 1;
            this.rooms = [];
            await this.fetchRooms(1);
            this.subscribeNewRooms();
        },

        subscribeNewRooms() {
            if (!window.Echo) return;
            this.rooms.forEach(room => {
                const channelName = 'chat.' + room.id;
                if (window.Echo.connector?.channels?.[`private-${channelName}`]) return;
                window.Echo.private(channelName)
                    .listen('.message.sent', (e) => {
                        if (this.selectedRoom && this.selectedRoom.id === e.room_id) {
                            if (!this.messages.find(m => m.id === e.id)) {
                                this.messages.push(e);
                                this.$nextTick(() => {
                                    if (this.$refs.messageContainer) {
                                        this.$refs.messageContainer.scrollTop = this.$refs.messageContainer.scrollHeight;
                                    }
                                });
                            }
                        }
                    })
                    .listen('.typing.started', (e) => {
                        if (this.selectedRoom && this.selectedRoom.id === e.room_id && e.sender_type === 'visitor') {
                            this.typingUser = e.sender_name;
                            clearTimeout(this.typingTimeout);
                            this.typingTimeout = setTimeout(() => { this.typingUser = null; }, 3000);
                        }
                    })
                    .listen('.message.read', (e) => {
                        if (this.selectedRoom && this.selectedRoom.id === e.room_id) {
                            this.messages.forEach(m => { m.is_read = true; });
                        }
                    });
            });
        },

        initEcho() {
            this.subscribeNewRooms();
        },

        async selectRoom(room) {
            this.selectedRoom = room;
            try {
                const res = await fetch(`/api/admin/rooms/${room.id}/messages`, { headers: this.authHeaders });
                const json = await res.json();
                if (json.success) {
                    const raw = json.data;
                    this.messages = Array.isArray(raw) ? raw : (Array.isArray(raw.data) ? raw.data : []);
                }
                this.$nextTick(() => {
                    if (this.$refs.messageContainer) {
                        this.$refs.messageContainer.scrollTop = this.$refs.messageContainer.scrollHeight;
                    }
                });
            } catch (e) {}
        },

        async sendMessage() {
            if (!this.newMessage.trim() || !this.selectedRoom) return;
            const content = this.newMessage.trim();
            this.newMessage = '';
            try {
                const res = await fetch(`/api/admin/rooms/${this.selectedRoom.id}/messages`, {
                    method: 'POST',
                    headers: this.authHeaders,
                    body: JSON.stringify({
                        content: content,
                        sender_name: '상담사',
                    }),
                });
                const json = await res.json();
                if (json.success) {
                    this.messages.push(json.data);
                    this.$nextTick(() => {
                        if (this.$refs.messageContainer) {
                            this.$refs.messageContainer.scrollTop = this.$refs.messageContainer.scrollHeight;
                        }
                    });
                }
            } catch (e) {}
        },

        emitTyping() {
            if (!this.selectedRoom) return;
            const now = Date.now();
            if (now - this.lastAdminTypingSent < 2000) return;
            this.lastAdminTypingSent = now;
            fetch(`/api/admin/rooms/${this.selectedRoom.id}/typing`, {
                method: 'POST',
                headers: this.authHeaders,
                body: JSON.stringify({ sender_name: '상담사' }),
            }).catch(() => {});
        },

        formatTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            const now = new Date();
            if (d.toDateString() === now.toDateString()) {
                return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
            }
            return (d.getMonth() + 1) + '/' + d.getDate();
        },

        formatMessageTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
        },
    };
}
</script>
