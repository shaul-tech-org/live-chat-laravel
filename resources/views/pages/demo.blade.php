@extends('layouts.app')

@section('title', 'LCHAT 위젯 데모')

@section('body')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-3xl mx-auto px-4 py-12">
        <h1 class="text-3xl font-bold mb-4">LCHAT 위젯 데모</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            이 페이지는 LCHAT 실시간 채팅 위젯의 데모 페이지입니다.
            우측 하단에 표시되는 채팅 버튼을 클릭하여 상담을 시작할 수 있습니다.
        </p>
        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg text-sm text-yellow-700 dark:text-yellow-400">
            <strong>참고:</strong> 실제 운영 환경에서는 <code class="bg-yellow-100 dark:bg-yellow-900/40 px-1 py-0.5 rounded">data-api-key</code> 속성에
            테넌트별로 발급받은 API 키를 설정해야 합니다.
        </div>
    </div>
</div>

<script src="/js/widget.js" data-api-key="demo" data-reverb-key="" data-reverb-host="" data-reverb-port="443"></script>
@endsection
