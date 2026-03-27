<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: #2563eb; color: #fff; padding: 20px 24px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 8px 0 0; font-size: 13px; opacity: 0.85; }
        .body { padding: 24px; }
        .message { margin-bottom: 16px; padding: 12px 16px; border-radius: 8px; }
        .message.visitor { background: #eff6ff; border-left: 3px solid #2563eb; }
        .message.agent { background: #f0fdf4; border-left: 3px solid #16a34a; }
        .message.system { background: #fefce8; border-left: 3px solid #ca8a04; font-style: italic; }
        .sender { font-weight: 600; font-size: 13px; margin-bottom: 4px; }
        .sender.visitor { color: #2563eb; }
        .sender.agent { color: #16a34a; }
        .sender.system { color: #ca8a04; }
        .content { font-size: 14px; color: #374151; line-height: 1.5; }
        .time { font-size: 11px; color: #9ca3af; margin-top: 4px; }
        .footer { padding: 16px 24px; background: #f9fafb; border-top: 1px solid #e5e7eb; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Live Chat - Transcript</h1>
            <p>{{ $room->visitor_name }} ({{ $room->visitor_email }})</p>
        </div>
        <div class="body">
            @forelse($messages as $msg)
                <div class="message {{ $msg->sender_type }}">
                    <div class="sender {{ $msg->sender_type }}">{{ $msg->sender_name }}</div>
                    <div class="content">{{ $msg->content }}</div>
                    <div class="time">{{ $msg->created_at->format('Y-m-d H:i:s') }}</div>
                </div>
            @empty
                <p style="text-align:center;color:#9ca3af;">No messages.</p>
            @endforelse
        </div>
        <div class="footer">
            Powered by Live Chat &mdash; {{ config('app.url') }}
        </div>
    </div>
</body>
</html>
