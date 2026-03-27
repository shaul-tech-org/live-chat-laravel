<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, interactive-widget=resizes-content">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>실시간 상담 - LCHAT</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; overscroll-behavior: none; -webkit-overflow-scrolling: touch; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #fff; color: #1f2937; }

        /* Layout */
        .mc-wrap { display: flex; flex-direction: column; height: 100vh; height: 100dvh; }
        .mc-header { display: flex; align-items: center; gap: 8px; padding: 14px 16px; background: #4F46E5; color: #fff; flex-shrink: 0; }
        .mc-header-title { font-size: 16px; font-weight: 600; }
        .mc-status-dot { width: 8px; height: 8px; border-radius: 50%; background: #9CA3AF; flex-shrink: 0; transition: background .3s; }
        .mc-status-dot.online { background: #34D399; }
        .mc-status-dot.reconnecting { background: #FBBF24; animation: mc-pulse 1s infinite; }
        @keyframes mc-pulse { 0%,100% { opacity:1; } 50% { opacity:.4; } }

        /* Messages */
        .mc-messages { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 8px; background: #F9FAFB; scrollbar-width: none; -ms-overflow-style: none; }
        .mc-messages::-webkit-scrollbar { display: none; }

        .mc-msg { max-width: 80%; padding: 10px 14px; border-radius: 14px; font-size: 14px; line-height: 1.5; word-break: break-word; }
        .mc-msg-visitor { align-self: flex-end; background: #4F46E5; color: #fff; border-bottom-right-radius: 4px; }
        .mc-msg-agent { align-self: flex-start; background: #E5E7EB; color: #1F2937; border-bottom-left-radius: 4px; }
        .mc-msg-system { align-self: center; font-size: 12px; color: #9CA3AF; font-style: italic; background: none; padding: 4px 8px; }
        .mc-msg-time { font-size: 11px; margin-top: 4px; opacity: .6; }
        .mc-msg-name { font-size: 11px; font-weight: 600; margin-bottom: 2px; opacity: .7; }
        .mc-msg-file img { max-width: 200px; border-radius: 8px; margin-top: 4px; }
        .mc-msg-file a { color: inherit; text-decoration: underline; }

        /* Typing indicator */
        .mc-typing { align-self: flex-start; display: none; align-items: center; gap: 4px; padding: 10px 14px; background: #E5E7EB; border-radius: 14px; border-bottom-left-radius: 4px; }
        .mc-typing.show { display: flex; }
        .mc-typing-dot { width: 7px; height: 7px; background: #9CA3AF; border-radius: 50%; }
        @keyframes mc-bounce { 0%,60%,100% { transform: translateY(0); } 30% { transform: translateY(-6px); } }
        .mc-typing-dot:nth-child(1) { animation: mc-bounce 1.2s infinite 0s; }
        .mc-typing-dot:nth-child(2) { animation: mc-bounce 1.2s infinite .15s; }
        .mc-typing-dot:nth-child(3) { animation: mc-bounce 1.2s infinite .3s; }

        /* Empty state */
        .mc-empty { flex: 1; display: flex; align-items: center; justify-content: center; color: #9CA3AF; font-size: 14px; }

        /* Input */
        .mc-input-area { display: flex; align-items: flex-end; gap: 8px; padding: 10px 12px; border-top: 1px solid #E5E7EB; background: #fff; flex-shrink: 0; }
        .mc-textarea { flex: 1; border: 1px solid #D1D5DB; border-radius: 10px; padding: 10px 12px; font-size: 16px; line-height: 1.4; min-height: 42px; max-height: 100px; resize: none; outline: none; background: #fff; color: #1f2937; overflow-y: auto; -webkit-appearance: none; }
        .mc-textarea:focus { border-color: #4F46E5; box-shadow: 0 0 0 2px rgba(79,70,229,.15); }
        .mc-textarea::placeholder { color: #9CA3AF; }
        .mc-send-btn { width: 42px; height: 42px; border-radius: 10px; border: none; background: #4F46E5; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background .15s; }
        .mc-send-btn:hover { background: #4338CA; }
        .mc-send-btn:disabled { background: #A5B4FC; cursor: not-allowed; }
        .mc-send-btn svg { width: 18px; height: 18px; fill: #fff; }

        /* Loading */
        .mc-loading { flex: 1; display: flex; align-items: center; justify-content: center; background: #F9FAFB; }
        .mc-spinner { width: 32px; height: 32px; border: 3px solid #E5E7EB; border-top-color: #4F46E5; border-radius: 50%; animation: mc-spin .7s linear infinite; }
        @keyframes mc-spin { to { transform: rotate(360deg); } }

        /* Error state */
        .mc-error { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; padding: 24px; background: #F9FAFB; text-align: center; }
        .mc-error-icon { font-size: 40px; }
        .mc-error-text { color: #6B7280; font-size: 14px; line-height: 1.5; }
        .mc-retry-btn { padding: 10px 20px; border: none; border-radius: 8px; background: #4F46E5; color: #fff; font-size: 14px; cursor: pointer; }

        /* Safe area for notch devices */
        .mc-header { padding-top: max(14px, env(safe-area-inset-top)); }
        .mc-input-area { padding-bottom: max(10px, env(safe-area-inset-bottom)); }

        /* Hide when Alpine is loading */
        [x-cloak] { display: none !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
</head>
<body>
    <div x-data="mobileChat()" x-cloak class="mc-wrap" x-ref="wrap">
        {{-- Header --}}
        <header class="mc-header">
            <div class="mc-status-dot" :class="connectionStatus"></div>
            <h1 class="mc-header-title">실시간 상담</h1>
        </header>

        {{-- Loading --}}
        <template x-if="state === 'loading'">
            <div class="mc-loading"><div class="mc-spinner"></div></div>
        </template>

        {{-- Error --}}
        <template x-if="state === 'error'">
            <div class="mc-error">
                <div class="mc-error-icon">!</div>
                <div class="mc-error-text" x-text="errorMessage"></div>
                <button class="mc-retry-btn" @click="loadMessages()">다시 시도</button>
            </div>
        </template>

        {{-- Messages --}}
        <template x-if="state === 'ready'">
            <div class="mc-messages" x-ref="msgContainer"
                 @scroll="onScroll()">
                {{-- Empty state --}}
                <template x-if="messages.length === 0">
                    <div class="mc-empty">상담사와 대화를 시작하세요</div>
                </template>

                {{-- Message list --}}
                <template x-for="msg in messages" :key="msg.id">
                    <div>
                        {{-- System --}}
                        <template x-if="msg.sender_type === 'system'">
                            <div class="mc-msg mc-msg-system" x-text="msg.content"></div>
                        </template>

                        {{-- Visitor --}}
                        <template x-if="msg.sender_type === 'visitor'">
                            <div style="align-self:flex-end;max-width:80%;">
                                <div class="mc-msg mc-msg-visitor">
                                    <template x-if="msg.content_type === 'file' && msg.file_url">
                                        <div class="mc-msg-file">
                                            <template x-if="isImage(msg.file_url)">
                                                <img :src="msg.file_url" :alt="msg.content" loading="lazy">
                                            </template>
                                            <template x-if="!isImage(msg.file_url)">
                                                <a :href="msg.file_url" target="_blank" x-text="msg.content || '파일 다운로드'"></a>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="msg.content_type !== 'file'">
                                        <span x-text="msg.content"></span>
                                    </template>
                                </div>
                                <div class="mc-msg-time" style="text-align:right;" x-text="formatTime(msg.created_at)"></div>
                            </div>
                        </template>

                        {{-- Agent --}}
                        <template x-if="msg.sender_type === 'agent'">
                            <div style="align-self:flex-start;max-width:80%;">
                                <div class="mc-msg-name" x-text="msg.sender_name || '상담사'"></div>
                                <div class="mc-msg mc-msg-agent">
                                    <template x-if="msg.content_type === 'file' && msg.file_url">
                                        <div class="mc-msg-file">
                                            <template x-if="isImage(msg.file_url)">
                                                <img :src="msg.file_url" :alt="msg.content" loading="lazy">
                                            </template>
                                            <template x-if="!isImage(msg.file_url)">
                                                <a :href="msg.file_url" target="_blank" x-text="msg.content || '파일 다운로드'"></a>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="msg.content_type !== 'file'">
                                        <span x-text="msg.content"></span>
                                    </template>
                                </div>
                                <div class="mc-msg-time" x-text="formatTime(msg.created_at)"></div>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Typing indicator --}}
                <div class="mc-typing" :class="{ 'show': showTyping }">
                    <div class="mc-typing-dot"></div>
                    <div class="mc-typing-dot"></div>
                    <div class="mc-typing-dot"></div>
                </div>
            </div>
        </template>

        {{-- Input area --}}
        <template x-if="state === 'ready'">
            <div class="mc-input-area">
                <textarea
                    x-ref="textarea"
                    x-model="newMessage"
                    @keydown.enter.prevent="if (!$event.shiftKey && !$event.isComposing) sendMessage()"
                    @input="autoResize(); sendTyping()"
                    @focus="onTextareaFocus()"
                    placeholder="메시지를 입력하세요..."
                    rows="1"
                    enterkeyhint="send"
                    class="mc-textarea"
                ></textarea>
                <button
                    class="mc-send-btn"
                    :disabled="!newMessage.trim() || sending"
                    @click="sendMessage()"
                >
                    <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </div>
        </template>
    </div>

    <script>
    function mobileChat() {
        const ROOM_ID = @json($roomId);
        const POLL_MS = 5000;
        const TYPING_DEBOUNCE_MS = 3000;
        const ECHO_CDN = 'https://cdn.jsdelivr.net/npm/laravel-echo@2/dist/echo.iife.min.js';
        const PUSHER_CDN = 'https://cdn.jsdelivr.net/npm/pusher-js@8/dist/web/pusher.min.js';
        const REVERB_KEY = @json(config('broadcasting.connections.reverb.key', ''));
        const REVERB_HOST = @json(config('broadcasting.connections.reverb.options.host', ''));
        const REVERB_PORT = parseInt(@json((string) config('broadcasting.connections.reverb.options.port', '443')), 10);

        return {
            /* ── State ─────────────────────────── */
            state: 'loading',        // loading | ready | error
            messages: [],
            newMessage: '',
            sending: false,
            connectionStatus: 'offline',
            showTyping: false,
            errorMessage: '',
            userScrolled: false,

            /* internal */
            _apiKey: null,
            _visitorName: null,
            _pollTimer: null,
            _typingTimeout: null,
            _lastTypingSent: 0,
            _echoChannel: null,
            _rafPending: false,

            /* ── Init ──────────────────────────── */
            init() {
                this._apiKey = this._getParam('api_key') || localStorage.getItem('lchat_api_key') || '';
                this._visitorName = this._getParam('name') || localStorage.getItem('lchat_visitor_name') || '방문자';

                if (this._apiKey) {
                    localStorage.setItem('lchat_api_key', this._apiKey);
                }

                this._setupKeyboard();
                this.loadMessages();
            },

            destroy() {
                this._stopPolling();
                if (this._typingTimeout) clearTimeout(this._typingTimeout);
                if (window.visualViewport) {
                    window.visualViewport.removeEventListener('resize', this._scheduleLayout);
                    window.visualViewport.removeEventListener('scroll', this._scheduleLayout);
                }
            },

            /* ── API helpers ───────────────────── */
            _getParam(key) {
                const url = new URL(window.location.href);
                return url.searchParams.get(key);
            },

            _headers() {
                return {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-API-Key': this._apiKey,
                };
            },

            _baseUrl() {
                return window.location.origin;
            },

            /* ── Load messages ─────────────────── */
            async loadMessages() {
                this.state = 'loading';
                this.errorMessage = '';

                try {
                    const res = await fetch(
                        `${this._baseUrl()}/api/rooms/${ROOM_ID}/messages?limit=50`,
                        { headers: { 'Accept': 'application/json', 'X-API-Key': this._apiKey } }
                    );

                    if (!res.ok) {
                        if (res.status === 401) {
                            this.errorMessage = 'API 키가 필요합니다. URL에 ?api_key=YOUR_KEY 를 추가해주세요.';
                        } else if (res.status === 404) {
                            this.errorMessage = '채팅방을 찾을 수 없습니다.';
                        } else {
                            this.errorMessage = `오류가 발생했습니다. (${res.status})`;
                        }
                        this.state = 'error';
                        return;
                    }

                    const json = await res.json();
                    const raw = json.data || json;
                    this.messages = Array.isArray(raw) ? raw : (Array.isArray(raw.data) ? raw.data : []);

                    this.state = 'ready';
                    this.$nextTick(() => this.scrollToBottom(true));
                    this._connectRealtime();
                } catch (e) {
                    console.error('[MobileChat] loadMessages error:', e);
                    this.errorMessage = '네트워크 오류가 발생했습니다.';
                    this.state = 'error';
                }
            },

            /* ── Send message ──────────────────── */
            async sendMessage() {
                const content = this.newMessage.trim();
                if (!content || this.sending) return;

                this.sending = true;
                this.newMessage = '';
                this.$nextTick(() => this.autoResize());

                // Optimistic append
                const tempId = 'temp-' + Date.now();
                const tempMsg = {
                    id: tempId,
                    sender_type: 'visitor',
                    sender_name: this._visitorName,
                    content: content,
                    content_type: 'text',
                    created_at: new Date().toISOString(),
                };
                this.messages.push(tempMsg);
                this.$nextTick(() => this.scrollToBottom(false));

                try {
                    const res = await fetch(
                        `${this._baseUrl()}/api/rooms/${ROOM_ID}/messages`,
                        {
                            method: 'POST',
                            headers: this._headers(),
                            body: JSON.stringify({
                                sender_type: 'visitor',
                                sender_name: this._visitorName,
                                content: content,
                                content_type: 'text',
                            }),
                        }
                    );

                    if (res.ok) {
                        const json = await res.json();
                        const real = json.data || json;
                        // Replace temp with real
                        const idx = this.messages.findIndex(m => m.id === tempId);
                        if (idx !== -1) this.messages[idx] = real;
                    } else {
                        this._appendSystem('메시지 전송에 실패했습니다.');
                    }
                } catch (e) {
                    console.error('[MobileChat] sendMessage error:', e);
                    this._appendSystem('메시지 전송에 실패했습니다.');
                } finally {
                    this.sending = false;
                    this.$nextTick(() => {
                        if (this.$refs.textarea) this.$refs.textarea.focus();
                    });
                }
            },

            /* ── Typing indicator ──────────────── */
            sendTyping() {
                const now = Date.now();
                if (now - this._lastTypingSent < TYPING_DEBOUNCE_MS) return;
                this._lastTypingSent = now;

                fetch(`${this._baseUrl()}/api/rooms/${ROOM_ID}/typing`, {
                    method: 'POST',
                    headers: this._headers(),
                    body: JSON.stringify({
                        sender_type: 'visitor',
                        sender_name: this._visitorName,
                    }),
                }).catch(() => {});
            },

            _showTypingIndicator() {
                this.showTyping = true;
                if (this._typingTimeout) clearTimeout(this._typingTimeout);
                this._typingTimeout = setTimeout(() => {
                    this.showTyping = false;
                }, 4000);
            },

            /* ── Realtime: Echo + Polling fallback ── */
            _connectRealtime() {
                this.connectionStatus = 'reconnecting';

                if (REVERB_KEY) {
                    this._connectEcho(REVERB_KEY, REVERB_HOST, REVERB_PORT).catch(() => {
                        this._startPolling();
                    });
                } else {
                    this._startPolling();
                }
            },

            async _connectEcho(reverbKey, reverbHost, reverbPort) {
                await this._loadScript(PUSHER_CDN);
                await this._loadScript(ECHO_CDN);

                const port = parseInt(reverbPort || '443', 10);
                const echo = new window.Echo({
                    broadcaster: 'reverb',
                    key: reverbKey,
                    wsHost: reverbHost || window.location.hostname,
                    wsPort: port,
                    wssPort: port,
                    forceTLS: port === 443,
                    enabledTransports: ['ws', 'wss'],
                    authEndpoint: this._baseUrl() + '/api/broadcasting/auth',
                    auth: {
                        headers: { 'X-API-Key': this._apiKey },
                    },
                });

                const conn = echo.connector.pusher.connection;
                conn.bind('connecting', () => { this.connectionStatus = 'reconnecting'; });
                conn.bind('connected', () => {
                    this.connectionStatus = 'online';
                    this._stopPolling();
                });
                conn.bind('unavailable', () => { this.connectionStatus = 'offline'; });
                conn.bind('disconnected', () => {
                    this.connectionStatus = 'reconnecting';
                    this._startPolling();
                });

                this._echoChannel = echo.private('chat.' + ROOM_ID);

                this._echoChannel.listen('.message.sent', (e) => {
                    const msg = e.message || e;
                    // Skip own messages
                    if (msg.sender_type === 'visitor' && String(msg.sender_name) === this._visitorName) return;
                    // Skip if already exists
                    if (this.messages.some(m => m.id === msg.id)) return;
                    this.messages.push(msg);
                    if (!this.userScrolled) {
                        this.$nextTick(() => this.scrollToBottom(false));
                    }
                });

                this._echoChannel.listen('.typing.started', (e) => {
                    const data = e.data || e;
                    if (data.sender_type === 'visitor') return;
                    this._showTypingIndicator();
                });
            },

            _loadScript(src) {
                return new Promise((resolve, reject) => {
                    if (document.querySelector(`script[src="${src}"]`)) {
                        resolve();
                        return;
                    }
                    const s = document.createElement('script');
                    s.src = src;
                    s.onload = resolve;
                    s.onerror = reject;
                    document.head.appendChild(s);
                });
            },

            _startPolling() {
                if (this._pollTimer) return;
                this.connectionStatus = 'online';
                this._pollTimer = setInterval(async () => {
                    try {
                        const res = await fetch(
                            `${this._baseUrl()}/api/rooms/${ROOM_ID}/messages?limit=50`,
                            { headers: { 'Accept': 'application/json', 'X-API-Key': this._apiKey } }
                        );
                        if (!res.ok) return;
                        const json = await res.json();
                        const raw = json.data || json;
                        const list = Array.isArray(raw) ? raw : (Array.isArray(raw.data) ? raw.data : []);
                        if (!list.length) return;

                        const existingIds = new Set(this.messages.map(m => m.id));
                        const hasNew = list.some(m => m.id && !existingIds.has(m.id) && !String(m.id).startsWith('temp-'));

                        if (hasNew) {
                            this.messages = list;
                            if (!this.userScrolled) {
                                this.$nextTick(() => this.scrollToBottom(false));
                            }
                        }
                    } catch (e) { /* silent */ }
                }, POLL_MS);
            },

            _stopPolling() {
                if (this._pollTimer) {
                    clearInterval(this._pollTimer);
                    this._pollTimer = null;
                }
            },

            /* ── Helpers ───────────────────────── */
            _appendSystem(text) {
                this.messages.push({
                    id: 'sys-' + Date.now(),
                    sender_type: 'system',
                    content: text,
                    content_type: 'system',
                    created_at: new Date().toISOString(),
                });
            },

            scrollToBottom(instant) {
                const el = this.$refs.msgContainer;
                if (!el) return;
                el.scrollTo({ top: el.scrollHeight, behavior: instant ? 'instant' : 'smooth' });
            },

            onScroll() {
                const el = this.$refs.msgContainer;
                if (!el) return;
                this.userScrolled = (el.scrollHeight - el.scrollTop - el.clientHeight) > 40;
            },

            formatTime(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return '';
                return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
            },

            isImage(url) {
                if (!url) return false;
                return /\.(jpg|jpeg|png|gif|webp|svg)(\?|$)/i.test(url);
            },

            autoResize() {
                const ta = this.$refs.textarea;
                if (!ta) return;
                ta.style.height = 'auto';
                ta.style.height = Math.min(ta.scrollHeight, 100) + 'px';
            },

            /* ── iOS keyboard handling ─────────── */
            _scheduleLayout: null,

            _setupKeyboard() {
                const vv = window.visualViewport;
                if (!vv) return;

                let rafPending = false;
                const update = () => {
                    const wrap = this.$refs.wrap;
                    if (!wrap) return;
                    // Set wrap height to visual viewport height to accommodate keyboard
                    wrap.style.height = vv.height + 'px';
                    // Ensure input stays visible
                    if (!this.userScrolled) {
                        this.$nextTick(() => this.scrollToBottom(false));
                    }
                };

                this._scheduleLayout = () => {
                    if (rafPending) return;
                    rafPending = true;
                    requestAnimationFrame(() => {
                        rafPending = false;
                        update();
                    });
                };

                vv.addEventListener('resize', this._scheduleLayout);
                vv.addEventListener('scroll', this._scheduleLayout);
            },

            onTextareaFocus() {
                // On mobile, keyboard open triggers visualViewport resize.
                // Delay scroll so keyboard has time to appear.
                setTimeout(() => {
                    this.scrollToBottom(false);
                    if (this._scheduleLayout) this._scheduleLayout();
                }, 300);
            },
        };
    }
    </script>
</body>
</html>
