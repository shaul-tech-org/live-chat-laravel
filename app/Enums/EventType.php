<?php

namespace App\Enums;

enum EventType: string
{
    case PageView = 'page_view';
    case WidgetOpen = 'widget_open';
    case WidgetClose = 'widget_close';
    case ChatStart = 'chat_start';
    case ChatEnd = 'chat_end';
}
