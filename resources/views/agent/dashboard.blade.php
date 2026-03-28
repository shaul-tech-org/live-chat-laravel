@extends('layouts.app')

@section('title', '상담원 대시보드 - LCHAT')

@section('body')
<script>window.__ADMIN_TOKEN = @json($adminToken ?? '');</script>
<div class="flex flex-col h-screen" x-data="agentDashboard()">
    {{-- 헤더 --}}
    <header class="flex items-center justify-between px-2 md:px-4 py-2 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shrink-0">
        <div class="flex items-center gap-2 md:gap-3">
            <h1 class="text-lg font-bold">LCHAT</h1>
            <span class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 px-2 py-0.5 rounded">상담원</span>
        </div>
        <div class="flex items-center gap-1 md:gap-3">
            <a href="{{ route('admin.dashboard') }}" class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hidden md:inline">관리자</a>
            <button @click="soundEnabled = !soundEnabled" class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" :title="soundEnabled ? '알림 음소거' : '알림 켜기'">
                <svg x-show="soundEnabled" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M12 6l-4 4H4v4h4l4 4V6z"/></svg>
                <svg x-show="!soundEnabled" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5v14a1 1 0 01-1.707.707L5.586 15z" clip-rule="evenodd"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/></svg>
            </button>
            <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="다크 모드">
                <svg x-show="!darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg x-show="darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="px-3 py-1 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 rounded">로그아웃</button>
            </form>
        </div>
    </header>

    {{-- 메인 콘텐츠 --}}
    <main class="flex flex-1 overflow-hidden">
        {{-- 좌측 사이드바: 데스크톱 항상 표시, 모바일은 mobileView=list 일 때만 --}}
        <aside class="w-full md:w-80 border-r border-gray-200 dark:border-gray-700 flex flex-col bg-white dark:bg-gray-800 shrink-0"
               :class="{ 'hidden md:flex': mobileView !== 'list' }">
            {{-- 내 대화 섹션 --}}
            <div class="flex-1 overflow-y-auto">
                <div class="px-3 pt-3 pb-2">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            내 대화
                            <span class="ml-1 text-xs font-normal text-gray-400" x-text="'(' + myRooms.length + ')'"></span>
                        </h2>
                        <button @click="refreshRooms()" class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="새로고침">
                            <svg class="w-3.5 h-3.5 text-gray-400" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </div>
                </div>

                <template x-for="room in myRooms" :key="room.id">
                    <div
                        @click="selectRoom(room); if (window.innerWidth < 768) mobileView = 'chat'"
                        :class="selectedRoom && selectedRoom.id === room.id ? 'bg-blue-50 dark:bg-blue-900/20 border-l-2 border-blue-500' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-l-2 border-transparent'"
                        class="px-3 py-2.5 cursor-pointer border-b border-gray-100 dark:border-gray-700 transition-colors"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-medium text-sm truncate" x-text="room.visitor_name || '방문자'"></span>
                            <span class="text-xs text-gray-400" x-text="formatTime(room.updated_at)"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400 truncate flex-1" x-text="room.last_message || '새 대화'"></span>
                            <span
                                x-show="room.unread_count > 0"
                                x-text="room.unread_count"
                                class="ml-2 px-1.5 py-0.5 bg-red-500 text-white text-xs rounded-full min-w-[20px] text-center"
                            ></span>
                        </div>
                    </div>
                </template>

                <div x-show="myRooms.length === 0" class="px-3 py-4 text-center">
                    <p class="text-xs text-gray-400 dark:text-gray-500">배정된 대화가 없습니다</p>
                </div>

                {{-- 대기 중 섹션 --}}
                <div class="px-3 pt-4 pb-2 border-t border-gray-200 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        대기 중
                        <span class="ml-1 text-xs font-normal text-orange-500" x-text="'(' + waitingRooms.length + ')'"></span>
                    </h2>
                </div>

                <template x-for="room in waitingRooms" :key="room.id">
                    <div
                        @click="selectRoom(room); if (window.innerWidth < 768) mobileView = 'chat'"
                        :class="selectedRoom && selectedRoom.id === room.id ? 'bg-orange-50 dark:bg-orange-900/20 border-l-2 border-orange-400' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-l-2 border-transparent'"
                        class="px-3 py-2.5 cursor-pointer border-b border-gray-100 dark:border-gray-700 transition-colors"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-medium text-sm truncate" x-text="room.visitor_name || '방문자'"></span>
                            <span class="text-xs text-gray-400" x-text="formatTime(room.created_at)"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400 truncate flex-1" x-text="room.last_message || '새 대화'"></span>
                            <button
                                @click.stop="assignRoom(room)"
                                class="ml-2 px-2 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors shrink-0"
                            >가져오기</button>
                        </div>
                    </div>
                </template>

                <div x-show="waitingRooms.length === 0" class="px-3 py-4 text-center">
                    <p class="text-xs text-gray-400 dark:text-gray-500">대기 중인 대화가 없습니다</p>
                </div>

                {{-- 종료된 대화 섹션 --}}
                <div class="px-3 pt-4 pb-2 border-t border-gray-200 dark:border-gray-700">
                    <button @click="showClosed = !showClosed" class="flex items-center gap-1 text-sm font-semibold text-gray-700 dark:text-gray-300 w-full">
                        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-90': showClosed }" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        종료
                        <span class="text-xs font-normal text-gray-400" x-text="'(' + closedRooms.length + ')'"></span>
                    </button>
                </div>

                <template x-if="showClosed">
                    <div>
                        <template x-for="room in closedRooms" :key="room.id">
                            <div
                                @click="selectRoom(room); if (window.innerWidth < 768) mobileView = 'chat'"
                                :class="selectedRoom && selectedRoom.id === room.id ? 'bg-gray-100 dark:bg-gray-700 border-l-2 border-gray-400' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-l-2 border-transparent'"
                                class="px-3 py-2.5 cursor-pointer border-b border-gray-100 dark:border-gray-700 transition-colors opacity-60"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-medium text-sm truncate" x-text="room.visitor_name || '방문자'"></span>
                                    <span class="text-xs px-1.5 py-0.5 bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400 rounded">종료</span>
                                </div>
                                <div class="text-xs text-gray-400" x-text="formatTime(room.closed_at || room.updated_at)"></div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </aside>

        {{-- 메인 채팅 영역: 데스크톱 항상 표시, 모바일은 mobileView=chat 일 때만 --}}
        <section class="flex-1 flex-col bg-gray-50 dark:bg-gray-900 hidden md:flex"
                 :style="mobileView === 'chat' ? 'display:flex !important' : ''"
            {{-- 빈 상태 --}}
            <div x-show="!selectedRoom" x-cloak class="flex-1 flex items-center justify-center">
                <div class="text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-200 dark:text-gray-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <p class="text-gray-400 dark:text-gray-500">대화를 선택하세요</p>
                    <p class="text-xs text-gray-300 dark:text-gray-600 mt-1">좌측에서 대화를 선택하거나 대기 중인 대화를 가져오세요</p>
                </div>
            </div>

            {{-- 선택된 대화 --}}
            <template x-if="selectedRoom">
                <div class="flex-1 flex flex-col">
                    {{-- 대화 헤더 --}}
                    <div class="px-4 py-3 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shrink-0">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <button @click="mobileView = 'list'" class="md:hidden p-1 -ml-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/40 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400" x-text="(selectedRoom.visitor_name || '?')[0].toUpperCase()"></span>
                                </div>
                                <div>
                                    <span class="font-medium text-sm" x-text="selectedRoom.visitor_name || '방문자'"></span>
                                    <span
                                        :class="selectedRoom.status === 'open' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                                        class="ml-2 text-xs px-1.5 py-0.5 rounded"
                                        x-text="selectedRoom.status === 'open' ? '진행중' : '종료'"
                                    ></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    x-show="selectedRoom.status === 'open' && selectedRoom.assigned_agent_id"
                                    @click="showTransferModal = true"
                                    class="px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded transition-colors"
                                >전달</button>
                                <button
                                    x-show="selectedRoom.status === 'open'"
                                    @click="closeRoom()"
                                    class="px-3 py-1.5 text-xs bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 rounded transition-colors"
                                >종료</button>
                            </div>
                        </div>
                    </div>

                    {{-- 메시지 목록 --}}
                    <div class="flex-1 overflow-y-auto p-4 space-y-3" x-ref="messageContainer">
                        <template x-for="msg in messages" :key="msg.id">
                            <div>
                                {{-- 시스템 메시지 --}}
                                <div x-show="msg.sender_type === 'system'" class="text-center">
                                    <span class="text-xs text-gray-400 italic" x-text="msg.content"></span>
                                </div>
                                {{-- 방문자 메시지 --}}
                                <div x-show="msg.sender_type === 'visitor'" class="flex justify-start">
                                    <div class="max-w-[70%]">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1" x-text="msg.sender_name || '방문자'"></div>
                                        <div class="bg-gray-200 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm">
                                            <span x-text="msg.content"></span>
                                        </div>
                                        <div class="text-xs text-gray-400 mt-1" x-text="formatMessageTime(msg.created_at)"></div>
                                    </div>
                                </div>
                                {{-- 상담원 메시지 --}}
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

                    {{-- 타이핑 인디케이터 --}}
                    <div x-show="typingUser" x-cloak class="px-4 py-1 text-xs text-gray-400 italic" x-text="typingUser + ' 입력 중...'"></div>

                    {{-- 메시지 입력 --}}
                    <div x-show="selectedRoom.status === 'open'" class="p-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shrink-0">
                        <div x-show="!selectedRoom.assigned_agent_id" class="text-center py-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">이 대화에 응답하려면 먼저 가져오세요</p>
                            <button
                                @click="assignRoom(selectedRoom)"
                                class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                            >대화 가져오기</button>
                        </div>
                        <div x-show="selectedRoom.assigned_agent_id" class="flex gap-2">
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

                    {{-- 종료된 대화 알림 --}}
                    <div x-show="selectedRoom.status === 'closed'" class="p-3 bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 text-center shrink-0">
                        <span class="text-sm text-gray-500 dark:text-gray-400">종료된 대화입니다</span>
                    </div>
                </div>
            </template>
        </section>

        {{-- 우측 사이드바: 방문자 정보 (태블릿 이하에서 숨김) --}}
        <aside x-show="selectedRoom && showInfoPanel" x-cloak class="w-60 border-l border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shrink-0 overflow-y-auto hidden lg:block">
            <template x-if="selectedRoom">
                <div class="p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">방문자 정보</h3>
                        <button @click="showInfoPanel = false" class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- 방문자 아바타 --}}
                    <div class="flex flex-col items-center mb-4">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/40 rounded-full flex items-center justify-center mb-2">
                            <span class="text-lg font-medium text-blue-600 dark:text-blue-400" x-text="(selectedRoom.visitor_name || '?')[0].toUpperCase()"></span>
                        </div>
                        <span class="text-sm font-medium" x-text="selectedRoom.visitor_name || '방문자'"></span>
                        <span x-show="selectedRoom.visitor_email" class="text-xs text-gray-400" x-text="selectedRoom.visitor_email"></span>
                    </div>

                    {{-- 상세 정보 --}}
                    <div class="space-y-3 text-xs">
                        <div>
                            <dt class="text-gray-400 dark:text-gray-500 mb-0.5">방 ID</dt>
                            <dd class="text-gray-700 dark:text-gray-300 font-mono break-all" x-text="selectedRoom.id"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 dark:text-gray-500 mb-0.5">테넌트</dt>
                            <dd class="text-gray-700 dark:text-gray-300 font-mono break-all" x-text="selectedRoom.tenant_id"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 dark:text-gray-500 mb-0.5">상태</dt>
                            <dd>
                                <span
                                    :class="selectedRoom.status === 'open' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                                    class="px-1.5 py-0.5 rounded text-xs"
                                    x-text="selectedRoom.status === 'open' ? '진행중' : '종료'"
                                ></span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 dark:text-gray-500 mb-0.5">시작 시간</dt>
                            <dd class="text-gray-700 dark:text-gray-300" x-text="formatFullTime(selectedRoom.created_at)"></dd>
                        </div>
                        <div x-show="selectedRoom.assigned_agent_id">
                            <dt class="text-gray-400 dark:text-gray-500 mb-0.5">담당 상담원</dt>
                            <dd class="text-gray-700 dark:text-gray-300" x-text="selectedRoom.assigned_agent_id === currentAgentId ? '나' : selectedRoom.assigned_agent_id"></dd>
                        </div>
                    </div>
                </div>
            </template>
        </aside>

        {{-- 정보 패널 토글 (우측 끝, 태블릿 이하에서 숨김) --}}
        <button
            x-show="selectedRoom && !showInfoPanel"
            @click="showInfoPanel = true"
            class="absolute right-0 top-1/2 -translate-y-1/2 p-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-l-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 hidden lg:block"
            title="방문자 정보"
        >
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </button>
    </main>

    {{-- 전달 모달 --}}
    <div x-show="showTransferModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div @click.outside="showTransferModal = false" class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-[calc(100%-2rem)] max-w-96 max-h-[80vh] overflow-hidden mx-4">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-medium">대화 전달</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">다른 상담원에게 이 대화를 전달합니다</p>
            </div>
            <div class="p-4 overflow-y-auto max-h-60">
                <template x-for="agent in transferAgents" :key="agent.id">
                    <div
                        @click="transferRoom(agent.id)"
                        class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors"
                    >
                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400" x-text="(agent.name || '?')[0].toUpperCase()"></span>
                        </div>
                        <div>
                            <div class="text-sm font-medium" x-text="agent.name"></div>
                            <div class="text-xs text-gray-400" x-text="agent.email"></div>
                        </div>
                        <span x-show="agent.is_online" class="ml-auto w-2 h-2 bg-green-500 rounded-full"></span>
                    </div>
                </template>
                <div x-show="transferAgents.length === 0" class="text-center py-4">
                    <p class="text-sm text-gray-400">전달 가능한 상담원이 없습니다</p>
                </div>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                <button @click="showTransferModal = false" class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">취소</button>
            </div>
        </div>
    </div>
</div>

@push('head')
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8/dist/web/pusher.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@2/dist/echo.iife.min.js" defer></script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.Echo) return;
    var token = document.cookie.match(/shaul_access_token=([^;]+)/)?.[1] || '';
    window.Echo = new window.Echo({
        broadcaster: 'reverb',
        key: '{{ config("reverb.apps.apps.0.key", "live-chat-key") }}',
        wsHost: '{{ config("reverb.apps.apps.0.options.host", request()->getHost()) }}',
        wsPort: {{ config("reverb.apps.apps.0.options.port", 443) }},
        wssPort: {{ config("reverb.apps.apps.0.options.port", 443) }},
        forceTLS: {{ config("reverb.apps.apps.0.options.scheme", "https") === "https" ? "true" : "false" }},
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/api/broadcasting/auth',
        auth: { headers: { 'Authorization': 'Bearer ' + token } },
    });
});

function agentDashboard() {
    return {
        // State
        mobileView: 'list',
        myRooms: [],
        waitingRooms: [],
        closedRooms: [],
        selectedRoom: null,
        messages: [],
        newMessage: '',
        showClosed: false,
        showInfoPanel: true,
        showTransferModal: false,
        transferAgents: [],
        soundEnabled: localStorage.getItem('soundEnabled') !== 'false',
        refreshing: false,
        currentAgentId: null,

        // Typing
        typingUser: null,
        typingTimeout: null,
        lastTypingSent: 0,

        // Polling
        roomPollInterval: null,
        subscribedChannels: new Set(),

        get authHeaders() {
            return {
                'Authorization': 'Bearer ' + (window.__ADMIN_TOKEN || ''),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            };
        },

        async init() {
            // auth_user에서 ID 추출
            try {
                const token = window.__ADMIN_TOKEN;
                if (token) {
                    this.currentAgentId = 'admin-builtin';
                }
            } catch(e) {}

            await this.fetchRooms();
            this.roomPollInterval = setInterval(() => this.fetchRooms(), 10000);

            // 전달 모달이 열릴 때 상담원 목록 가져오기
            this.$watch('showTransferModal', async (val) => {
                if (val) {
                    await this.fetchTransferAgents();
                }
            });
        },

        async fetchTransferAgents() {
            try {
                const res = await fetch('/api/agent/agents', { headers: this.authHeaders });
                const json = await res.json();
                if (json.success) {
                    this.transferAgents = json.data || [];
                }
            } catch (e) {}
        },

        async fetchRooms() {
            try {
                const res = await fetch('/api/agent/rooms', { headers: this.authHeaders });
                const json = await res.json();
                if (json.success) {
                    this.myRooms = json.data.my_rooms || [];
                    this.waitingRooms = json.data.waiting_rooms || [];
                    this.closedRooms = json.data.closed_rooms || [];

                    // 실시간 채널 구독
                    [...this.myRooms, ...this.waitingRooms].forEach(room => {
                        this.subscribeRoom(room.id);
                    });

                    // 선택된 방 상태 동기화
                    if (this.selectedRoom) {
                        const all = [...this.myRooms, ...this.waitingRooms, ...this.closedRooms];
                        const updated = all.find(r => r.id === this.selectedRoom.id);
                        if (updated) {
                            this.selectedRoom = updated;
                        }
                    }
                }
            } catch (e) {}
        },

        async refreshRooms() {
            this.refreshing = true;
            await this.fetchRooms();
            setTimeout(() => { this.refreshing = false; }, 500);
        },

        subscribeRoom(roomId) {
            if (!window.Echo || this.subscribedChannels.has(roomId)) return;
            this.subscribedChannels.add(roomId);

            window.Echo.private('chat.' + roomId)
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
                    // 알림음 재생
                    if (this.soundEnabled && e.sender_type === 'visitor') {
                        this.playNotification();
                    }
                    // 방 목록 갱신
                    this.fetchRooms();
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
        },

        playNotification() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.frequency.value = 800;
                gain.gain.value = 0.1;
                osc.start();
                osc.stop(ctx.currentTime + 0.15);
            } catch(e) {}
        },

        async selectRoom(room) {
            this.selectedRoom = room;
            this.messages = [];
            this.typingUser = null;

            try {
                const res = await fetch(`/api/agent/rooms/${room.id}/messages`, { headers: this.authHeaders });
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

            this.subscribeRoom(room.id);
        },

        async sendMessage() {
            if (!this.newMessage.trim() || !this.selectedRoom) return;
            const content = this.newMessage.trim();
            this.newMessage = '';

            try {
                const res = await fetch(`/api/agent/rooms/${this.selectedRoom.id}/messages`, {
                    method: 'POST',
                    headers: this.authHeaders,
                    body: JSON.stringify({ content }),
                });
                const json = await res.json();
                if (json.success) {
                    if (!this.messages.find(m => m.id === json.data.id)) {
                        this.messages.push(json.data);
                    }
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
            if (now - this.lastTypingSent < 2000) return;
            this.lastTypingSent = now;
            fetch(`/api/agent/rooms/${this.selectedRoom.id}/typing`, {
                method: 'POST',
                headers: this.authHeaders,
                body: JSON.stringify({}),
            }).catch(() => {});
        },

        async assignRoom(room) {
            try {
                const res = await fetch(`/api/agent/rooms/${room.id}/assign`, {
                    method: 'POST',
                    headers: this.authHeaders,
                });
                const json = await res.json();
                if (json.success) {
                    await this.fetchRooms();
                    // 선택하고 방 상태 업데이트
                    const updated = this.myRooms.find(r => r.id === room.id);
                    if (updated) {
                        this.selectedRoom = updated;
                    }
                }
            } catch (e) {}
        },

        async closeRoom() {
            if (!this.selectedRoom) return;
            if (!confirm('이 대화를 종료하시겠습니까?')) return;

            try {
                const res = await fetch(`/api/agent/rooms/${this.selectedRoom.id}/close`, {
                    method: 'POST',
                    headers: this.authHeaders,
                });
                const json = await res.json();
                if (json.success) {
                    this.selectedRoom = json.data;
                    await this.fetchRooms();
                }
            } catch (e) {}
        },

        async transferRoom(agentId) {
            if (!this.selectedRoom) return;

            try {
                const res = await fetch(`/api/agent/rooms/${this.selectedRoom.id}/transfer`, {
                    method: 'POST',
                    headers: this.authHeaders,
                    body: JSON.stringify({ agent_id: agentId }),
                });
                const json = await res.json();
                if (json.success) {
                    this.showTransferModal = false;
                    this.selectedRoom = null;
                    this.messages = [];
                    await this.fetchRooms();
                }
            } catch (e) {}
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

        formatFullTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.getFullYear() + '-' +
                (d.getMonth() + 1).toString().padStart(2, '0') + '-' +
                d.getDate().toString().padStart(2, '0') + ' ' +
                d.getHours().toString().padStart(2, '0') + ':' +
                d.getMinutes().toString().padStart(2, '0');
        },
    };
}

</script>
@endpush
@endsection
