<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use App\Models\Customer;
use App\Models\Service;
use Filament\Notifications\Notification;

class WhatsAppChatManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string $view = 'filament.pages.whatsapp-chat-manager';
    protected static ?string $navigationLabel = 'WhatsApp Chat';
    protected static ?string $title = 'WhatsApp Chat Manager';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Communication';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['admin', 'staff']);
    }

    public array $chats = [];
    public array $messages = [];
    public string $selectedChatId = '';
    public string $selectedChatName = '';
    public string $newMessage = '';
    public bool $isLoading = false;
    public bool $isConnected = false;
    public array $messageTemplates = [];
    public string $searchQuery = '';
    public array $customerInfo = [];

    protected $listeners = [
        'refreshChats' => 'loadChats',
        'refreshMessages' => 'loadMessages',
        'echo:whatsapp-updates,MessageReceived' => 'handleNewMessage',
    ];

    public function mount(): void
    {
        $this->checkWhatsAppStatus();
        $this->loadMessageTemplates();
        $this->loadChats();
    }

    public function checkWhatsAppStatus(): void
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-API-Key' => config('services.whatsapp.api_key')])
                ->get(config('services.whatsapp.api_url') . '/session/status');

            if ($response->successful()) {
                $data = $response->json();
                $this->isConnected = $data['success'] && $data['isReady'];
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp status check failed: ' . $e->getMessage());
            $this->isConnected = false;
        }
    }

    public function loadChats(): void
    {
        if (!$this->isConnected) {
            return;
        }

        try {
            $this->isLoading = true;

            $response = Http::timeout(15)
                ->withHeaders(['X-API-Key' => config('services.whatsapp.api_key')])
                ->get(config('services.whatsapp.api_url') . '/chats/list');

            if ($response->successful()) {
                $chats = $response->json()['chats'] ?? [];

                // Enhance chats with customer information
                $this->chats = collect($chats)->map(function ($chat) {
                    $phoneNumber = $this->extractPhoneNumber($chat['id']);
                    $customer = Customer::where('phone', $phoneNumber)->first();

                    return [
                        'id' => $chat['id'],
                        'name' => $customer ? $customer->name : ($chat['name'] ?? $phoneNumber),
                        'lastMessage' => $chat['lastMessage']['body'] ?? '',
                        'timestamp' => $chat['timestamp'] ?? time(),
                        'unreadCount' => $chat['unreadCount'] ?? 0,
                        'isGroup' => $chat['isGroup'] ?? false,
                        'customer' => $customer,
                        'phoneNumber' => $phoneNumber,
                        'profilePic' => $chat['profilePic'] ?? null,
                    ];
                })->filter(function ($chat) {
                    // Filter by search query if provided
                    if (empty($this->searchQuery)) {
                        return true;
                    }

                    return str_contains(strtolower($chat['name']), strtolower($this->searchQuery)) ||
                        str_contains($chat['phoneNumber'], $this->searchQuery);
                })->sortByDesc('timestamp')->values()->toArray();
            }
        } catch (\Exception $e) {
            Log::error('Failed to load chats: ' . $e->getMessage());
            Notification::make()
                ->title('Error loading chats')
                ->body('Failed to load WhatsApp conversations')
                ->danger()
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectChat(string $chatId, string $chatName): void
    {
        $this->selectedChatId = $chatId;
        $this->selectedChatName = $chatName;
        $this->loadMessages();
        $this->loadCustomerInfo();
        $this->markAsRead();
    }

    public function loadMessages(): void
    {
        if (empty($this->selectedChatId)) {
            return;
        }

        try {
            $this->isLoading = true;

            $response = Http::timeout(15)
                ->withHeaders(['X-API-Key' => config('services.whatsapp.api_key')])
                ->post(config('services.whatsapp.api_url') . '/chats/messages', [
                    'chatId' => $this->selectedChatId,
                    'limit' => 50
                ]);

            if ($response->successful()) {
                $this->messages = $response->json()['messages'] ?? [];

                // Process messages for better display
                $this->messages = collect($this->messages)->map(function ($message) {
                    return [
                        'id' => $message['id'],
                        'body' => $message['body'] ?? '',
                        'fromMe' => $message['fromMe'] ?? false,
                        'timestamp' => $message['timestamp'] ?? time(),
                        'type' => $message['type'] ?? 'text',
                        'hasMedia' => $message['hasMedia'] ?? false,
                        'mediaUrl' => $message['mediaUrl'] ?? null,
                        'caption' => $message['caption'] ?? '',
                        'ack' => $message['ack'] ?? 0,
                        'isForwarded' => $message['isForwarded'] ?? false,
                        'author' => $message['author'] ?? null,
                    ];
                })->sortBy('timestamp')->values()->toArray();
            }
        } catch (\Exception $e) {
            Log::error('Failed to load messages: ' . $e->getMessage());
            Notification::make()
                ->title('Error loading messages')
                ->body('Failed to load conversation messages')
                ->danger()
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }

    public function sendMessage(): void
    {
        if (empty($this->newMessage) || empty($this->selectedChatId)) {
            return;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-API-Key' => config('services.whatsapp.api_key')])
                ->post(config('services.whatsapp.api_url') . '/message/send', [
                    'chatId' => $this->selectedChatId,
                    'message' => $this->newMessage
                ]);

            if ($response->successful()) {
                $this->newMessage = '';
                $this->loadMessages();
                $this->loadChats(); // Refresh chat list to update last message

                Notification::make()
                    ->title('Message sent')
                    ->success()
                    ->send();
            } else {
                throw new \Exception('Failed to send message');
            }
        } catch (\Exception $e) {
            Log::error('Failed to send message: ' . $e->getMessage());
            Notification::make()
                ->title('Error sending message')
                ->body('Failed to send WhatsApp message')
                ->danger()
                ->send();
        }
    }

    public function useTemplate(string $template): void
    {
        $this->newMessage = $template;
    }

    public function markAsRead(): void
    {
        if (empty($this->selectedChatId)) {
            return;
        }

        try {
            Http::timeout(5)
                ->withHeaders(['X-API-Key' => config('services.whatsapp.api_key')])
                ->post(config('services.whatsapp.api_url') . '/chats/mark-read', [
                    'chatId' => $this->selectedChatId
                ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark as read: ' . $e->getMessage());
        }
    }

    private function extractPhoneNumber(string $chatId): string
    {
        // Extract phone number from WhatsApp chat ID (format: 628123456789@c.us)
        return str_replace(['@c.us', '@g.us'], '', $chatId);
    }

    private function loadCustomerInfo(): void
    {
        $phoneNumber = $this->extractPhoneNumber($this->selectedChatId);
        $customer = Customer::where('phone', $phoneNumber)->first();

        if ($customer) {
            $this->customerInfo = [
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'totalServices' => Service::where('customer_id', $customer->id)->count(),
                'lastService' => Service::where('customer_id', $customer->id)
                    ->latest()
                    ->first()?->created_at?->format('d M Y'),
            ];
        } else {
            $this->customerInfo = [
                'name' => 'Unknown Customer',
                'phone' => $phoneNumber,
                'isNewCustomer' => true,
            ];
        }
    }

    private function loadMessageTemplates(): void
    {
        $this->messageTemplates = [
            'greeting' => 'Halo! Terima kasih telah menghubungi Hartono Motor. Ada yang bisa kami bantu?',
            'service_reminder' => 'Halo! Kendaraan Anda sudah waktunya untuk service berkala. Silakan booking jadwal yang sesuai.',
            'service_complete' => 'Service kendaraan Anda telah selesai. Silakan ambil kendaraan Anda. Terima kasih!',
            'spare_parts' => 'Spare part yang Anda cari tersedia. Silakan datang ke workshop untuk informasi lebih lanjut.',
            'location' => 'Lokasi Hartono Motor: [Alamat Workshop]. Buka Senin-Sabtu 08:00-17:00.',
            'thank_you' => 'Terima kasih telah mempercayakan kendaraan Anda kepada Hartono Motor!',
        ];
    }

    #[On('refreshChats')]
    public function refreshChats(): void
    {
        $this->loadChats();
    }

    public function handleNewMessage($event): void
    {
        // Handle real-time message updates
        if (isset($event['chatId']) && $event['chatId'] === $this->selectedChatId) {
            $this->loadMessages();
        }
        $this->loadChats();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->loadChats()),

            Action::make('status')
                ->label($this->isConnected ? 'Connected' : 'Disconnected')
                ->icon($this->isConnected ? 'heroicon-o-signal' : 'heroicon-o-signal-slash')
                ->color($this->isConnected ? 'success' : 'danger')
                ->disabled(),
        ];
    }
}
