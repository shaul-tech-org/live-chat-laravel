<?php

namespace App\Http\Resources;

use App\Models\Agent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WidgetConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $config = $this->widget_config ?? [];

        return [
            'widget_config' => (object) $config,
            'prechat_fields' => $config['prechat_fields'] ?? $this->defaultPrechatFields(),
            'business_hours' => $config['business_hours'] ?? null,
            'is_within_business_hours' => $this->calculateIsWithinBusinessHours($config),
            'auto_reply_message' => $this->auto_reply_message,
            'tenant_name' => $this->name,
            'agents_online' => Agent::where('tenant_id', $this->id)
                ->where('is_online', true)
                ->where('is_active', true)
                ->count(),
        ];
    }

    /**
     * 기본 프리챗 필드 설정
     */
    private function defaultPrechatFields(): array
    {
        return [
            ['name' => 'name', 'label' => '이름', 'type' => 'text', 'required' => true],
            ['name' => 'email', 'label' => '이메일', 'type' => 'email', 'required' => false],
            ['name' => 'phone', 'label' => '전화번호', 'type' => 'tel', 'required' => false],
        ];
    }

    /**
     * 현재 시간이 운영시간 내인지 서버 사이드에서 계산
     */
    private function calculateIsWithinBusinessHours(array $config): bool
    {
        $bh = $config['business_hours'] ?? null;

        if (!$bh || !($bh['enabled'] ?? false)) {
            return true; // 운영시간 미설정 시 항상 운영 중으로 간주
        }

        $timezone = $bh['timezone'] ?? 'Asia/Seoul';
        $schedule = $bh['schedule'] ?? [];

        try {
            $now = Carbon::now($timezone);
        } catch (\Exception $e) {
            return true; // 잘못된 타임존이면 운영 중으로 간주
        }

        $dayMap = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $dayKey = $dayMap[$now->dayOfWeekIso - 1]; // 1=Mon ... 7=Sun

        if (!isset($schedule[$dayKey])) {
            return false; // 해당 요일 스케줄 없으면 비운영
        }

        $daySchedule = $schedule[$dayKey];
        $start = $daySchedule['start'] ?? null;
        $end = $daySchedule['end'] ?? null;

        if (!$start || !$end) {
            return false;
        }

        $currentTime = $now->format('H:i');
        return $currentTime >= $start && $currentTime < $end;
    }
}
