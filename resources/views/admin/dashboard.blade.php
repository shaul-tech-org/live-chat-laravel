@extends('layouts.admin')

@section('content')
<div class="h-full">
    {{-- 채팅 탭 --}}
    <div x-show="activeTab === 'chat'" x-cloak class="h-full">
        @include('admin.partials.chat')
    </div>

    {{-- 테넌트 탭 --}}
    <div x-show="activeTab === 'tenants'" x-cloak class="h-full overflow-y-auto p-4">
        @include('admin.partials.tenants')
    </div>

    {{-- 상담원 탭 --}}
    <div x-show="activeTab === 'agents'" x-cloak class="h-full overflow-y-auto p-4">
        @include('admin.partials.agents')
    </div>

    {{-- 피드백 탭 --}}
    <div x-show="activeTab === 'feedbacks'" x-cloak class="h-full overflow-y-auto p-4">
        @include('admin.partials.feedbacks')
    </div>

    {{-- FAQ 탭 --}}
    <div x-show="activeTab === 'faq'" x-cloak class="h-full overflow-y-auto p-4">
        @include('admin.partials.faq')
    </div>

    {{-- 통계 탭 --}}
    <div x-show="activeTab === 'stats'" x-cloak class="h-full overflow-y-auto p-4">
        @include('admin.partials.stats')
    </div>
</div>
@endsection
