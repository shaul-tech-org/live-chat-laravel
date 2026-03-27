@extends('layouts.app')

@section('title', '관리자 - LCHAT')

@section('body')
<div class="flex flex-col h-screen" x-data="adminApp()">
    {{-- 헤더 --}}
    <header class="flex items-center justify-between px-4 py-2 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shrink-0">
        <div class="flex items-center gap-3">
            <h1 class="text-lg font-bold">LCHAT</h1>
            <span class="text-xs text-gray-500 dark:text-gray-400">관리자</span>
        </div>
        <div class="flex items-center gap-3">
            <button @click="soundEnabled = !soundEnabled" class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" :title="soundEnabled ? '알림 음소거' : '알림 켜기'">
                <span x-show="soundEnabled">🔔</span>
                <span x-show="!soundEnabled">🔕</span>
            </button>
            <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="다크 모드">
                <span x-show="!darkMode">🌙</span>
                <span x-show="darkMode">☀️</span>
            </button>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="px-3 py-1 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 rounded">로그아웃</button>
            </form>
        </div>
    </header>

    {{-- 탭 네비게이션 --}}
    <nav class="flex border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 shrink-0 overflow-x-auto">
        <template x-for="tab in tabs" :key="tab.id">
            <button
                @click="activeTab = tab.id"
                :class="activeTab === tab.id ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors"
                x-text="tab.label"
            ></button>
        </template>
    </nav>

    {{-- 탭 콘텐츠 --}}
    <main class="flex-1 overflow-hidden">
        @yield('content')
    </main>
</div>

@push('scripts')
<script>
function adminApp() {
    return {
        activeTab: 'chat',
        soundEnabled: localStorage.getItem('soundEnabled') !== 'false',
        tabs: [
            { id: 'chat', label: '채팅' },
            { id: 'tenants', label: '테넌트' },
            { id: 'agents', label: '상담원' },
            { id: 'feedbacks', label: '피드백' },
            { id: 'faq', label: 'FAQ' },
            { id: 'stats', label: '통계' },
        ],
    };
}
</script>
@endpush
@endsection
