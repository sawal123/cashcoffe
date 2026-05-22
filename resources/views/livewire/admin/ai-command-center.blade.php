<div>
    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <div
                class="w-12 h-12 bg-gradient-to-tr from-violet-600 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-violet-500/20 text-white">
                <iconify-icon icon="solar:chatbot-bold-duotone" class="text-2xl animate-pulse"></iconify-icon>
            </div>
            <div>
                <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">AI Command Center</h6>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Autopilot & Chatbot Interaktif Manajemen Master
                    Data Kasir</p>
            </div>
        </div>
        <x-breadcrumb title="AI Command Center" />
    </div>

    <!-- Alert / Toast Container -->
    <x-toast />

    <!-- Chatbot Container & AI Panel -->
    <div x-data="{
        scrollToBottom() {
            $nextTick(() => {
                const el = $refs.chatContainer;
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            });
        }
    }" x-init="scrollToBottom()" x-on:chat-message-added.window="scrollToBottom()"
        class="bg-white dark:bg-neutral-800 border-2 border-neutral-100 dark:border-neutral-700 shadow-sm rounded-[2rem] p-6 md:p-8 relative overflow-hidden mb-8 text-neutral-800 dark:text-white animate-fade-in">
        <!-- Abstract glowing background circles -->
        <div
            class="absolute -top-24 -right-24 w-72 h-72 bg-violet-600/5 dark:bg-violet-600/10 rounded-full blur-3xl pointer-events-none">
        </div>
        <div
            class="absolute -bottom-24 -left-24 w-72 h-72 bg-indigo-600/5 dark:bg-indigo-600/10 rounded-full blur-3xl pointer-events-none">
        </div>

        <div class="relative z-10 space-y-6">
            <!-- Autopilot Header Status -->
            <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-700/80 pb-4">
                <div class="flex items-center gap-3">
                    <div class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></div>
                    <span
                        class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Asisten
                        Chat Autopilot Aktif</span>
                </div>
                <div class="text-[10px] text-neutral-400 dark:text-neutral-500">
                    Model: <span class="font-mono text-neutral-500 dark:text-neutral-400">gpt-4o-mini</span>
                </div>
            </div>

            <!-- Chat Message Timeline (Scrollable) -->
            <div x-ref="chatContainer"
                class="max-h-[450px] overflow-y-auto pr-2 space-y-4 scrollbar-thin scrollbar-thumb-neutral-200 dark:scrollbar-thumb-neutral-700 scrollbar-track-transparent flex flex-col">
                @foreach ($chatHistory as $chat)
                    @if ($chat['sender'] === 'user')
                        <!-- User Message (Aligned Right) -->
                        <div class="flex items-start justify-end p-2 gap-3 self-end w-full max-w-[85%] sm:max-w-[70%]">
                            <div class="flex  flex-col items-end w-full">
                                <div
                                    class="bg-gradient-to-tr from-violet-600 to-indigo-600 text-white rounded-2xl rounded-tr-none p-2 shadow-md">
                                    <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $chat['text'] }}</p>
                                </div>
                                <span
                                    class="text-[9px] text-neutral-400 dark:text-neutral-500 mt-1 mr-1">{{ $chat['time'] }}</span>
                            </div>
                            <div
                                class="w-8 h-8 rounded-full p-2 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-800/50 flex items-center justify-center shrink-0 text-indigo-600 dark:text-indigo-300 font-bold text-xs shadow-inner">
                                SA
                            </div>
                        </div>
                    @else
                        <!-- AI Message (Aligned Left) -->
                        <div class="flex items-start gap-3 w-full max-w-[85%] sm:max-w-[70%]">
                            <div
                                class="w-8 h-8 rounded-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 flex items-center justify-center shrink-0 shadow-sm text-indigo-600 dark:text-indigo-400">
                                <iconify-icon icon="solar:chatbot-bold-duotone" class="text-lg"></iconify-icon>
                            </div>
                            <div class="flex flex-col items-start w-full">
                                <div
                                    class="bg-neutral-50 p-3 dark:bg-neutral-900/80 border border-neutral-200 dark:border-neutral-800/60 text-neutral-800 dark:text-neutral-200 rounded-2xl rounded-tl-none px-4.5 py-3 shadow-sm">
                                    <p class="text-sm leading-relaxed whitespace-pre-wrap">{!! $this->formatMessage($chat['text']) !!}</p>
                                    @if (!empty($chat['redirect_url']))
                                        <div
                                            class="mt-2 pt-2 border-t border-neutral-200/60 dark:border-neutral-800/60 flex">
                                            <a href="{{ $chat['redirect_url'] }}" wire:navigate
                                                class="inline-flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-semibold transition-colors duration-200">
                                                <iconify-icon icon="solar:link-round-bold"
                                                    class="text-sm"></iconify-icon>
                                                Buka Halaman:
                                                {{ ucwords(str_replace('-', ' ', trim($chat['redirect_url'], '/'))) ?: 'Dashboard' }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                                <span
                                    class="text-[9px] text-neutral-400 dark:text-neutral-500 mt-1 ml-1">{{ $chat['time'] }}</span>
                            </div>
                        </div>
                    @endif
                @endforeach

                <!-- Bouncing typing indicator -->
                <div wire:loading wire:target="executeCommand"
                    class="flex items-start gap-3 w-full max-w-[85%] sm:max-w-[70%]">
                    <div
                        class="w-8 h-8 rounded-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 flex items-center justify-center shrink-0 shadow-sm text-indigo-600 dark:text-indigo-400">
                        <iconify-icon icon="solar:chatbot-bold-duotone" class="text-lg"></iconify-icon>
                    </div>
                    <div
                        class="bg-neutral-50 dark:bg-neutral-900/80 border border-neutral-200 dark:border-neutral-800/60 text-neutral-400 rounded-2xl rounded-tl-none px-4.5 py-3 shadow-sm">
                        <div class="flex items-center gap-1 py-1 px-1">
                            <div class="w-2 h-2 bg-indigo-500 dark:bg-indigo-400 rounded-full animate-bounce"
                                style="animation-delay: 0ms"></div>
                            <div class="w-2 h-2 bg-indigo-500 dark:bg-indigo-400 rounded-full animate-bounce"
                                style="animation-delay: 150ms"></div>
                            <div class="w-2 h-2 bg-indigo-500 dark:bg-indigo-400 rounded-full animate-bounce"
                                style="animation-delay: 300ms"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Input Form & Actions -->
            <form wire:submit.prevent="executeCommand"
                class="pt-4 border-t border-neutral-100 dark:border-neutral-700/80">
                <div class="flex items-center gap-3">
                    <div class="flex-1">
                        <x-ui.input type="text" wire:model="commandText"
                            placeholder="Tulis pesan atau instruksi harga di sini... (misal: 'ubah kopi susu tier Medan jadi 25k' atau 'halo')"
                            required class="!py-3.5"
                            prefix="<iconify-icon icon='solar:keyboard-linear' class='text-xl text-neutral-400 dark:text-neutral-500'></iconify-icon>" />
                    </div>
                    <x-ui.button type="submit" color="purple" wire:loading.attr="disabled"
                        class="!px-6 !py-3.5 flex items-center gap-2 shrink-0">
                        <iconify-icon icon="solar:plain-bold" class="text-base"></iconify-icon>
                        <span>Kirim</span>
                    </x-ui.button>
                </div>
            </form>

            <!-- Quick Template Suggestions -->
            <div class="space-y-2 pt-1">
                <span
                    class="block text-[10px] font-bold text-neutral-400 dark:text-neutral-500 uppercase tracking-widest">Templat
                    Obrolan Cepat:</span>
                <div class="flex flex-wrap gap-2.5">
                    <button type="button" wire:click="$set('commandText', 'Ganti harga kopi susu tier Medan jadi 25k')"
                        class="bg-neutral-50 hover:bg-neutral-100 dark:bg-neutral-900/40 dark:hover:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 hover:border-neutral-300 dark:hover:border-neutral-700 text-neutral-600 dark:text-neutral-400 hover:text-neutral-800 dark:hover:text-neutral-200 px-3 py-1.5 rounded-xl text-xs font-semibold transition duration-200 flex items-center gap-1.5">
                        <iconify-icon icon="solar:bolt-circle-line"
                            class="text-indigo-600 dark:text-indigo-400"></iconify-icon>
                        <span>Ubah Kopi Susu (Medan) jadi 25k</span>
                    </button>
                    <button type="button"
                        wire:click="$set('commandText', 'Halo! Tolong infokan tier harga apa saja yang ada di sistem?')"
                        class="bg-neutral-50 hover:bg-neutral-100 dark:bg-neutral-900/40 dark:hover:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 hover:border-neutral-300 dark:hover:border-neutral-700 text-neutral-600 dark:text-neutral-400 hover:text-neutral-800 dark:hover:text-neutral-200 px-3 py-1.5 rounded-xl text-xs font-semibold transition duration-200 flex items-center gap-1.5">
                        <iconify-icon icon="solar:chat-square-call-line"
                            class="text-indigo-600 dark:text-indigo-400"></iconify-icon>
                        <span>Tanyakan Daftar Tier Harga</span>
                    </button>
                    <button type="button" wire:click="$set('commandText', 'Ganti harga menu')"
                        class="bg-neutral-50 hover:bg-neutral-100 dark:bg-neutral-900/40 dark:hover:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 hover:border-neutral-300 dark:hover:border-neutral-700 text-neutral-600 dark:text-neutral-400 hover:text-neutral-800 dark:hover:text-neutral-200 px-3 py-1.5 rounded-xl text-xs font-semibold transition duration-200 flex items-center gap-1.5">
                        <iconify-icon icon="solar:settings-bold"
                            class="text-indigo-600 dark:text-indigo-400"></iconify-icon>
                        <span>Mulai Perubahan Harga</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
