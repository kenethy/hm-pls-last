<?php

namespace App\Filament\Resources\WhatsAppMessageResource\Widgets;

use App\Models\WhatsAppMessage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WhatsAppMessageStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalMessages = WhatsAppMessage::count();
        $sentMessages = WhatsAppMessage::where('status', 'sent')->count();
        $failedMessages = WhatsAppMessage::where('status', 'failed')->count();
        $pendingMessages = WhatsAppMessage::where('status', 'pending')->count();
        $automatedMessages = WhatsAppMessage::where('is_automated', true)->count();

        // Calculate success rate
        $successRate = $totalMessages > 0 ? round(($sentMessages / $totalMessages) * 100, 1) : 0;

        // Get today's messages
        $todayMessages = WhatsAppMessage::whereDate('created_at', today())->count();

        return [
            Stat::make('Total Pesan', $totalMessages)
                ->description('Semua pesan WhatsApp')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('primary'),

            Stat::make('Pesan Terkirim', $sentMessages)
                ->description("Tingkat keberhasilan: {$successRate}%")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Pesan Gagal', $failedMessages)
                ->description('Perlu dikirim ulang')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Pesan Menunggu', $pendingMessages)
                ->description('Dalam antrian')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Pesan Otomatis', $automatedMessages)
                ->description('Dari sistem')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('info'),

            Stat::make('Hari Ini', $todayMessages)
                ->description('Pesan hari ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('gray'),
        ];
    }
}
