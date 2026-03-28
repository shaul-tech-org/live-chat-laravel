/**
 * Live Chat Widget — Floating Bubble UI
 *
 * Usage:
 *   <script src="https://chat.shaul.kr/js/widget.js"
 *           data-api-key="YOUR_API_KEY"
 *           data-reverb-key="YOUR_REVERB_KEY"
 *           data-reverb-host="chat.shaul.kr"
 *           data-reverb-port="443"
 *           data-lang="ko"
 *           data-color="#4F46E5"
 *           data-position="bottom-right"
 *           data-title="실시간 채팅"
 *           data-greeting="무엇을 도와드릴까요?"
 *           data-logo="https://example.com/logo.png"></script>
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
    var LS_EMAIL = 'lchat_visitor_email';

    var script = document.currentScript;
    var cfg = {
        apiKey: script?.getAttribute('data-api-key') || '',
        reverbKey: script?.getAttribute('data-reverb-key') || '',
        reverbHost: script?.getAttribute('data-reverb-host') || window.location.hostname,
        reverbPort: parseInt(script?.getAttribute('data-reverb-port') || '443', 10),
        baseUrl: script?.src ? new URL(script.src).origin : '',
    };

    /* ── Branding Config ──────────────────────────────────────── */
    var DEFAULT_COLOR = '#4F46E5';
    var branding = {
        color: script?.getAttribute('data-color') || DEFAULT_COLOR,
        position: script?.getAttribute('data-position') || 'bottom-right',
        title: script?.getAttribute('data-title') || '',
        greeting: script?.getAttribute('data-greeting') || '',
        logo: script?.getAttribute('data-logo') || '',
    };

    /* Compute a darker hover shade from the primary color */
    function darkenColor(hex, amount) {
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        var r = Math.max(0, parseInt(hex.substring(0,2), 16) - amount);
        var g = Math.max(0, parseInt(hex.substring(2,4), 16) - amount);
        var b = Math.max(0, parseInt(hex.substring(4,6), 16) - amount);
        return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
    }

    function hexToRgba(hex, alpha) {
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        var r = parseInt(hex.substring(0,2), 16);
        var g = parseInt(hex.substring(2,4), 16);
        var b = parseInt(hex.substring(4,6), 16);
        return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
    }

    var isLeftPosition = branding.position === 'bottom-left';
    var positionSide = isLeftPosition ? 'left' : 'right';

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
        connectionStatus: 'offline', /* 'online' | 'reconnecting' | 'offline' */
        agentsOnline: null, /* null = unknown, 0 = offline, >0 = online */
        visitorEmail: localStorage.getItem(LS_EMAIL) || '',
        lang: 'ko',
    };

    /* ── i18n ──────────────────────────────────────────────── */
    var LANGS = {
        ko: {
            title: '실시간 채팅', greeting: '무엇을 도와드릴까요?',
            greetingDesc: '궁금한 점을 선택하거나 이름을 입력해 상담을 시작하세요.',
            responseHint: '보통 2분 이내 응답', nameInput: '이름 입력',
            startBtn: '시작', msgPlaceholder: '메시지를 입력하세요...',
            topicGeneral: '일반 문의', topicQuote: '견적 요청', topicSupport: '기술 지원',
            or: '또는', offline: '오프라인', reconnecting: '재연결 중...',
            offlineTitle: '현재 상담사가 부재중입니다',
            offlineDesc: '이메일과 메시지를 남겨주시면\n빠른 시간 내 답변드리겠습니다.',
            offlineEmail: '이메일 주소 (필수)', offlineMsg: '메시지를 입력하세요...',
            offlineSubmit: '메시지 남기기',
            offlineSuccess: '메시지가 전송되었습니다',
            offlineSuccessDesc: '빠른 시간 내 {email}으로 답변드리겠습니다.',
            offlineError: '전송에 실패했습니다. 다시 시도해주세요.',
            openChat: '채팅 열기', close: '닫기',
            attachFile: '파일 첨부', sendMsg: '보내기',
            uploading: '업로드 중...', uploadPct: '업로드 중... {pct}%',
            cancel: '취소', dropFile: '파일을 놓아주세요',
            image: '이미지', file: '파일',
            sendFailed: '메시지 전송에 실패했습니다.',
            fileSizeLimit: '파일 크기는 10MB를 초과할 수 없습니다.',
            uploadResponseError: '파일 업로드 응답을 처리할 수 없습니다.',
            uploadFailed: '파일 업로드에 실패했습니다.',
            uploadError: '파일 업로드 중 오류가 발생했습니다.',
            fileMsgFailed: '파일 메시지 전송에 실패했습니다.',
            connectFailed: '연결에 실패했습니다. 다시 시도해주세요.',
            visitor: '방문자',
        },
        en: {
            title: 'Live Chat', greeting: 'How can we help?',
            greetingDesc: 'Choose a topic or enter your name to start.',
            responseHint: 'Usually responds in 2 min', nameInput: 'Your name',
            startBtn: 'Start', msgPlaceholder: 'Type a message...',
            topicGeneral: 'General', topicQuote: 'Quote', topicSupport: 'Support',
            or: 'or', offline: 'Offline', reconnecting: 'Reconnecting...',
            offlineTitle: 'We are currently offline',
            offlineDesc: 'Leave your email and message,\nand we\'ll get back to you shortly.',
            offlineEmail: 'Email (required)', offlineMsg: 'Your message...',
            offlineSubmit: 'Leave a message',
            offlineSuccess: 'Message sent',
            offlineSuccessDesc: 'We\'ll reply to {email} shortly.',
            offlineError: 'Failed to send. Please try again.',
            openChat: 'Open chat', close: 'Close',
            attachFile: 'Attach file', sendMsg: 'Send',
            uploading: 'Uploading...', uploadPct: 'Uploading... {pct}%',
            cancel: 'Cancel', dropFile: 'Drop file here',
            image: 'Image', file: 'File',
            sendFailed: 'Failed to send message.',
            fileSizeLimit: 'File size cannot exceed 10MB.',
            uploadResponseError: 'Could not process upload response.',
            uploadFailed: 'File upload failed.',
            uploadError: 'An error occurred during file upload.',
            fileMsgFailed: 'Failed to send file message.',
            connectFailed: 'Connection failed. Please try again.',
            visitor: 'Visitor',
        },
        ja: {
            title: 'ライブチャット', greeting: 'お手伝いできますか？',
            greetingDesc: 'トピックを選択するか、名前を入力して開始してください。',
            responseHint: '通常2分以内に応答', nameInput: 'お名前',
            startBtn: '開始', msgPlaceholder: 'メッセージを入力...',
            topicGeneral: '一般的な問い合わせ', topicQuote: '見積もり依頼', topicSupport: '技術サポート',
            or: 'または', offline: 'オフライン', reconnecting: '再接続中...',
            offlineTitle: '現在オペレーターは不在です',
            offlineDesc: 'メールアドレスとメッセージを残していただければ\nすぐにご返信いたします。',
            offlineEmail: 'メールアドレス（必須）', offlineMsg: 'メッセージを入力してください...',
            offlineSubmit: 'メッセージを残す',
            offlineSuccess: 'メッセージが送信されました',
            offlineSuccessDesc: 'まもなく{email}にご返信いたします。',
            offlineError: '送信に失敗しました。もう一度お試しください。',
            openChat: 'チャットを開く', close: '閉じる',
            attachFile: 'ファイル添付', sendMsg: '送信',
            uploading: 'アップロード中...', uploadPct: 'アップロード中... {pct}%',
            cancel: 'キャンセル', dropFile: 'ここにファイルをドロップ',
            image: '画像', file: 'ファイル',
            sendFailed: 'メッセージの送信に失敗しました。',
            fileSizeLimit: 'ファイルサイズは10MBを超えることはできません。',
            uploadResponseError: 'アップロード応答を処理できませんでした。',
            uploadFailed: 'ファイルのアップロードに失敗しました。',
            uploadError: 'ファイルアップロード中にエラーが発生しました。',
            fileMsgFailed: 'ファイルメッセージの送信に失敗しました。',
            connectFailed: '接続に失敗しました。もう一度お試しください。',
            visitor: '訪問者',
        },
    };

    /* Detect language: data-lang attribute > navigator.language > 'ko' */
    (function detectLang() {
        var attr = script?.getAttribute('data-lang');
        if (attr && LANGS[attr]) { state.lang = attr; return; }
        var nav = (navigator.language || navigator.userLanguage || '').toLowerCase();
        if (nav.indexOf('ja') === 0) { state.lang = 'ja'; return; }
        if (nav.indexOf('en') === 0) { state.lang = 'en'; return; }
        state.lang = 'ko';
    })();

    function t(key) { return (LANGS[state.lang] || LANGS.ko)[key] || key; }

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
            '.lchat-hidden{display:none !important;}\n' +

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
            '.lchat-status-dot{width:8px;height:8px;border-radius:50%;background:#9CA3AF;flex-shrink:0;transition:background .3s ease;}\n' +
            '.lchat-status-dot.lchat-online{background:#34D399;}\n' +
            '.lchat-status-dot.lchat-reconnecting{background:#FBBF24;animation:lchat-status-pulse 1s infinite;}\n' +
            '.lchat-status-dot.lchat-offline{background:#9CA3AF;}\n' +
            '@keyframes lchat-status-pulse{0%,100%{opacity:1;}50%{opacity:.4;}}\n' +
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
            '.lchat-attach-btn{width:38px;height:38px;border-radius:10px;border:none;background:transparent;color:#6B7280;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .15s,color .15s;}\n' +
            '.lchat-attach-btn:hover{background:#F3F4F6;color:#4F46E5;}\n' +
            '.lchat-attach-btn svg{width:20px;height:20px;fill:currentColor;}\n' +
            '.lchat-send-btn{width:38px;height:38px;border-radius:10px;border:none;background:#4F46E5;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .15s;}\n' +
            '.lchat-send-btn:hover{background:#4338CA;}\n' +
            '.lchat-send-btn:disabled{background:#A5B4FC;cursor:not-allowed;}\n' +
            '.lchat-send-btn svg{width:18px;height:18px;fill:#fff;}\n' +

            /* File upload styles */
            '.lchat-upload-progress{padding:8px 12px;background:#EEF2FF;border-top:1px solid #E5E7EB;display:flex;align-items:center;gap:8px;font-size:12px;color:#4F46E5;}\n' +
            '.lchat-upload-progress-bar{flex:1;height:4px;background:#E5E7EB;border-radius:2px;overflow:hidden;}\n' +
            '.lchat-upload-progress-fill{height:100%;background:#4F46E5;border-radius:2px;transition:width .2s ease;}\n' +
            '.lchat-upload-cancel{background:none;border:none;color:#EF4444;cursor:pointer;font-size:12px;padding:2px 4px;}\n' +
            '.lchat-msg-image{max-width:200px;border-radius:8px;cursor:pointer;display:block;margin:4px 0;}\n' +
            '.lchat-msg-image:hover{opacity:.9;}\n' +
            '.lchat-msg-file{display:flex;align-items:center;gap:8px;padding:8px 12px;background:rgba(255,255,255,.15);border-radius:8px;margin:4px 0;text-decoration:none;color:inherit;font-size:13px;}\n' +
            '.lchat-msg-agent .lchat-msg-file{background:rgba(0,0,0,.06);}\n' +
            '.lchat-msg-file:hover{opacity:.8;}\n' +
            '.lchat-msg-file-icon{flex-shrink:0;width:20px;height:20px;}\n' +
            '.lchat-msg-file-info{overflow:hidden;}\n' +
            '.lchat-msg-file-name{font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}\n' +
            '.lchat-msg-file-size{font-size:11px;opacity:.6;}\n' +
            '.lchat-drag-overlay{position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(79,70,229,.1);border:2px dashed #4F46E5;border-radius:12px;display:flex;align-items:center;justify-content:center;z-index:10;pointer-events:none;}\n' +
            '.lchat-drag-overlay-text{font-size:14px;font-weight:600;color:#4F46E5;}\n' +
            '.lchat-image-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.85);z-index:9999999;display:flex;align-items:center;justify-content:center;cursor:pointer;}\n' +
            '.lchat-image-overlay img{max-width:90%;max-height:90%;border-radius:8px;}\n' +

            /* Pre-chat form */
            '.lchat-prechat{flex:1;display:flex;flex-direction:column;align-items:center;padding:32px 24px;gap:0;background:#F9FAFB;overflow-y:auto;}\n' +
            '.lchat-prechat-avatar{width:64px;height:64px;border-radius:50%;background:#EEF2FF;display:flex;align-items:center;justify-content:center;margin-top:24px;margin-bottom:12px;}\n' +
            '.lchat-prechat-avatar svg{width:32px;height:32px;fill:#4F46E5;}\n' +
            '.lchat-prechat-title{font-size:20px;font-weight:700;color:#1F2937;margin-bottom:4px;}\n' +
            '.lchat-prechat-desc{font-size:14px;color:#6B7280;text-align:center;margin-bottom:20px;}\n' +
            '.lchat-prechat-response{font-size:12px;color:#9CA3AF;margin-bottom:24px;display:flex;align-items:center;gap:4px;}\n' +
            '.lchat-prechat-response::before{content:"";width:6px;height:6px;border-radius:50%;background:#34D399;}\n' +
            '.lchat-prechat-topics{width:100%;display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:20px;}\n' +
            '.lchat-topic-btn{padding:10px 6px;border:1px solid #E5E7EB;border-radius:10px;background:#fff;color:#374151;font-size:12px;font-weight:500;cursor:pointer;text-align:center;transition:all .15s;display:flex;flex-direction:column;align-items:center;gap:4px;}\n' +
            '.lchat-topic-btn:hover{border-color:#4F46E5;background:#EEF2FF;color:#4F46E5;}\n' +
            '.lchat-topic-icon{font-size:16px;flex-shrink:0;}\n' +
            '.lchat-prechat-divider{width:100%;text-align:center;color:#9CA3AF;font-size:12px;margin:12px 0;position:relative;}\n' +
            '.lchat-prechat-divider::before,.lchat-prechat-divider::after{content:"";position:absolute;top:50%;width:calc(50% - 20px);height:1px;background:#E5E7EB;}\n' +
            '.lchat-prechat-divider::before{left:0;}\n' +
            '.lchat-prechat-divider::after{right:0;}\n' +
            '.lchat-prechat-form{width:100%;display:flex;gap:8px;}\n' +
            '.lchat-prechat input{flex:1;padding:10px 14px;border:1px solid #D1D5DB;border-radius:10px;font-size:14px;outline:none;background:#fff;color:#1F2937;}\n' +
            '.lchat-prechat input:focus{border-color:#4F46E5;box-shadow:0 0 0 2px rgba(79,70,229,.15);}\n' +
            '.lchat-prechat-btn{padding:10px 16px;border:none;border-radius:10px;background:#4F46E5;color:#fff;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s;white-space:nowrap;}\n' +
            '.lchat-prechat-btn:hover{background:#4338CA;}\n' +
            '.lchat-prechat-btn:disabled{background:#A5B4FC;cursor:not-allowed;}\n' +

            /* Offline form */
            '.lchat-offline{flex:1;display:flex;flex-direction:column;align-items:center;padding:32px 24px;gap:0;background:#F9FAFB;overflow-y:auto;}\n' +
            '.lchat-offline-icon{width:64px;height:64px;border-radius:50%;background:#FEF3C7;display:flex;align-items:center;justify-content:center;margin-top:24px;margin-bottom:12px;}\n' +
            '.lchat-offline-icon svg{width:32px;height:32px;fill:#F59E0B;}\n' +
            '.lchat-offline-title{font-size:18px;font-weight:700;color:#1F2937;margin-bottom:8px;text-align:center;}\n' +
            '.lchat-offline-desc{font-size:14px;color:#6B7280;text-align:center;margin-bottom:24px;line-height:1.5;}\n' +
            '.lchat-offline-form{width:100%;display:flex;flex-direction:column;gap:12px;}\n' +
            '.lchat-offline-form input,.lchat-offline-form textarea{width:100%;padding:10px 14px;border:1px solid #D1D5DB;border-radius:10px;font-size:14px;outline:none;background:#fff;color:#1F2937;font-family:inherit;box-sizing:border-box;}\n' +
            '.lchat-offline-form input:focus,.lchat-offline-form textarea:focus{border-color:#4F46E5;box-shadow:0 0 0 2px rgba(79,70,229,.15);}\n' +
            '.lchat-offline-form textarea{min-height:100px;resize:vertical;line-height:1.5;}\n' +
            '.lchat-offline-btn{width:100%;padding:12px 16px;border:none;border-radius:10px;background:#F59E0B;color:#fff;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s;}\n' +
            '.lchat-offline-btn:hover{background:#D97706;}\n' +
            '.lchat-offline-btn:disabled{background:#FCD34D;cursor:not-allowed;}\n' +
            '.lchat-offline-success{text-align:center;color:#059669;font-size:14px;font-weight:500;padding:8px 0;}\n' +

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
            '.lchat-dark .lchat-prechat-avatar{background:#1E1B4B;}\n' +
            '.lchat-dark .lchat-prechat-avatar svg{fill:#818CF8;}\n' +
            '.lchat-dark .lchat-prechat-title{color:#F3F4F6;}\n' +
            '.lchat-dark .lchat-prechat-desc{color:#9CA3AF;}\n' +
            '.lchat-dark .lchat-prechat input{background:#374151;color:#F3F4F6;border-color:#4B5563;}\n' +
            '.lchat-dark .lchat-topic-btn{background:#1F2937;color:#D1D5DB;border-color:#374151;}\n' +
            '.lchat-dark .lchat-topic-btn:hover{background:#1E1B4B;color:#A5B4FC;border-color:#4F46E5;}\n' +
            '.lchat-dark .lchat-prechat-divider::before,.lchat-dark .lchat-prechat-divider::after{background:#374151;}\n' +
            '.lchat-dark .lchat-attach-btn{color:#9CA3AF;}\n' +
            '.lchat-dark .lchat-attach-btn:hover{background:#374151;color:#818CF8;}\n' +
            '.lchat-dark .lchat-upload-progress{background:#1E1B4B;border-top-color:#374151;color:#818CF8;}\n' +
            '.lchat-dark .lchat-upload-progress-bar{background:#374151;}\n' +
            '.lchat-dark .lchat-upload-progress-fill{background:#818CF8;}\n' +
            '.lchat-dark .lchat-msg-agent .lchat-msg-file{background:rgba(255,255,255,.08);}\n' +
            '.lchat-dark .lchat-drag-overlay{background:rgba(30,27,75,.3);border-color:#818CF8;}\n' +
            '.lchat-dark .lchat-drag-overlay-text{color:#818CF8;}\n' +
            '.lchat-dark .lchat-header{background:#3730A3;}\n' +
            '.lchat-dark .lchat-status-dot.lchat-online{background:#34D399;}\n' +
            '.lchat-dark .lchat-status-dot.lchat-reconnecting{background:#F59E0B;}\n' +
            '.lchat-dark .lchat-status-dot.lchat-offline{background:#6B7280;}\n' +
            '.lchat-dark .lchat-offline{background:#111827;}\n' +
            '.lchat-dark .lchat-offline-icon{background:#451A03;}\n' +
            '.lchat-dark .lchat-offline-icon svg{fill:#FBBF24;}\n' +
            '.lchat-dark .lchat-offline-title{color:#F3F4F6;}\n' +
            '.lchat-dark .lchat-offline-desc{color:#9CA3AF;}\n' +
            '.lchat-dark .lchat-offline-form input,.lchat-dark .lchat-offline-form textarea{background:#374151;color:#F3F4F6;border-color:#4B5563;}\n' +
            '.lchat-dark .lchat-msg-check.lchat-read{color:#34D399;}\n' +
            '.lchat-dark .lchat-bh-offline{background:#451A03;color:#FCD34D;border-bottom-color:#78350F;}\n' +
            '.lchat-dark .lchat-prechat-field-label{color:#9CA3AF;}\n' +
            '.lchat-dark .lchat-prechat-fields input{background:#374151;color:#F3F4F6;border-color:#4B5563;}\n' +

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
    var ICON_ATTACH = '<svg viewBox="0 0 24 24"><path d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5c0-1.38 1.12-2.5 2.5-2.5s2.5 1.12 2.5 2.5v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z"/></svg>';
    var ICON_FILE = '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>';

    /* ── DOM Construction ───────────────────────────────────── */
    var root, bubble, badge, panel, backdrop, header, messagesEl, typingEl, inputArea, textarea, sendBtn, prechatEl, prechatInput, prechatBtn, attachBtn, fileInput, uploadProgressEl, dragOverlay, offlineEl, offlineEmailInput, offlineMessageInput, offlineSubmitBtn;

    function buildDOM() {
        root = document.createElement('div');
        root.className = 'lchat-widget';

        /* Bubble */
        bubble = document.createElement('div');
        bubble.className = 'lchat-bubble';
        bubble.innerHTML = ICON_CHAT;
        bubble.setAttribute('aria-label', t('openChat'));
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
        title.textContent = t('title');
        headerLeft.appendChild(statusDot);
        headerLeft.appendChild(title);
        var closeBtn = document.createElement('button');
        closeBtn.className = 'lchat-close-btn';
        closeBtn.innerHTML = ICON_CLOSE;
        closeBtn.setAttribute('aria-label', t('close'));
        header.appendChild(headerLeft);
        header.appendChild(closeBtn);
        panel.appendChild(header);

        /* Pre-chat form */
        prechatEl = document.createElement('div');
        prechatEl.className = 'lchat-prechat';

        /* Avatar */
        var avatar = document.createElement('div');
        avatar.className = 'lchat-prechat-avatar';
        avatar.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>';
        prechatEl.appendChild(avatar);

        var prechatTitle = document.createElement('div');
        prechatTitle.className = 'lchat-prechat-title';
        prechatTitle.textContent = t('greeting');
        prechatEl.appendChild(prechatTitle);

        var prechatDesc = document.createElement('div');
        prechatDesc.className = 'lchat-prechat-desc';
        prechatDesc.textContent = t('greetingDesc');
        prechatEl.appendChild(prechatDesc);

        /* Response time */
        var responseHint = document.createElement('div');
        responseHint.className = 'lchat-prechat-response';
        responseHint.textContent = t('responseHint');
        prechatEl.appendChild(responseHint);

        /* Quick topic buttons */
        var topics = document.createElement('div');
        topics.className = 'lchat-prechat-topics';
        var topicList = [
            { icon: '💬', key: 'topicGeneral' },
            { icon: '📋', key: 'topicQuote' },
            { icon: '🛠', key: 'topicSupport' },
        ];
        topicList.forEach(function (tp) {
            var btn = document.createElement('button');
            btn.className = 'lchat-topic-btn';
            btn.innerHTML = '<span class="lchat-topic-icon">' + tp.icon + '</span>' + t(tp.key);
            btn.addEventListener('click', function () {
                startWithTopic(t(tp.key));
            });
            topics.appendChild(btn);
        });
        prechatEl.appendChild(topics);

        /* Divider */
        var divider = document.createElement('div');
        divider.className = 'lchat-prechat-divider';
        divider.textContent = t('or');
        prechatEl.appendChild(divider);

        /* Name input row */
        var formRow = document.createElement('div');
        formRow.className = 'lchat-prechat-form';
        prechatInput = document.createElement('input');
        prechatInput.type = 'text';
        prechatInput.placeholder = t('nameInput');
        prechatInput.maxLength = 50;
        prechatInput.setAttribute('autocomplete', 'one-time-code');
        prechatInput.setAttribute('enterkeyhint', 'go');
        prechatBtn = document.createElement('button');
        prechatBtn.className = 'lchat-prechat-btn';
        prechatBtn.textContent = t('startBtn');
        formRow.appendChild(prechatInput);
        formRow.appendChild(prechatBtn);
        prechatEl.appendChild(formRow);

        panel.appendChild(prechatEl);

        /* Offline form */
        offlineEl = document.createElement('div');
        offlineEl.className = 'lchat-offline lchat-hidden';

        var offlineIcon = document.createElement('div');
        offlineIcon.className = 'lchat-offline-icon';
        offlineIcon.innerHTML = '<svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>';
        offlineEl.appendChild(offlineIcon);

        var offlineTitle = document.createElement('div');
        offlineTitle.className = 'lchat-offline-title';
        offlineTitle.textContent = t('offlineTitle');
        offlineEl.appendChild(offlineTitle);

        var offlineDesc = document.createElement('div');
        offlineDesc.className = 'lchat-offline-desc';
        offlineDesc.textContent = t('offlineDesc');
        offlineEl.appendChild(offlineDesc);

        var offlineForm = document.createElement('div');
        offlineForm.className = 'lchat-offline-form';

        offlineEmailInput = document.createElement('input');
        offlineEmailInput.type = 'email';
        offlineEmailInput.placeholder = t('offlineEmail');
        offlineEmailInput.required = true;
        offlineEmailInput.setAttribute('autocomplete', 'email');
        offlineForm.appendChild(offlineEmailInput);

        offlineMessageInput = document.createElement('textarea');
        offlineMessageInput.placeholder = t('offlineMsg');
        offlineMessageInput.required = true;
        offlineForm.appendChild(offlineMessageInput);

        offlineSubmitBtn = document.createElement('button');
        offlineSubmitBtn.className = 'lchat-offline-btn';
        offlineSubmitBtn.textContent = t('offlineSubmit');
        offlineForm.appendChild(offlineSubmitBtn);

        offlineEl.appendChild(offlineForm);
        panel.appendChild(offlineEl);

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

        /* Upload progress bar (inserted before input area) */
        uploadProgressEl = document.createElement('div');
        uploadProgressEl.className = 'lchat-upload-progress lchat-hidden';
        uploadProgressEl.innerHTML = '<span class="lchat-upload-progress-label">' + t('uploading') + '</span>' +
            '<div class="lchat-upload-progress-bar"><div class="lchat-upload-progress-fill" style="width:0%"></div></div>' +
            '<button class="lchat-upload-cancel" aria-label="' + t('cancel') + '">&#10005;</button>';
        panel.appendChild(uploadProgressEl);

        /* Input area */
        inputArea = document.createElement('div');
        inputArea.className = 'lchat-input-area lchat-hidden';

        /* Hidden file input */
        fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf,text/plain,text/csv';
        fileInput.style.display = 'none';

        /* Attach button */
        attachBtn = document.createElement('button');
        attachBtn.className = 'lchat-attach-btn';
        attachBtn.innerHTML = ICON_ATTACH;
        attachBtn.setAttribute('aria-label', t('attachFile'));

        textarea = document.createElement('div');
        textarea.className = 'lchat-textarea';
        textarea.setAttribute('contenteditable', 'true');
        textarea.setAttribute('role', 'textbox');
        textarea.setAttribute('aria-label', t('msgPlaceholder'));
        textarea.setAttribute('data-placeholder', t('msgPlaceholder'));
        textarea.setAttribute('enterkeyhint', 'send');
        sendBtn = document.createElement('button');
        sendBtn.className = 'lchat-send-btn';
        sendBtn.innerHTML = ICON_SEND;
        sendBtn.setAttribute('aria-label', t('sendMsg'));
        inputArea.appendChild(fileInput);
        inputArea.appendChild(attachBtn);
        inputArea.appendChild(textarea);
        inputArea.appendChild(sendBtn);
        panel.appendChild(inputArea);

        /* Drag & drop overlay */
        dragOverlay = document.createElement('div');
        dragOverlay.className = 'lchat-drag-overlay lchat-hidden';
        dragOverlay.innerHTML = '<span class="lchat-drag-overlay-text">' + t('dropFile') + '</span>';
        panel.appendChild(dragOverlay);
        panel.style.position = panel.style.position || 'fixed';

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

    /* ── Connection Status ──────────────────────────────────── */
    var STATUS_I18N_KEYS = {
        online: 'title',
        reconnecting: 'reconnecting',
        offline: 'offline',
    };

    function updateConnectionStatus(status) {
        if (state.connectionStatus === status) return;
        state.connectionStatus = status;

        var dot = root && root.querySelector('.lchat-status-dot');
        var titleEl = root && root.querySelector('.lchat-header-title');
        if (!dot || !titleEl) return;

        dot.classList.remove('lchat-online', 'lchat-reconnecting', 'lchat-offline');
        dot.classList.add('lchat-' + status);
        titleEl.textContent = t(STATUS_I18N_KEYS[status] || 'offline');
    }

    /* ── UI Helpers ─────────────────────────────────────────── */
    function hideAllViews() {
        prechatEl.style.display = 'none';
        offlineEl.classList.add('lchat-hidden');
        messagesEl.classList.add('lchat-hidden');
        inputArea.classList.add('lchat-hidden');
    }

    function showChat() {
        hideAllViews();
        messagesEl.classList.remove('lchat-hidden');
        messagesEl.appendChild(typingEl);
        inputArea.classList.remove('lchat-hidden');
    }

    function showPrechat() {
        hideAllViews();
        prechatEl.style.display = 'flex';
    }

    function showOfflineForm() {
        hideAllViews();
        offlineEl.classList.remove('lchat-hidden');
        if (state.visitorEmail) {
            offlineEmailInput.value = state.visitorEmail;
        }
        /* LCHAT-93: 운영시간 외 메시지가 있으면 오프라인 설명에 표시 */
        if (state.businessHours && state.businessHours.offline_message) {
            var offlineDesc = offlineEl.querySelector('.lchat-offline-desc');
            if (offlineDesc) {
                offlineDesc.textContent = state.businessHours.offline_message;
            }
        }
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
                /* Check agent availability before showing prechat or offline */
                fetchWidgetConfig().then(function () {
                    if (state.agentsOnline === 0) {
                        showOfflineForm();
                    } else {
                        showPrechat();
                    }
                }).catch(function () {
                    showPrechat(); /* fallback to normal prechat on error */
                });
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
            var localeMap = { ko: 'ko-KR', en: 'en-US', ja: 'ja-JP' };
            return d.toLocaleTimeString(localeMap[state.lang] || 'ko-KR', { hour: '2-digit', minute: '2-digit' });
        } catch (_) {
            return '';
        }
    }

    /* ── File Helpers ──────────────────────────────────────── */
    function formatFileSize(bytes) {
        if (!bytes) return '';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function isImageType(contentType) {
        return contentType === 'image';
    }

    function isFileType(contentType) {
        return contentType === 'file';
    }

    function buildMessageContentEl(msg) {
        if (isImageType(msg.content_type) && msg.file_url) {
            var img = document.createElement('img');
            img.className = 'lchat-msg-image';
            img.src = resolveFileUrl(msg.file_url);
            img.alt = msg.content || t('image');
            img.loading = 'lazy';
            img.addEventListener('click', function () {
                openImageOverlay(img.src);
            });
            return img;
        }
        if (isFileType(msg.content_type) && msg.file_url) {
            var link = document.createElement('a');
            link.className = 'lchat-msg-file';
            link.href = resolveFileUrl(msg.file_url);
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.download = '';

            var iconSpan = document.createElement('span');
            iconSpan.className = 'lchat-msg-file-icon';
            iconSpan.innerHTML = ICON_FILE;
            link.appendChild(iconSpan);

            var info = document.createElement('span');
            info.className = 'lchat-msg-file-info';

            var fname = document.createElement('div');
            fname.className = 'lchat-msg-file-name';
            fname.textContent = msg.content || t('file');
            info.appendChild(fname);

            if (msg.file_size) {
                var fsize = document.createElement('div');
                fsize.className = 'lchat-msg-file-size';
                fsize.textContent = formatFileSize(msg.file_size);
                info.appendChild(fsize);
            }

            link.appendChild(info);
            return link;
        }
        /* Default: text */
        var el = document.createElement('div');
        el.textContent = msg.content;
        return el;
    }

    function resolveFileUrl(url) {
        if (!url) return '';
        if (url.indexOf('http') === 0) return url;
        return cfg.baseUrl + url;
    }

    function openImageOverlay(src) {
        var overlay = document.createElement('div');
        overlay.className = 'lchat-image-overlay';
        var img = document.createElement('img');
        img.src = src;
        overlay.appendChild(img);
        overlay.addEventListener('click', function () {
            if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
        });
        document.body.appendChild(overlay);
    }

    /* ── Render Messages ───────────────────────────────────── */
    function renderMessages() {
        /* Keep typing indicator reference */
        if (typingEl.parentNode === messagesEl) {
            messagesEl.removeChild(typingEl);
        }
        messagesEl.innerHTML = '';

        state.messages.forEach(function (msg) {
            messagesEl.appendChild(buildMessageEl(msg));
        });

        messagesEl.appendChild(typingEl);
        scrollToBottom(false);
    }

    /* LCHAT-94: 메시지 엘리먼트 빌드 (읽음 확인 체크마크 포함) */
    function buildMessageEl(msg) {
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
            wrapper.appendChild(buildMessageContentEl(msg));
            if (msg.created_at) {
                var timeEl = document.createElement('div');
                timeEl.className = 'lchat-msg-time lchat-msg-status';
                timeEl.textContent = formatTime(msg.created_at);
                /* LCHAT-94: visitor 메시지에 전송/읽음 체크마크 표시 */
                if (isVisitor) {
                    var check = document.createElement('span');
                    check.className = 'lchat-msg-check' + (msg.is_read ? ' lchat-read' : '');
                    check.textContent = msg.is_read ? ' \u2713\u2713' : ' \u2713';
                    check.setAttribute('data-msg-id', msg.id || '');
                    timeEl.appendChild(check);
                }
                wrapper.appendChild(timeEl);
            }
        }

        return wrapper;
    }

    function appendMessage(msg) {
        /* Deduplicate */
        for (var i = 0; i < state.messages.length; i++) {
            if (state.messages[i].id && state.messages[i].id === msg.id) return;
        }
        state.messages.push(msg);

        /* Insert before typing indicator */
        messagesEl.insertBefore(buildMessageEl(msg), typingEl);
        scrollToBottom(false);

        /* LCHAT-94: 새 agent 메시지를 받으면 자동으로 읽음 처리 */
        if (state.open && msg.sender_type === 'agent') {
            markReadForVisitor();
        }
    }

    /* ── API Calls ─────────────────────────────────────────── */
    function apiHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-API-Key': cfg.apiKey,
        };
    }

    function fetchWidgetConfig() {
        return fetch(cfg.baseUrl + '/api/widget/config?api_key=' + encodeURIComponent(cfg.apiKey), {
            headers: { 'Accept': 'application/json' },
        })
        .then(function (r) {
            if (!r.ok) throw new Error('Widget config failed: ' + r.status);
            return r.json();
        })
        .then(function (json) {
            var data = json.data || json;
            state.agentsOnline = typeof data.agents_online === 'number' ? data.agents_online : null;
        });
    }

    function createRoom() {
        var body = { visitor_name: state.visitorName };
        if (state.visitorEmail) {
            body.visitor_email = state.visitorEmail;
        }
        return fetch(cfg.baseUrl + '/api/rooms', {
            method: 'POST',
            headers: apiHeaders(),
            body: JSON.stringify(body),
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
            /* LCHAT-94: 메시지 로드 후 읽음 처리 */
            if (state.open) markReadForVisitor();
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
            appendSystemMsg(t('sendFailed'));
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

    /* LCHAT-94: 방문자가 메시지를 볼 때 읽음 처리 API 호출 */
    function markReadForVisitor() {
        if (!state.roomId) return;
        fetch(cfg.baseUrl + '/api/rooms/' + state.roomId + '/read', {
            method: 'POST',
            headers: apiHeaders(),
            body: JSON.stringify({
                reader_type: 'visitor',
                reader_name: state.visitorName || 'visitor',
            }),
        }).catch(function () { /* silent */ });
    }

    /**
     * LCHAT-94: 상담원 읽음 시 visitor 메시지 체크마크를 ✓✓ (읽음)으로 업데이트
     */
    function updateReadReceipts(lastReadMessageId) {
        if (!lastReadMessageId) return;
        state.lastReadMessageId = lastReadMessageId;

        /* Update in-memory message data */
        var found = false;
        for (var i = state.messages.length - 1; i >= 0; i--) {
            var m = state.messages[i];
            if (m.id === lastReadMessageId) found = true;
            if (found && m.sender_type === 'visitor') {
                m.is_read = true;
            }
        }

        /* Update DOM checkmarks */
        var checks = messagesEl.querySelectorAll('.lchat-msg-check');
        checks.forEach(function (el) {
            var msgId = el.getAttribute('data-msg-id');
            if (!msgId) return;
            for (var j = 0; j < state.messages.length; j++) {
                if (state.messages[j].id === msgId && state.messages[j].is_read) {
                    el.classList.add('lchat-read');
                    el.textContent = ' \u2713\u2713';
                    break;
                }
            }
        });
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

    /* ── File Upload ───────────────────────────────────────── */
    var currentUploadXhr = null;

    function uploadFile(file) {
        if (!state.roomId) return;

        if (file.size > 10 * 1024 * 1024) {
            appendSystemMsg(t('fileSizeLimit'));
            return;
        }

        var isImage = file.type && file.type.indexOf('image/') === 0;
        var contentType = isImage ? 'image' : 'file';

        uploadProgressEl.classList.remove('lchat-hidden');
        var fillEl = uploadProgressEl.querySelector('.lchat-upload-progress-fill');
        var labelEl = uploadProgressEl.querySelector('.lchat-upload-progress-label');
        fillEl.style.width = '0%';
        labelEl.textContent = t('uploadPct').replace('{pct}', '0');
        attachBtn.disabled = true;

        var formData = new FormData();
        formData.append('file', file);

        var xhr = new XMLHttpRequest();
        currentUploadXhr = xhr;

        xhr.upload.addEventListener('progress', function (e) {
            if (e.lengthComputable) {
                var pct = Math.round((e.loaded / e.total) * 100);
                fillEl.style.width = pct + '%';
                labelEl.textContent = t('uploadPct').replace('{pct}', pct);
            }
        });

        xhr.addEventListener('load', function () {
            currentUploadXhr = null;
            uploadProgressEl.classList.add('lchat-hidden');
            attachBtn.disabled = false;

            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var json = JSON.parse(xhr.responseText);
                    var data = json.data || json;
                    sendFileMessage(data, contentType, file.name, file.size);
                } catch (err) {
                    logError(err);
                    appendSystemMsg(t('uploadResponseError'));
                }
            } else {
                appendSystemMsg(t('uploadFailed'));
            }
        });

        xhr.addEventListener('error', function () {
            currentUploadXhr = null;
            uploadProgressEl.classList.add('lchat-hidden');
            attachBtn.disabled = false;
            appendSystemMsg(t('uploadError'));
        });

        xhr.addEventListener('abort', function () {
            currentUploadXhr = null;
            uploadProgressEl.classList.add('lchat-hidden');
            attachBtn.disabled = false;
        });

        xhr.open('POST', cfg.baseUrl + '/api/upload');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-API-Key', cfg.apiKey);
        xhr.send(formData);
    }

    function cancelUpload() {
        if (currentUploadXhr) {
            currentUploadXhr.abort();
        }
    }

    function sendFileMessage(uploadData, contentType, fileName, fileSize) {
        var fileUrl = uploadData.file_url || uploadData.url;
        var displayName = uploadData.file_name || fileName;

        var tempMsg = {
            id: 'temp-' + Date.now(),
            sender_type: 'visitor',
            sender_name: state.visitorName,
            content: displayName,
            content_type: contentType,
            file_url: fileUrl,
            file_size: uploadData.file_size || fileSize,
            created_at: new Date().toISOString(),
        };
        appendMessage(tempMsg);

        fetch(cfg.baseUrl + '/api/rooms/' + state.roomId + '/messages', {
            method: 'POST',
            headers: apiHeaders(),
            body: JSON.stringify({
                sender_type: 'visitor',
                sender_name: state.visitorName,
                content: displayName,
                content_type: contentType,
                file_url: fileUrl,
            }),
        })
        .then(function (r) {
            if (!r.ok) throw new Error('Send file message failed: ' + r.status);
            return r.json();
        })
        .then(function (json) {
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
            appendSystemMsg(t('fileMsgFailed'));
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

        updateConnectionStatus('reconnecting');

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

                /* Connection status bindings */
                var pusherConn = echo.connector.pusher.connection;
                pusherConn.bind('connecting', function () {
                    updateConnectionStatus('reconnecting');
                });
                pusherConn.bind('connected', function () {
                    updateConnectionStatus('online');
                    stopPolling(); /* stop fallback polling if Echo reconnects */
                });
                pusherConn.bind('unavailable', function () {
                    updateConnectionStatus('offline');
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

                /* LCHAT-94: 상담원이 읽음 처리 시 체크마크 업데이트 */
                state.echoChannel.listen('.message.read', function (e) {
                    var data = e.data || e;
                    if (data.reader_type === 'agent') {
                        updateReadReceipts(data.last_read_message_id);
                    }
                });

                echo.connector.pusher.connection.bind('disconnected', function () {
                    updateConnectionStatus('reconnecting');
                    startPolling();
                });
            });
    }

    function startPolling() {
        if (state.pollTimer) return;
        updateConnectionStatus('online');
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

    function isScrollableArea(el) {
        return el === messagesEl || el === prechatEl;
    }

    function preventBgScroll(e) {
        var el = e.target;
        while (el && el !== document.body && el !== document.documentElement) {
            if (isScrollableArea(el)) {
                /* Allow scroll but prevent overscroll at edges */
                var scrollEl = el;
                var atTop = scrollEl.scrollTop <= 0;
                var atBottom = scrollEl.scrollTop + scrollEl.clientHeight >= scrollEl.scrollHeight - 1;
                if (e.touches && e.touches.length === 1) {
                    var touch = e.touches[0];
                    var dy = touch.clientY - (state._lastTouchY || touch.clientY);
                    state._lastTouchY = touch.clientY;
                    if ((atTop && dy > 0) || (atBottom && dy < 0)) {
                        e.preventDefault();
                    }
                }
                return;
            }
            if (el === panel) {
                e.preventDefault();
                return;
            }
            el = el.parentElement;
        }
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
        prechatInput.addEventListener('focus', function () {
            if (isMobile()) {
                setTimeout(function () {
                    prechatInput.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }, 300);
                setTimeout(function () {
                    prechatInput.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }, 600);
            }
        });
        prechatInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handlePrechat();
            }
        });

        /* Offline form */
        offlineSubmitBtn.addEventListener('click', handleOfflineSubmit);
        offlineEmailInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                offlineMessageInput.focus();
            }
        });
        offlineMessageInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleOfflineSubmit();
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

        /* File attach button */
        attachBtn.addEventListener('click', function () {
            fileInput.click();
        });

        /* File input change */
        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files[0]) {
                uploadFile(fileInput.files[0]);
                fileInput.value = '';
            }
        });

        /* Upload cancel button */
        uploadProgressEl.querySelector('.lchat-upload-cancel').addEventListener('click', function () {
            cancelUpload();
        });

        /* Drag & drop on panel */
        var dragCounter = 0;
        panel.addEventListener('dragenter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dragCounter++;
            if (state.roomId && !messagesEl.classList.contains('lchat-hidden')) {
                dragOverlay.classList.remove('lchat-hidden');
            }
        });
        panel.addEventListener('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dragCounter--;
            if (dragCounter <= 0) {
                dragCounter = 0;
                dragOverlay.classList.add('lchat-hidden');
            }
        });
        panel.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
        });
        panel.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dragCounter = 0;
            dragOverlay.classList.add('lchat-hidden');
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0] && state.roomId) {
                uploadFile(e.dataTransfer.files[0]);
            }
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
                appendSystemMsg(t('connectFailed'));
                prechatBtn.disabled = false;
            });
    }

    function handleOfflineSubmit() {
        var email = offlineEmailInput.value.trim();
        var message = offlineMessageInput.value.trim();

        if (!email || !message) {
            if (!email) offlineEmailInput.focus();
            else offlineMessageInput.focus();
            return;
        }

        /* Basic email validation */
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            offlineEmailInput.focus();
            return;
        }

        offlineSubmitBtn.disabled = true;
        state.visitorEmail = email;
        state.visitorName = email.split('@')[0];
        localStorage.setItem(LS_EMAIL, email);
        localStorage.setItem(LS_NAME, state.visitorName);

        createRoom()
            .then(function () {
                /* Send the offline message */
                return fetch(cfg.baseUrl + '/api/rooms/' + state.roomId + '/messages', {
                    method: 'POST',
                    headers: apiHeaders(),
                    body: JSON.stringify({
                        sender_type: 'visitor',
                        sender_name: state.visitorName,
                        content: message,
                        content_type: 'text',
                    }),
                });
            })
            .then(function (r) {
                if (!r.ok) throw new Error('Send failed: ' + r.status);
                return r.json();
            })
            .then(function () {
                /* Show success state */
                offlineEl.innerHTML = '';
                var successIcon = document.createElement('div');
                successIcon.className = 'lchat-offline-icon';
                successIcon.style.background = '#D1FAE5';
                successIcon.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#059669"/></svg>';
                offlineEl.appendChild(successIcon);

                var successTitle = document.createElement('div');
                successTitle.className = 'lchat-offline-title';
                successTitle.style.marginTop = '12px';
                successTitle.textContent = t('offlineSuccess');
                offlineEl.appendChild(successTitle);

                var successDesc = document.createElement('div');
                successDesc.className = 'lchat-offline-desc';
                successDesc.textContent = t('offlineSuccessDesc').replace('{email}', email);
                offlineEl.appendChild(successDesc);
            })
            .catch(function (err) {
                logError(err);
                offlineSubmitBtn.disabled = false;
                /* Show inline error */
                var existingErr = offlineEl.querySelector('.lchat-offline-success');
                if (existingErr) existingErr.remove();
                var errMsg = document.createElement('div');
                errMsg.className = 'lchat-offline-success';
                errMsg.style.color = '#DC2626';
                errMsg.textContent = t('offlineError');
                offlineEl.appendChild(errMsg);
            });
    }

    function startWithTopic(topic) {
        state.visitorName = t('visitor');
        localStorage.setItem(LS_NAME, state.visitorName);

        createRoom()
            .then(function () {
                showChat();
                /* Send topic as first message */
                var content = topic;
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
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    var msg = json.data || json;
                    appendMessage(msg);
                })
                .catch(logError);
                loadMessages();
            })
            .catch(function (err) {
                logError(err);
                appendSystemMsg(t('connectFailed'));
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
