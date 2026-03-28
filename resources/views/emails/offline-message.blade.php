<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: #f59e0b; color: #fff; padding: 20px 24px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 8px 0 0; font-size: 13px; opacity: 0.85; }
        .body { padding: 24px; }
        .info { margin-bottom: 16px; }
        .info-label { font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
        .info-value { font-size: 14px; color: #1f2937; }
        .message-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 16px; margin-top: 16px; }
        .message-content { font-size: 14px; color: #374151; line-height: 1.6; white-space: pre-wrap; }
        .action { margin-top: 24px; text-align: center; }
        .action a { display: inline-block; background: #4f46e5; color: #fff; text-decoration: none; padding: 10px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; }
        .footer { padding: 16px 24px; background: #f9fafb; border-top: 1px solid #e5e7eb; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>오프라인 메시지 수신</h1>
            <p>상담사 부재 중 방문자 메시지가 도착했습니다.</p>
        </div>
        <div class="body">
            <div class="info">
                <div class="info-label">방문자</div>
                <div class="info-value">{{ $senderName }}</div>
            </div>
            @if($visitorEmail)
            <div class="info">
                <div class="info-label">이메일</div>
                <div class="info-value">{{ $visitorEmail }}</div>
            </div>
            @endif
            <div class="info">
                <div class="info-label">채팅방 ID</div>
                <div class="info-value" style="font-family: monospace; font-size: 12px;">{{ $roomId }}</div>
            </div>
            <div class="message-box">
                <div class="info-label">메시지</div>
                <div class="message-content">{{ $messageContent }}</div>
            </div>
            <div class="action">
                <a href="{{ config('app.url') }}/admin/rooms/{{ $roomId }}">채팅방 열기</a>
            </div>
        </div>
        <div class="footer">
            Powered by Live Chat &mdash; {{ config('app.url') }}
        </div>
    </div>
</body>
</html>
