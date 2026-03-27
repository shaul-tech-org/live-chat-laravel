@extends('layouts.app')

@section('title', 'LCHAT 위젯 데모')

@section('body')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-4xl mx-auto px-4 py-12">
        {{-- Header --}}
        <div class="mb-10">
            <h1 class="text-3xl font-bold mb-2">LCHAT 위젯 데모</h1>
            <p class="text-gray-600 dark:text-gray-400">
                실시간 채팅 위젯을 웹사이트에 손쉽게 추가하세요. 아래에서 설치 방법을 확인하고, 우측 하단의 채팅 버튼으로 직접 체험해 보세요.
            </p>
        </div>

        {{-- Installation Guide --}}
        <section class="mb-8">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
                위젯 설치
            </h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">HTML &lt;body&gt; 태그 닫기 직전에 아래 스크립트를 추가하세요</span>
                </div>
                <div class="p-4 relative group">
                    <pre class="text-sm bg-gray-900 text-green-400 rounded-lg p-4 overflow-x-auto"><code>&lt;script
  src="https://lchat.shaul.kr/js/widget.js"
  data-api-key="YOUR_API_KEY"
  data-reverb-key="YOUR_REVERB_KEY"
  data-reverb-host="lchat.shaul.kr"
  data-reverb-port="443"&gt;
&lt;/script&gt;</code></pre>
                    <button
                        onclick="navigator.clipboard.writeText(this.closest('.relative').querySelector('code').textContent);this.textContent='복사됨!';setTimeout(()=>this.textContent='복사',1500)"
                        class="absolute top-6 right-6 px-3 py-1 text-xs bg-gray-700 hover:bg-gray-600 text-gray-300 rounded transition-colors opacity-0 group-hover:opacity-100"
                    >복사</button>
                </div>
            </div>
        </section>

        {{-- Configuration Options --}}
        <section class="mb-8">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                설정 옵션
            </h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">속성</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">필수</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">설명</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr>
                            <td class="px-4 py-3"><code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">data-api-key</code></td>
                            <td class="px-4 py-3 text-center"><span class="text-red-500 font-bold">*</span></td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">테넌트별 발급 API 키</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">data-reverb-key</code></td>
                            <td class="px-4 py-3 text-center"><span class="text-red-500 font-bold">*</span></td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">Laravel Reverb 앱 키 (실시간 통신용)</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">data-reverb-host</code></td>
                            <td class="px-4 py-3 text-center"><span class="text-red-500 font-bold">*</span></td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">Reverb WebSocket 호스트</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">data-reverb-port</code></td>
                            <td class="px-4 py-3 text-center"></td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">Reverb 포트 (기본값: 443)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- API Documentation --}}
        <section class="mb-8">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                API 엔드포인트
            </h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                <div class="p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 text-xs font-bold rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">GET</span>
                        <code class="text-sm">/api/widget/config</code>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">위젯 설정 조회 (테마, FAQ 등)</p>
                </div>
                <div class="p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 text-xs font-bold rounded bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">POST</span>
                        <code class="text-sm">/api/rooms</code>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">새 채팅방 생성</p>
                </div>
                <div class="p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 text-xs font-bold rounded bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">POST</span>
                        <code class="text-sm">/api/rooms/{id}/messages</code>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">메시지 전송</p>
                </div>
                <div class="p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 text-xs font-bold rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">GET</span>
                        <code class="text-sm">/api/rooms/{id}/messages</code>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">메시지 목록 조회</p>
                </div>
                <div class="p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 text-xs font-bold rounded bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">POST</span>
                        <code class="text-sm">/api/feedbacks</code>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">상담 평가 전송</p>
                </div>
                <div class="p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 text-xs font-bold rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">GET</span>
                        <code class="text-sm">/api/health</code>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">서비스 상태 확인</p>
                </div>
            </div>
        </section>

        {{-- Live Demo Notice --}}
        <section class="mb-8">
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-medium text-blue-800 dark:text-blue-300 mb-1">라이브 데모</p>
                        <p class="text-sm text-blue-700 dark:text-blue-400">
                            우측 하단의 채팅 버튼을 클릭하여 위젯을 체험해 보세요.
                            이 데모 페이지에서는 <code class="bg-blue-100 dark:bg-blue-900/40 px-1 py-0.5 rounded">demo</code> API 키가 사용됩니다.
                            실제 운영 환경에서는 Admin 대시보드에서 발급받은 테넌트 API 키를 사용하세요.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script src="/js/widget.js" data-api-key="demo" data-reverb-key="" data-reverb-host="" data-reverb-port="443"></script>
@endsection
