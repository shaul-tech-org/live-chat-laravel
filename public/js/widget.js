/**
 * Live Chat Widget — Echo.js Integration (Phase 3)
 *
 * 사용법:
 *   <script src="https://lchat.example.com/js/widget.js"
 *           data-api-key="YOUR_API_KEY"
 *           data-reverb-key="YOUR_REVERB_KEY"
 *           data-reverb-host="lchat.example.com"
 *           data-reverb-port="443"></script>
 *
 * 전체 위젯 UI는 Phase 4에서 구현 예정.
 */
(function () {
    'use strict';

    var ECHO_CDN = 'https://cdn.jsdelivr.net/npm/laravel-echo@2/dist/echo.iife.min.js';
    var PUSHER_CDN = 'https://cdn.jsdelivr.net/npm/pusher-js@8/dist/web/pusher.min.js';

    var script = document.currentScript;
    var config = {
        apiKey: script?.getAttribute('data-api-key') || '',
        reverbKey: script?.getAttribute('data-reverb-key') || '',
        reverbHost: script?.getAttribute('data-reverb-host') || window.location.hostname,
        reverbPort: parseInt(script?.getAttribute('data-reverb-port') || '443', 10),
        baseUrl: script?.src ? new URL(script.src).origin : '',
    };

    var reconnectAttempts = 0;
    var maxReconnectAttempts = 5;
    var reconnectDelay = 3000;

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    async function initEcho() {
        if (!window.Pusher) {
            await loadScript(PUSHER_CDN);
        }
        if (!window.Echo) {
            await loadScript(ECHO_CDN);
        }

        window.chatEcho = new window.Echo({
            broadcaster: 'reverb',
            key: config.reverbKey,
            wsHost: config.reverbHost,
            wsPort: config.reverbPort,
            wssPort: config.reverbPort,
            forceTLS: config.reverbPort === 443,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: config.baseUrl + '/api/broadcasting/auth',
            auth: {
                headers: {
                    'X-API-Key': config.apiKey,
                },
            },
        });

        window.chatEcho.connector.pusher.connection.bind('connected', function () {
            reconnectAttempts = 0;
            document.dispatchEvent(new CustomEvent('livechat:connected'));
        });

        window.chatEcho.connector.pusher.connection.bind('disconnected', function () {
            document.dispatchEvent(new CustomEvent('livechat:disconnected'));
            attemptReconnect();
        });

        window.chatEcho.connector.pusher.connection.bind('error', function (err) {
            document.dispatchEvent(new CustomEvent('livechat:error', { detail: err }));
        });

        return window.chatEcho;
    }

    function attemptReconnect() {
        if (reconnectAttempts >= maxReconnectAttempts) {
            document.dispatchEvent(new CustomEvent('livechat:reconnect_failed'));
            return;
        }
        reconnectAttempts++;
        var delay = reconnectDelay * Math.pow(2, reconnectAttempts - 1);
        setTimeout(function () {
            if (window.chatEcho && window.chatEcho.connector.pusher.connection.state !== 'connected') {
                window.chatEcho.connector.pusher.connect();
            }
        }, delay);
    }

    function subscribeToRoom(roomId) {
        if (!window.chatEcho) {
            console.error('[LiveChat] Echo not initialized');
            return null;
        }

        var channel = window.chatEcho.private('chat.' + roomId);

        channel.listen('.message.sent', function (e) {
            document.dispatchEvent(new CustomEvent('livechat:message', { detail: e }));
        });

        channel.listen('.typing.started', function (e) {
            document.dispatchEvent(new CustomEvent('livechat:typing', { detail: e }));
        });

        channel.listen('.reaction.added', function (e) {
            document.dispatchEvent(new CustomEvent('livechat:reaction', { detail: e }));
        });

        channel.listen('.message.read', function (e) {
            document.dispatchEvent(new CustomEvent('livechat:read', { detail: e }));
        });

        channel.listen('.agent.offline', function (e) {
            document.dispatchEvent(new CustomEvent('livechat:agent_offline', { detail: e }));
        });

        channel.listen('.system.message', function (e) {
            document.dispatchEvent(new CustomEvent('livechat:system', { detail: e }));
        });

        return channel;
    }

    function subscribeToAdmin(tenantId) {
        if (!window.chatEcho) {
            console.error('[LiveChat] Echo not initialized');
            return null;
        }

        var channel = window.chatEcho.private('admin.' + tenantId);

        channel.listen('.message.sent', function (e) {
            document.dispatchEvent(new CustomEvent('livechat:admin:message', { detail: e }));
        });

        channel.listen('.agent.status.changed', function (e) {
            document.dispatchEvent(new CustomEvent('livechat:admin:agent_status', { detail: e }));
        });

        return channel;
    }

    async function sendMessage(roomId, content, senderName, contentType) {
        var response = await fetch(config.baseUrl + '/api/rooms/' + roomId + '/messages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-API-Key': config.apiKey,
            },
            body: JSON.stringify({
                sender_type: 'visitor',
                sender_name: senderName || '방문자',
                content: content,
                content_type: contentType || 'text',
            }),
        });

        if (!response.ok) {
            throw new Error('Message send failed: ' + response.status);
        }

        return response.json();
    }

    async function sendTyping(roomId, senderName) {
        await fetch(config.baseUrl + '/api/rooms/' + roomId + '/typing', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': config.apiKey,
            },
            body: JSON.stringify({
                sender_type: 'visitor',
                sender_name: senderName || '방문자',
            }),
        });
    }

    async function markRead(roomId, readerName) {
        var response = await fetch(config.baseUrl + '/api/rooms/' + roomId + '/read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-API-Key': config.apiKey,
            },
            body: JSON.stringify({
                reader_type: 'visitor',
                reader_name: readerName || '방문자',
            }),
        });

        if (!response.ok) {
            throw new Error('Mark read failed: ' + response.status);
        }

        return response.json();
    }

    async function getMessages(roomId, limit, before) {
        var url = config.baseUrl + '/api/rooms/' + roomId + '/messages?limit=' + (limit || 50);
        if (before) {
            url += '&before=' + before;
        }

        var response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-API-Key': config.apiKey,
            },
        });

        if (!response.ok) {
            throw new Error('Get messages failed: ' + response.status);
        }

        return response.json();
    }

    window.LiveChat = {
        config: config,
        init: initEcho,
        subscribe: subscribeToRoom,
        subscribeAdmin: subscribeToAdmin,
        send: sendMessage,
        sendTyping: sendTyping,
        markRead: markRead,
        getMessages: getMessages,
    };
})();
