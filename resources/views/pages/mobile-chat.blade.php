@extends('layouts.app')

@section('title', '채팅 - LCHAT')

@section('body')
<div x-data="mobileChat()" x-cloak class="flex flex-col h-screen bg-white dark:bg-gray-900">
    <header class="flex items-center px-4 py-3 bg-blue-600 text-white shrink-0">
        <h1 class="text-lg font-bold">실시간 상담</h1>
    </header>

    <div class="flex-1 overflow-y-auto p-4 space-y-3" x-ref="messageContainer">
        <template x-for="msg in messages" :key="msg.id">
            <div>
                <div x-show="msg.sender_type === 'system'" class="text-center">
                    <span class="text-xs text-gray-400 italic" x-text="msg.content"></span>
                </div>
                <div x-show="msg.sender_type === 'visitor'" class="flex justify-end">
                    <div class="max-w-[75%]">
                        <div class="bg-blue-600 text-white rounded-lg px-3 py-2 text-sm">
                            <span x-text="msg.content"></span>
                        </div>
                        <div class="text-xs text-gray-400 mt-1 text-right" x-text="formatTime(msg.created_at)"></div>
                    </div>
                </div>
                <div x-show="msg.sender_type === 'agent'" class="flex justify-start">
                    <div class="max-w-[75%]">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1" x-text="msg.sender_name || '상담사'"></div>
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm">
                            <span x-text="msg.content"></span>
                        </div>
                        <div class="text-xs text-gray-400 mt-1" x-text="formatTime(msg.created_at)"></div>
                    </div>
                </div>
            </div>
        </template>
        <div x-show="messages.length === 0" class="flex-1 flex items-center justify-center h-full">
            <p class="text-gray-400 dark:text-gray-500">상담사와 대화를 시작하세요</p>
        </div>
    </div>

    <div class="p-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shrink-0">
        <div class="flex gap-2">
            <textarea
                x-model="newMessage"
                @keydown.enter.prevent="sendMessage()"
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

<script>
function mobileChat() {
    return {
        messages: [],
        newMessage: '',
        roomId: '{{ $roomId }}',

        get authHeaders() {
            return {
                'Authorization': 'Bearer ' + document.cookie.match(/shaul_access_token=([^;]+)/)?.[1],
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            };
        },

        async init() {
            try {
                const res = await fetch(`/api/rooms/${this.roomId}/messages`, { headers: this.authHeaders });
                const json = await res.json();
                if (json.success) this.messages = json.data;
                this.$nextTick(() => this.scrollToBottom());
            } catch (e) {}
        },

        async sendMessage() {
            if (!this.newMessage.trim()) return;
            const content = this.newMessage.trim();
            this.newMessage = '';
            try {
                const res = await fetch(`/api/rooms/${this.roomId}/messages`, {
                    method: 'POST',
                    headers: this.authHeaders,
                    body: JSON.stringify({
                        content: content,
                        sender_type: 'visitor',
                        sender_name: '방문자',
                    }),
                });
                const json = await res.json();
                if (json.success) {
                    this.messages.push(json.data);
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch (e) {}
        },

        scrollToBottom() {
            if (this.$refs.messageContainer) {
                this.$refs.messageContainer.scrollTop = this.$refs.messageContainer.scrollHeight;
            }
        },

        formatTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
        },
    };
}
</script>
@endsection
