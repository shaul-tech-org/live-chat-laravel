/**
 * Live Chat Widget — Floating Bubble UI
 *
 * Usage:
 *   <script src="https://chat.shaul.kr/js/widget.js"
 *           data-api-key="YOUR_API_KEY"
 *           data-reverb-key="YOUR_REVERB_KEY"
 *           data-reverb-host="chat.shaul.kr"
 *           data-reverb-port="443"></script>
 */
(function () {
    'use strict';

    /* ── Config ─────────────────────────────────────────────── */
    var ECHO_CDN = 'https://cdn.jsdelivr.net/npm/laravel-echo@2/dist/echo.iife.min.js';
    var PUSHER_CDN = 'https://cdn.jsdelivr.net/npm/pusher-js@8/dist/web/pusher.min.js';
    var TYPING_DEBOUNCE_MS = 3000;
    var POLL_INTERVAL_MS = 5000;
    var LS_ROOM = 'lchat_room_id';
    var LS_VISITOR = 'lchat_visitor_id';
    var LS_NAME = 'lchat_visitor_name';

    var script = document.currentScript;
    var cfg = {
        apiKey: script?.getAttribute('data-api-key') || '',
        reverbKey: script?.getAttribute('data-reverb-key') || '',
        reverbHost: script?.getAttribute('data-reverb-host') || window.location.hostname,
        reverbPort: parseInt(script?.getAttribute('data-reverb-port') || '443', 10),
        baseUrl: script?.src ? new URL(script.src).origin : '',
    };

    /* ── State ──────────────────────────────────────────────── */
    var state = {
        open: false,
        roomId: localStorage.getItem(LS_ROOM) || null,
        visitorId: localStorage.getItem(LS_VISITOR) || null,
        visitorName: localStorage.getItem(LS_NAME) || '',
        messages: [],
        unread: 0,
        echoChannel: null,
        pollTimer: null,
        lastTypingSent: 0,
        userScrolled: false,
        darkMode: false,
        typingTimeout: null,
        showTyping: false,
    };

    /* ── Dark Mode Detection ───────────────────────────────── */
    var mql = window.matchMedia('(prefers-color-scheme: dark)');
    state.darkMode = mql.matches;
    mql.addEventListener('change', function (e) {
        state.darkMode = e.matches;
        applyDarkMode();
    });

    /* ── Styles ─────────────────────────────────────────────── */
    function injectStyles() {
        var style = document.createElement('style');
        style.textContent = '\n' +
            /* Reset & base */
            '.lchat-widget,.lchat-widget *,.lchat-widget *::before,.lchat-widget *::after{box-sizing:border-box;margin:0;padding:0;font-family:system-ui,-apple-system,sans-serif;}\n' +

            /* Bubble */
            '.lchat-bubble{position:fixed;bottom:24px;right:24px;width:60px;height:60px;border-radius:50%;background:#4F46E5;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:999999;box-shadow:0 4px 14px rgba(79,70,229,.45);transition:transform .2s ease,box-shadow .2s ease;}\n' +
            '.lchat-bubble:hover{transform:scale(1.1);box-shadow:0 6px 20px rgba(79,70,229,.55);}\n' +
            '.lchat-bubble svg{width:28px;height:28px;fill:#fff;}\n' +
            '.lchat-badge{position:absolute;top:-4px;right:-4px;min-width:20px;height:20px;border-radius:10px;background:#EF4444;color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 5px;line-height:1;}\n' +
            '.lchat-badge.lchat-hidden{display:none;}\n' +

            /* Pulse animation */
            '@keyframes lchat-pulse{0%{box-shadow:0 0 0 0 rgba(79,70,229,.5);}70%{box-shadow:0 0 0 12px rgba(79,70,229,0);}100%{box-shadow:0 0 0 0 rgba(79,70,229,0);}}\n' +
            '.lchat-bubble-pulse{animation:lchat-pulse .8s ease-out;}\n' +

            /* Panel */
            '.lchat-backdrop{display:none;position:fixed;top:0;left:0;right:0;bottom:0;width:100%;height:200vh;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);z-index:999998;touch-action:none;}\n' +
            '.lchat-backdrop.lchat-show{display:block;}\n' +
            '.lchat-panel{position:fixed;bottom:96px;right:24px;width:380px;height:520px;background:#fff;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,.18);z-index:999999;display:flex;flex-direction:column;overflow:hidden;transform:translateY(20px);opacity:0;transition:transform .25s ease,opacity .25s ease;pointer-events:none;}\n' +
            '.lchat-panel.lchat-open{transform:translateY(0);opacity:1;pointer-events:auto;}\n' +

            /* Header */
            '.lchat-header{background:#4F46E5;color:#fff;padding:16px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}\n' +
            '.lchat-header-left{display:flex;align-items:center;gap:8px;}\n' +
            '.lchat-header-title{font-size:15px;font-weight:600;}\n' +
            '.lchat-status-dot{width:8px;height:8px;border-radius:50%;background:#34D399;flex-shrink:0;}\n' +
            '.lchat-close-btn{background:none;border:none;color:#fff;cursor:pointer;padding:4px;border-radius:4px;display:flex;align-items:center;justify-content:center;}\n' +
            '.lchat-close-btn:hover{background:rgba(255,255,255,.15);}\n' +
            '.lchat-close-btn svg{width:20px;height:20px;fill:#fff;}\n' +

            /* Messages area */
            '.lchat-messages{flex:1;overflow-y:auto;padding:16px;background:#F9FAFB;display:flex;flex-direction:column;gap:8px;scrollbar-width:none;-ms-overflow-style:none;}\n' +
            '.lchat-messages::-webkit-scrollbar{display:none;}\n' +
            '.lchat-msg{max-width:78%;padding:10px 14px;border-radius:14px;font-size:14px;line-height:1.45;word-break:break-word;}\n' +
            '.lchat-msg-visitor{align-self:flex-end;background:#4F46E5;color:#fff;border-bottom-right-radius:4px;}\n' +
            '.lchat-msg-agent{align-self:flex-start;background:#E5E7EB;color:#1F2937;border-bottom-left-radius:4px;}\n' +
            '.lchat-msg-system{align-self:center;font-size:12px;color:#9CA3AF;font-style:italic;background:none;padding:4px 8px;}\n' +
            '.lchat-msg-time{font-size:11px;margin-top:4px;opacity:.6;}\n' +
            '.lchat-msg-name{font-size:11px;font-weight:600;margin-bottom:2px;opacity:.7;}\n' +

            /* Typing indicator */
            '.lchat-typing{align-self:flex-start;display:flex;align-items:center;gap:4px;padding:10px 14px;background:#E5E7EB;border-radius:14px;border-bottom-left-radius:4px;}\n' +
            '.lchat-typing.lchat-hidden{display:none;}\n' +
            '.lchat-typing-dot{width:7px;height:7px;background:#9CA3AF;border-radius:50%;}\n' +
            '@keyframes lchat-bounce{0%,60%,100%{transform:translateY(0);}30%{transform:translateY(-6px);}}\n' +
            '.lchat-typing-dot:nth-child(1){animation:lchat-bounce 1.2s infinite .0s;}\n' +
            '.lchat-typing-dot:nth-child(2){animation:lchat-bounce 1.2s infinite .15s;}\n' +
            '.lchat-typing-dot:nth-child(3){animation:lchat-bounce 1.2s infinite .3s;}\n' +

            /* Input area */
            '.lchat-input-area{padding:12px;border-top:1px solid #E5E7EB;display:flex;align-items:flex-end;gap:8px;background:#fff;flex-shrink:0;}\n' +
            '.lchat-textarea{flex:1;border:1px solid #D1D5DB;border-radius:10px;padding:10px 12px;font-size:14px;line-height:1.4;min-height:20px;max-height:100px;outline:none;background:#fff;color:#1F2937;overflow-y:auto;scrollbar-width:none;-ms-overflow-style:none;-webkit-user-select:text;user-select:text;word-break:break-word;white-space:pre-wrap;}\n' +
            '.lchat-textarea::-webkit-scrollbar{display:none;}\n' +
            '.lchat-textarea:focus{border-color:#4F46E5;box-shadow:0 0 0 2px rgba(79,70,229,.15);}\n' +
            '.lchat-textarea:empty::before{content:attr(data-placeholder);color:#9CA3AF;pointer-events:none;}\n' +
            '.lchat-textarea br.lchat-placeholder-br{display:none;}\n' +
            '.lchat-send-btn{width:38px;height:38px;border-radius:10px;border:none;background:#4F46E5;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .15s;}\n' +
            '.lchat-send-btn:hover{background:#4338CA;}\n' +
            '.lchat-send-btn:disabled{background:#A5B4FC;cursor:not-allowed;}\n' +
            '.lchat-send-btn svg{width:18px;height:18px;fill:#fff;}\n' +

            /* Pre-chat form */
            '.lchat-prechat{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:32px;gap:16px;background:#F9FAFB;}\n' +
            '.lchat-prechat-title{font-size:18px;font-weight:600;color:#1F2937;}\n' +
            '.lchat-prechat-desc{font-size:14px;color:#6B7280;text-align:center;}\n' +
            '.lchat-prechat input{width:100%;padding:10px 14px;border:1px solid #D1D5DB;border-radius:10px;font-size:14px;outline:none;background:#fff;color:#1F2937;}\n' +
            '.lchat-prechat input:focus{border-color:#4F46E5;box-shadow:0 0 0 2px rgba(79,70,229,.15);}\n' +
            '.lchat-prechat-btn{width:100%;padding:10px;border:none;border-radius:10px;background:#4F46E5;color:#fff;font-size:15px;font-weight:600;cursor:pointer;transition:background .15s;}\n' +
            '.lchat-prechat-btn:hover{background:#4338CA;}\n' +
            '.lchat-prechat-btn:disabled{background:#A5B4FC;cursor:not-allowed;}\n' +

            /* Dark mode */
            '.lchat-dark .lchat-panel{background:#1F2937;}\n' +
            '.lchat-dark .lchat-messages{background:#111827;}\n' +
            '.lchat-dark .lchat-msg-agent{background:#374151;color:#F3F4F6;}\n' +
            '.lchat-dark .lchat-msg-system{color:#6B7280;}\n' +
            '.lchat-dark .lchat-typing{background:#374151;}\n' +
            '.lchat-dark .lchat-typing-dot{background:#6B7280;}\n' +
            '.lchat-dark .lchat-input-area{background:#1F2937;border-top-color:#374151;}\n' +
            '.lchat-dark .lchat-textarea{background:#374151;color:#F3F4F6;border-color:#4B5563;}\n' +
            '.lchat-dark .lchat-textarea::placeholder{color:#6B7280;}\n' +
            '.lchat-dark .lchat-prechat{background:#111827;}\n' +
            '.lchat-dark .lchat-prechat-title{color:#F3F4F6;}\n' +
            '.lchat-dark .lchat-prechat-desc{color:#9CA3AF;}\n' +
            '.lchat-dark .lchat-prechat input{background:#374151;color:#F3F4F6;border-color:#4B5563;}\n' +
            '.lchat-dark .lchat-header{background:#3730A3;}\n' +

            /* Mobile responsive */
            '@media(max-width:639px){\n' +
            '  .lchat-panel{bottom:0;right:0;left:0;top:0;width:100%;height:100%;border-radius:0;overscroll-behavior:none;}\n' +
            '  .lchat-bubble{bottom:16px;right:16px;}\n' +
            '  .lchat-textarea,.lchat-prechat input{font-size:16px !important;}\n' +
            '}\n';

        document.head.appendChild(style);
    }

    /* ── SVG Icons ──────────────────────────────────────────── */
    var ICON_CHAT = '<svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.2L4 17.2V4h16v12z"/><path d="M7 9h10v2H7zm0-3h10v2H7z"/></svg>';
    var ICON_CLOSE = '<svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';
    var ICON_SEND = '<svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>';

    /* ── DOM Construction ───────────────────────────────────── */
    var root, bubble, badge, panel, backdrop, header, messagesEl, typingEl, inputArea, textarea, sendBtn, prechatEl, prechatInput, prechatBtn;

    function buildDOM() {
        root = document.createElement('div');
        root.className = 'lchat-widget';

        /* Bubble */
        bubble = document.createElement('div');
        bubble.className = 'lchat-bubble';
        bubble.innerHTML = ICON_CHAT;
        bubble.setAttribute('aria-label', '채팅 열기');
        badge = document.createElement('div');
        badge.className = 'lchat-badge lchat-hidden';
        badge.textContent = '0';
        bubble.appendChild(badge);
        root.appendChild(bubble);

        /* Backdrop (mobile: covers background behind keyboard) */
        backdrop = document.createElement('div');
        backdrop.className = 'lchat-backdrop';
        root.appendChild(backdrop);

        /* Panel */
        panel = document.createElement('div');
        panel.className = 'lchat-panel';

        /* Header */
        header = document.createElement('div');
        header.className = 'lchat-header';
        var headerLeft = document.createElement('div');
        headerLeft.className = 'lchat-header-left';
        var statusDot = document.createElement('div');
        statusDot.className = 'lchat-status-dot';
        var title = document.createElement('div');
        title.className = 'lchat-header-title';
        title.textContent = '실시간 채팅';
        headerLeft.appendChild(statusDot);
        headerLeft.appendChild(title);
        var closeBtn = document.createElement('button');
        closeBtn.className = 'lchat-close-btn';
        closeBtn.innerHTML = ICON_CLOSE;
        closeBtn.setAttribute('aria-label', '닫기');
        header.appendChild(headerLeft);
        header.appendChild(closeBtn);
        panel.appendChild(header);

        /* Pre-chat form */
        prechatEl = document.createElement('div');
        prechatEl.className = 'lchat-prechat';
        var prechatTitle = document.createElement('div');
        prechatTitle.className = 'lchat-prechat-title';
        prechatTitle.textContent = '채팅 시작하기';
        var prechatDesc = document.createElement('div');
        prechatDesc.className = 'lchat-prechat-desc';
        prechatDesc.textContent = '이름을 입력하시면 상담이 시작됩니다.';
        prechatInput = document.createElement('input');
        prechatInput.type = 'text';
        prechatInput.placeholder = '이름을 입력하세요';
        prechatInput.maxLength = 50;
        prechatInput.setAttribute('autocomplete', 'one-time-code');
        prechatInput.setAttribute('enterkeyhint', 'go');
        prechatInput.setAttribute('data-form-type', 'other');
        prechatInput.setAttribute('data-lpignore', 'true');
        prechatBtn = document.createElement('button');
        prechatBtn.className = 'lchat-prechat-btn';
        prechatBtn.textContent = '시작하기';
        prechatEl.appendChild(prechatTitle);
        prechatEl.appendChild(prechatDesc);
        prechatEl.appendChild(prechatInput);
        prechatEl.appendChild(prechatBtn);
        panel.appendChild(prechatEl);

        /* Messages area */
        messagesEl = document.createElement('div');
        messagesEl.className = 'lchat-messages lchat-hidden';

        /* Typing indicator */
        typingEl = document.createElement('div');
        typingEl.className = 'lchat-typing lchat-hidden';
        for (var i = 0; i < 3; i++) {
            var dot = document.createElement('div');
            dot.className = 'lchat-typing-dot';
            typingEl.appendChild(dot);
        }

        panel.appendChild(messagesEl);

        /* Input area */
        inputArea = document.createElement('div');
        inputArea.className = 'lchat-input-area lchat-hidden';
        textarea = document.createElement('div');
        textarea.className = 'lchat-textarea';
        textarea.setAttribute('contenteditable', 'true');
        textarea.setAttribute('role', 'textbox');
        textarea.setAttribute('aria-label', '메시지 입력');
        textarea.setAttribute('data-placeholder', '메시지를 입력하세요...');
        textarea.setAttribute('enterkeyhint', 'send');
        sendBtn = document.createElement('button');
        sendBtn.className = 'lchat-send-btn';
        sendBtn.innerHTML = ICON_SEND;
        sendBtn.setAttribute('aria-label', '보내기');
        inputArea.appendChild(textarea);
        inputArea.appendChild(sendBtn);
        panel.appendChild(inputArea);

        root.appendChild(panel);
        document.body.appendChild(root);

        applyDarkMode();
    }

    function applyDarkMode() {
        if (!root) return;
        if (state.darkMode) {
            root.classList.add('lchat-dark');
        } else {
            root.classList.remove('lchat-dark');
        }
    }

    /* ── UI Helpers ─────────────────────────────────────────── */
    function showChat() {
        prechatEl.style.display = 'none';
        messagesEl.classList.remove('lchat-hidden');
        messagesEl.appendChild(typingEl);
        inputArea.classList.remove('lchat-hidden');
    }

    function showPrechat() {
        prechatEl.style.display = 'flex';
        messagesEl.classList.add('lchat-hidden');
        inputArea.classList.add('lchat-hidden');
    }

    function togglePanel() {
        state.open = !state.open;
        if (state.open) {
            panel.classList.add('lchat-open');
            if (isMobile()) {
                state.savedScrollY = window.scrollY;
                document.documentElement.style.overflow = 'hidden';
                document.body.style.overflow = 'hidden';
                document.body.style.position = 'fixed';
                document.body.style.top = '-' + state.savedScrollY + 'px';
                document.body.style.left = '0';
                document.body.style.right = '0';
                document.body.style.width = '100%';
                document.addEventListener('touchstart', trackTouchStart, { passive: true });
                document.addEventListener('touchmove', preventBgScroll, { passive: false });
                backdrop.classList.add('lchat-show');
                updatePanelLayout();
            }
            if (state.visitorName && state.roomId) {
                showChat();
                loadMessages();
            } else if (state.visitorName) {
                createRoom().then(function () { showChat(); loadMessages(); }).catch(logError);
            } else {
                showPrechat();
            }
            state.unread = 0;
            updateBadge();
            scrollToBottom(true);
            if (!isMobile()) textarea.focus();
        } else {
            panel.classList.remove('lchat-open');
            if (isMobile()) {
                document.removeEventListener('touchstart', trackTouchStart);
                document.removeEventListener('touchmove', preventBgScroll);
                document.documentElement.style.overflow = '';
                document.body.style.overflow = '';
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.left = '';
                document.body.style.right = '';
                document.body.style.width = '';
                window.scrollTo(0, state.savedScrollY || 0);
                backdrop.classList.remove('lchat-show');
                resetPanelLayout();
            }
        }
    }

    function updateBadge() {
        if (state.unread > 0 && !state.open) {
            badge.textContent = state.unread > 99 ? '99+' : String(state.unread);
            badge.classList.remove('lchat-hidden');
        } else {
            badge.classList.add('lchat-hidden');
        }
    }

    function pulseBubble() {
        bubble.classList.remove('lchat-bubble-pulse');
        void bubble.offsetWidth; /* force reflow */
        bubble.classList.add('lchat-bubble-pulse');
    }

    function scrollToBottom(force) {
        if (!messagesEl) return;
        if (force || !state.userScrolled) {
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }
    }

    function formatTime(dateStr) {
        try {
            var d = new Date(dateStr);
            return d.toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' });
        } catch (_) {
            return '';
        }
    }

    /* ── Render Messages ───────────────────────────────────── */
    function renderMessages() {
        /* Keep typing indicator reference */
        if (typingEl.parentNode === messagesEl) {
            messagesEl.removeChild(typingEl);
        }
        messagesEl.innerHTML = '';

        state.messages.forEach(function (msg) {
            var wrapper = document.createElement('div');
            var isVisitor = msg.sender_type === 'visitor';
            var isSystem = msg.sender_type === 'system' || msg.content_type === 'system';

            if (isSystem) {
                wrapper.className = 'lchat-msg lchat-msg-system';
                wrapper.textContent = msg.content;
            } else {
                wrapper.className = 'lchat-msg ' + (isVisitor ? 'lchat-msg-visitor' : 'lchat-msg-agent');
                if (!isVisitor && msg.sender_name) {
                    var nameEl = document.createElement('div');
                    nameEl.className = 'lchat-msg-name';
                    nameEl.textContent = msg.sender_name;
                    wrapper.appendChild(nameEl);
                }
                var contentEl = document.createElement('div');
                contentEl.textContent = msg.content;
                wrapper.appendChild(contentEl);
                if (msg.created_at) {
                    var timeEl = document.createElement('div');
                    timeEl.className = 'lchat-msg-time';
                    timeEl.textContent = formatTime(msg.created_at);
                    wrapper.appendChild(timeEl);
                }
            }

            messagesEl.appendChild(wrapper);
        });

        messagesEl.appendChild(typingEl);
        scrollToBottom(false);
    }

    function appendMessage(msg) {
        /* Deduplicate */
        for (var i = 0; i < state.messages.length; i++) {
            if (state.messages[i].id && state.messages[i].id === msg.id) return;
        }
        state.messages.push(msg);

        var wrapper = document.createElement('div');
        var isVisitor = msg.sender_type === 'visitor';
        var isSystem = msg.sender_type === 'system' || msg.content_type === 'system';

        if (isSystem) {
            wrapper.className = 'lchat-msg lchat-msg-system';
            wrapper.textContent = msg.content;
        } else {
            wrapper.className = 'lchat-msg ' + (isVisitor ? 'lchat-msg-visitor' : 'lchat-msg-agent');
            if (!isVisitor && msg.sender_name) {
                var nameEl = document.createElement('div');
                nameEl.className = 'lchat-msg-name';
                nameEl.textContent = msg.sender_name;
                wrapper.appendChild(nameEl);
            }
            var contentEl = document.createElement('div');
            contentEl.textContent = msg.content;
            wrapper.appendChild(contentEl);
            if (msg.created_at) {
                var timeEl = document.createElement('div');
                timeEl.className = 'lchat-msg-time';
                timeEl.textContent = formatTime(msg.created_at);
                wrapper.appendChild(timeEl);
            }
        }

        /* Insert before typing indicator */
        messagesEl.insertBefore(wrapper, typingEl);
        scrollToBottom(false);
    }

    /* ── API Calls ─────────────────────────────────────────── */
    function apiHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-API-Key': cfg.apiKey,
        };
    }

    function createRoom() {
        return fetch(cfg.baseUrl + '/api/rooms', {
            method: 'POST',
            headers: apiHeaders(),
            body: JSON.stringify({ visitor_name: state.visitorName }),
        })
        .then(function (r) {
            if (!r.ok) throw new Error('Create room failed: ' + r.status);
            return r.json();
        })
        .then(function (json) {
            var data = json.data || json;
            state.roomId = String(data.id);
            state.visitorId = String(data.visitor_id || '');
            localStorage.setItem(LS_ROOM, state.roomId);
            localStorage.setItem(LS_VISITOR, state.visitorId);
            connectRealtime();
        });
    }

    function loadMessages() {
        if (!state.roomId) return;
        fetch(cfg.baseUrl + '/api/rooms/' + state.roomId + '/messages?limit=50', {
            headers: { 'Accept': 'application/json', 'X-API-Key': cfg.apiKey },
        })
        .then(function (r) {
            if (!r.ok) throw new Error('Get messages failed: ' + r.status);
            return r.json();
        })
        .then(function (json) {
            var raw = json.data || json;
            var list = Array.isArray(raw) ? raw : (Array.isArray(raw.data) ? raw.data : []);
            state.messages = list;
            renderMessages();
            scrollToBottom(true);
        })
        .catch(logError);
    }

    function getTextareaValue() {
        return (textarea.innerText || textarea.textContent || '').trim();
    }

    function clearTextarea() {
        textarea.innerHTML = '';
    }

    function sendMessage() {
        var content = getTextareaValue();
        if (!content || !state.roomId) return;

        clearTextarea();
        sendBtn.disabled = true;

        var tempMsg = {
            id: 'temp-' + Date.now(),
            sender_type: 'visitor',
            sender_name: state.visitorName,
            content: content,
            content_type: 'text',
            created_at: new Date().toISOString(),
        };
        appendMessage(tempMsg);

        fetch(cfg.baseUrl + '/api/rooms/' + state.roomId + '/messages', {
            method: 'POST',
            headers: apiHeaders(),
            body: JSON.stringify({
                sender_type: 'visitor',
                sender_name: state.visitorName,
                content: content,
                content_type: 'text',
            }),
        })
        .then(function (r) {
            if (!r.ok) throw new Error('Send failed: ' + r.status);
            return r.json();
        })
        .then(function (json) {
            /* Replace temp with real message */
            var real = json.data || json;
            for (var i = 0; i < state.messages.length; i++) {
                if (state.messages[i].id === tempMsg.id) {
                    state.messages[i] = real;
                    break;
                }
            }
        })
        .catch(function (err) {
            logError(err);
            appendSystemMsg('메시지 전송에 실패했습니다.');
        })
        .finally(function () {
            sendBtn.disabled = false;
            textarea.focus();
        });
    }

    function sendTypingIndicator() {
        var now = Date.now();
        if (now - state.lastTypingSent < TYPING_DEBOUNCE_MS) return;
        state.lastTypingSent = now;

        if (!state.roomId) return;
        fetch(cfg.baseUrl + '/api/rooms/' + state.roomId + '/typing', {
            method: 'POST',
            headers: apiHeaders(),
            body: JSON.stringify({
                sender_type: 'visitor',
                sender_name: state.visitorName,
            }),
        }).catch(function () { /* silent */ });
    }

    function appendSystemMsg(text) {
        appendMessage({
            id: 'sys-' + Date.now(),
            sender_type: 'system',
            content: text,
            content_type: 'system',
            created_at: new Date().toISOString(),
        });
    }

    /* ── Textarea Auto-resize ──────────────────────────────── */
    function autoResizeTextarea() {
        /* contenteditable auto-resizes naturally via min/max-height CSS */
    }

    /* ── WebSocket / Polling ────────────────────────────────── */
    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            if (document.querySelector('script[src="' + src + '"]')) {
                resolve();
                return;
            }
            var s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    function connectRealtime() {
        if (!state.roomId) return;

        if (cfg.reverbKey) {
            connectEcho().catch(function () {
                startPolling();
            });
        } else {
            startPolling();
        }
    }

    function connectEcho() {
        return loadScript(PUSHER_CDN)
            .then(function () { return loadScript(ECHO_CDN); })
            .then(function () {
                var echo = new window.Echo({
                    broadcaster: 'reverb',
                    key: cfg.reverbKey,
                    wsHost: cfg.reverbHost,
                    wsPort: cfg.reverbPort,
                    wssPort: cfg.reverbPort,
                    forceTLS: cfg.reverbPort === 443,
                    enabledTransports: ['ws', 'wss'],
                    authEndpoint: cfg.baseUrl + '/api/broadcasting/auth',
                    auth: {
                        headers: { 'X-API-Key': cfg.apiKey },
                    },
                });

                state.echoChannel = echo.private('chat.' + state.roomId);

                state.echoChannel.listen('.message.sent', function (e) {
                    var msg = e.message || e;
                    if (msg.sender_type === 'visitor' && String(msg.sender_name) === state.visitorName) return;
                    appendMessage(msg);
                    if (!state.open) {
                        state.unread++;
                        updateBadge();
                        pulseBubble();
                    }
                });

                state.echoChannel.listen('.typing.started', function (e) {
                    var data = e.data || e;
                    if (data.sender_type === 'visitor') return;
                    showTypingIndicator();
                });

                echo.connector.pusher.connection.bind('disconnected', function () {
                    startPolling();
                });
            });
    }

    function startPolling() {
        if (state.pollTimer) return;
        state.pollTimer = setInterval(function () {
            if (!state.roomId) return;
            fetch(cfg.baseUrl + '/api/rooms/' + state.roomId + '/messages?limit=50', {
                headers: { 'Accept': 'application/json', 'X-API-Key': cfg.apiKey },
            })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (json) {
                if (!json) return;
                var raw = json.data || json;
                var list = Array.isArray(raw) ? raw : (Array.isArray(raw.data) ? raw.data : []);
                if (!list.length) return;

                var prevLen = state.messages.length;
                var existingIds = {};
                state.messages.forEach(function (m) { if (m.id) existingIds[m.id] = true; });

                var newMsgs = list.filter(function (m) {
                    return m.id && !existingIds[m.id] && !(String(m.id).indexOf('temp-') === 0);
                });

                if (newMsgs.length > 0) {
                    /* Full refresh to stay in sync */
                    state.messages = list;
                    renderMessages();

                    if (!state.open) {
                        state.unread += newMsgs.filter(function (m) { return m.sender_type !== 'visitor'; }).length;
                        updateBadge();
                        pulseBubble();
                    }
                }
            })
            .catch(function () { /* silent */ });
        }, POLL_INTERVAL_MS);
    }

    function stopPolling() {
        if (state.pollTimer) {
            clearInterval(state.pollTimer);
            state.pollTimer = null;
        }
    }

    function showTypingIndicator() {
        state.showTyping = true;
        typingEl.classList.remove('lchat-hidden');
        scrollToBottom(false);
        clearTimeout(state.typingTimeout);
        state.typingTimeout = setTimeout(function () {
            state.showTyping = false;
            typingEl.classList.add('lchat-hidden');
        }, 3000);
    }

    /* ── Mobile Viewport / Keyboard Handling ────────────────── */
    function isMobile() {
        return window.innerWidth < 640;
    }

    function preventBgScroll(e) {
        /* Allow scrolling inside messages area only */
        var el = e.target;
        while (el && el !== document.body && el !== document.documentElement) {
            if (el === messagesEl) {
                /* Allow only if there's actually overflow to scroll */
                var atTop = messagesEl.scrollTop <= 0;
                var atBottom = messagesEl.scrollTop + messagesEl.clientHeight >= messagesEl.scrollHeight - 1;
                if (e.touches && e.touches.length === 1) {
                    var touch = e.touches[0];
                    var dy = touch.clientY - (state._lastTouchY || touch.clientY);
                    state._lastTouchY = touch.clientY;
                    /* Block if at edge and trying to scroll further */
                    if ((atTop && dy > 0) || (atBottom && dy < 0)) {
                        e.preventDefault();
                    }
                }
                return;
            }
            if (el === panel) {
                /* Inside panel but not messages — block scroll */
                e.preventDefault();
                return;
            }
            el = el.parentElement;
        }
        /* Outside panel — always block */
        e.preventDefault();
    }

    function trackTouchStart(e) {
        if (e.touches && e.touches.length === 1) {
            state._lastTouchY = e.touches[0].clientY;
        }
    }

    var rafPending = false;

    function updatePanelLayout() {
        if (!isMobile() || !state.open) return;

        var vv = window.visualViewport;
        if (!vv) return;

        /* Position panel to match the visual viewport exactly */
        var top = vv.offsetTop;
        var h = vv.height;
        panel.style.cssText = 'position:fixed;top:' + top + 'px;left:0;right:0;bottom:auto;height:' + h + 'px;width:100%;border-radius:0;transition:none;';
    }

    function resetPanelLayout() {
        panel.style.cssText = '';
    }

    function scheduleLayout() {
        if (rafPending) return;
        rafPending = true;
        requestAnimationFrame(function () {
            rafPending = false;
            updatePanelLayout();
        });
    }

    function setupMobileKeyboard() {
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', scheduleLayout);
            window.visualViewport.addEventListener('scroll', scheduleLayout);
        }
    }

    /* ── Event Handlers ────────────────────────────────────── */
    function bindEvents() {
        /* Bubble click */
        bubble.addEventListener('click', togglePanel);

        /* Close button */
        panel.querySelector('.lchat-close-btn').addEventListener('click', function (e) {
            e.stopPropagation();
            togglePanel();
        });

        /* Pre-chat form */
        prechatBtn.addEventListener('click', handlePrechat);
        prechatInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handlePrechat();
            }
        });

        /* Send button */
        sendBtn.addEventListener('click', function () { sendMessage(); });

        /* Textarea: Enter to send, Shift+Enter for newline */
        textarea.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
                e.preventDefault();
                sendMessage();
            }
        });

        /* Prevent paste from inserting HTML (plain text only) */
        textarea.addEventListener('paste', function (e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, text);
        });

        /* Typing indicator debounce */
        textarea.addEventListener('input', function () {
            sendTypingIndicator();
        });

        /* Mobile keyboard: reposition panel on focus */
        textarea.addEventListener('focus', function () {
            if (isMobile()) {
                setTimeout(scheduleLayout, 100);
            }
        });

        /* Scroll detection */
        messagesEl.addEventListener('scroll', function () {
            var threshold = 40;
            var atBottom = messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < threshold;
            state.userScrolled = !atBottom;
        });
    }

    function handlePrechat() {
        var name = prechatInput.value.trim();
        if (!name) {
            prechatInput.focus();
            return;
        }

        prechatBtn.disabled = true;
        state.visitorName = name;
        localStorage.setItem(LS_NAME, name);

        createRoom()
            .then(function () {
                showChat();
                loadMessages();
                if (window.innerWidth >= 640) textarea.focus();
            })
            .catch(function (err) {
                logError(err);
                appendSystemMsg('연결에 실패했습니다. 다시 시도해주세요.');
                prechatBtn.disabled = false;
            });
    }

    /* ── Logging ────────────────────────────────────────────── */
    function logError(err) {
        console.error('[LiveChat]', err);
    }

    /* ── Initialization ────────────────────────────────────── */
    function init() {
        injectStyles();
        buildDOM();
        bindEvents();
        setupMobileKeyboard();

        /* Restore existing session */
        if (state.visitorName && state.roomId) {
            connectRealtime();
        }
    }

    /* Wait for DOM ready */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /* ── Public API ─────────────────────────────────────────── */
    window.LiveChat = {
        open: function () { if (!state.open) togglePanel(); },
        close: function () { if (state.open) togglePanel(); },
        destroy: function () {
            stopPolling();
            if (root && root.parentNode) root.parentNode.removeChild(root);
        },
    };
})();
