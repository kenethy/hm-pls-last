<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 h-[calc(100vh-200px)]">
        
        {{-- Chat List Sidebar --}}
        <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col">
            {{-- Search Header --}}
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="relative">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model.live.debounce.300ms="searchQuery"
                            placeholder="Search conversations..."
                            class="pl-10"
                        />
                    </x-filament::input.wrapper>
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400" />
                    </div>
                </div>
            </div>

            {{-- Connection Status --}}
            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 rounded-full {{ $isConnected ? 'bg-green-500' : 'bg-red-500' }}"></div>
                    <span class="text-sm {{ $isConnected ? 'text-green-600' : 'text-red-600' }}">
                        {{ $isConnected ? 'WhatsApp Connected' : 'WhatsApp Disconnected' }}
                    </span>
                </div>
            </div>

            {{-- Chat List --}}
            <div class="flex-1 overflow-y-auto">
                @if($isLoading && empty($chats))
                    <div class="p-4 text-center">
                        <x-filament::loading-indicator class="h-6 w-6 mx-auto" />
                        <p class="text-sm text-gray-500 mt-2">Loading conversations...</p>
                    </div>
                @elseif(empty($chats))
                    <div class="p-4 text-center">
                        <x-heroicon-o-chat-bubble-left-right class="h-12 w-12 mx-auto text-gray-400 mb-2" />
                        <p class="text-sm text-gray-500">No conversations found</p>
                    </div>
                @else
                    @foreach($chats as $chat)
                        <div 
                            wire:click="selectChat('{{ $chat['id'] }}', '{{ addslashes($chat['name']) }}')"
                            class="p-4 border-b border-gray-100 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors {{ $selectedChatId === $chat['id'] ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-l-blue-500' : '' }}"
                        >
                            <div class="flex items-start space-x-3">
                                {{-- Profile Picture --}}
                                <div class="flex-shrink-0">
                                    @if($chat['profilePic'])
                                        <img src="{{ $chat['profilePic'] }}" alt="{{ $chat['name'] }}" class="w-10 h-10 rounded-full">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                            <x-heroicon-o-user class="h-6 w-6 text-gray-500" />
                                        </div>
                                    @endif
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                            {{ $chat['name'] }}
                                        </h3>
                                        <div class="flex items-center space-x-1">
                                            @if($chat['unreadCount'] > 0)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    {{ $chat['unreadCount'] }}
                                                </span>
                                            @endif
                                            <span class="text-xs text-gray-500">
                                                {{ \Carbon\Carbon::createFromTimestamp($chat['timestamp'])->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <p class="text-sm text-gray-500 truncate mt-1">
                                        {{ $chat['lastMessage'] ?: 'No messages yet' }}
                                    </p>
                                    
                                    @if($chat['customer'])
                                        <div class="flex items-center mt-1">
                                            <x-heroicon-o-user-circle class="h-4 w-4 text-green-500 mr-1" />
                                            <span class="text-xs text-green-600 dark:text-green-400">Customer</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Chat Messages Area --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col">
            @if(empty($selectedChatId))
                {{-- No Chat Selected --}}
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <x-heroicon-o-chat-bubble-left-right class="h-16 w-16 mx-auto text-gray-400 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Select a conversation</h3>
                        <p class="text-gray-500">Choose a conversation from the sidebar to start messaging</p>
                    </div>
                </div>
            @else
                {{-- Chat Header --}}
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $selectedChatName }}</h2>
                            <p class="text-sm text-gray-500">{{ $this->extractPhoneNumber($selectedChatId) }}</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <x-filament::button
                                wire:click="loadMessages"
                                size="sm"
                                color="gray"
                                icon="heroicon-o-arrow-path"
                            >
                                Refresh
                            </x-filament::button>
                        </div>
                    </div>
                </div>

                {{-- Messages Area --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-4" id="messages-container">
                    @if($isLoading && empty($messages))
                        <div class="text-center">
                            <x-filament::loading-indicator class="h-6 w-6 mx-auto" />
                            <p class="text-sm text-gray-500 mt-2">Loading messages...</p>
                        </div>
                    @elseif(empty($messages))
                        <div class="text-center">
                            <p class="text-gray-500">No messages in this conversation</p>
                        </div>
                    @else
                        @foreach($messages as $message)
                            <div class="flex {{ $message['fromMe'] ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg {{ $message['fromMe'] ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100' }}">
                                    {{-- Message Content --}}
                                    @if($message['type'] === 'text')
                                        <p class="text-sm whitespace-pre-wrap">{{ $message['body'] }}</p>
                                    @elseif($message['hasMedia'])
                                        @if($message['type'] === 'image')
                                            <div class="mb-2">
                                                @if($message['mediaUrl'])
                                                    <img src="{{ $message['mediaUrl'] }}" alt="Image" class="rounded max-w-full h-auto">
                                                @else
                                                    <div class="bg-gray-300 dark:bg-gray-600 rounded p-4 text-center">
                                                        <x-heroicon-o-photo class="h-8 w-8 mx-auto mb-2" />
                                                        <p class="text-xs">Image</p>
                                                    </div>
                                                @endif
                                            </div>
                                            @if($message['caption'])
                                                <p class="text-sm">{{ $message['caption'] }}</p>
                                            @endif
                                        @elseif($message['type'] === 'document')
                                            <div class="flex items-center space-x-2">
                                                <x-heroicon-o-document class="h-6 w-6" />
                                                <span class="text-sm">Document</span>
                                            </div>
                                        @elseif($message['type'] === 'audio' || $message['type'] === 'ptt')
                                            <div class="flex items-center space-x-2">
                                                <x-heroicon-o-speaker-wave class="h-6 w-6" />
                                                <span class="text-sm">{{ $message['type'] === 'ptt' ? 'Voice Message' : 'Audio' }}</span>
                                            </div>
                                        @else
                                            <p class="text-sm">{{ ucfirst($message['type']) }} message</p>
                                        @endif
                                    @endif

                                    {{-- Message Info --}}
                                    <div class="flex items-center justify-between mt-1">
                                        <span class="text-xs opacity-75">
                                            {{ \Carbon\Carbon::createFromTimestamp($message['timestamp'])->format('H:i') }}
                                        </span>
                                        @if($message['fromMe'])
                                            <div class="flex items-center space-x-1">
                                                @if($message['ack'] >= 3)
                                                    <x-heroicon-o-check-circle class="h-3 w-3 text-blue-200" />
                                                @elseif($message['ack'] >= 2)
                                                    <x-heroicon-o-check class="h-3 w-3 text-blue-200" />
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Message Input --}}
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <form wire:submit="sendMessage" class="flex space-x-2">
                        <div class="flex-1">
                            <x-filament::input.wrapper>
                                <x-filament::input
                                    type="text"
                                    wire:model="newMessage"
                                    placeholder="Type a message..."
                                    wire:keydown.enter="sendMessage"
                                />
                            </x-filament::input.wrapper>
                        </div>
                        <x-filament::button
                            type="submit"
                            :disabled="empty($newMessage)"
                            icon="heroicon-o-paper-airplane"
                        >
                            Send
                        </x-filament::button>
                    </form>
                </div>
            @endif
        </div>

        {{-- Customer Info & Templates Sidebar --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Customer Information --}}
            @if(!empty($selectedChatId))
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Customer Info</h3>
                    
                    @if(!empty($customerInfo))
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Name</label>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ $customerInfo['name'] ?? 'Unknown' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Phone</label>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ $customerInfo['phone'] ?? 'Unknown' }}</p>
                            </div>
                            @if(isset($customerInfo['totalServices']))
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Total Services</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $customerInfo['totalServices'] }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Last Service</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $customerInfo['lastService'] ?? 'Never' }}</p>
                                </div>
                            @endif
                            @if(isset($customerInfo['isNewCustomer']))
                                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                        <x-heroicon-o-exclamation-triangle class="h-4 w-4 inline mr-1" />
                                        New customer - not in database
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            {{-- Message Templates --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Templates</h3>
                
                <div class="space-y-2">
                    @foreach($messageTemplates as $key => $template)
                        <x-filament::button
                            wire:click="useTemplate('{{ addslashes($template) }}')"
                            size="sm"
                            color="gray"
                            class="w-full justify-start text-left"
                            :disabled="empty($selectedChatId)"
                        >
                            {{ ucfirst(str_replace('_', ' ', $key)) }}
                        </x-filament::button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Auto-scroll to bottom script --}}
    <script>
        document.addEventListener('livewire:updated', () => {
            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });
    </script>
</x-filament-panels::page>
