<div x-data="{ showCartMobile: false }"
    class=" md:w-80 lg:w-[380px] shrink-0 order-2 md:order-2 md:sticky md:top-24 z-10 h-fit">

    <style>
        @media (max-width: 639px) {
            .mobile-hide-cart {
                display: none !important;
            }

            .mobile-fullscreen-cart {
                position: fixed !important;
                inset: 0 !important;
                z-index: 9999 !important;
                width: 100% !important;
                height: 100vh !important;
                overflow-y: auto !important;
                padding-bottom: 90px !important;
                margin: 0 !important;
                border-radius: 0 !important;
                max-width: none !important;
            }
        }

        @media (min-width: 640px) {

            .mobile-fullscreen-cart,
            .mobile-hide-cart {
                position: static !important;
                width: 100% !important;
                height: auto !important;
                display: block !important;
                padding-bottom: 0 !important;
            }
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #e2e8f0;
            border-radius: 10px;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #334155;
        }
    </style>

    @if ($status !== 'selesai' && $status !== 'dibatalkan')
        {{-- Mobile Bottom Bar --}}
        <div class="sm:hidden fixed bottom-0 left-0 right-0 p-4 bg-white/80 dark:bg-neutral-900/80 backdrop-blur-xl border-t border-neutral-200 dark:border-neutral-800 z-40"
            x-show="!showCartMobile" x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0">
            <div class="flex justify-between items-center max-w-lg mx-auto gap-4">
                <div class="flex flex-col">
                    <span class="text-[10px] font-black text-neutral-400 uppercase tracking-widest leading-none mb-1">Total
                        Pembayaran</span>
                    <span class="text-lg font-black text-blue-600 dark:text-blue-400">Rp
                        {{ number_format($totalAfterDiscount, 0, ',', '.') }}</span>
                </div>

                <button type="button" @click="showCartMobile = true"
                    class="flex-1 bg-blue-600 text-white font-bold h-12 rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-blue-500/30 transition-all active:scale-95">
                    <iconify-icon icon="mingcute:shopping-cart-2-line" class="text-xl"></iconify-icon>
                    <span class="text-sm">Keranjang</span>
                    @if (count($pesanan) > 0)
                        <span
                            class="bg-white text-blue-600 font-black text-[10px] px-2 py-0.5 rounded-full">{{ count($pesanan) }}</span>
                    @endif
                </button>
            </div>
        </div>
    @endif

    <div id="pesan"
        class="bg-white dark:bg-neutral-800 border border-neutral-100 dark:border-neutral-700 rounded-2xl p-6 h-fit max-h-[calc(100vh-2rem)] lg:max-h-[calc(100vh-8rem)] overflow-y-auto custom-scrollbar transition-all duration-300 relative"
        :class="showCartMobile ? 'mobile-fullscreen-cart' : 'mobile-hide-cart'">

        {{-- Header Cart --}}
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-neutral-50 dark:border-neutral-700">
            <div>
                <h2 class="font-black text-xl text-neutral-900 dark:text-white leading-tight">Detail Pesanan</h2>
                <div class="flex items-center gap-2 mt-1">
                    <iconify-icon icon="mingcute:box-line" class="text-neutral-400"></iconify-icon>
                    <span class="text-xs font-bold text-neutral-400">{{ count($pesanan) }} Item Terpilih</span>
                </div>
            </div>
            <button x-show="showCartMobile" type="button" @click="showCartMobile = false"
                class="w-10 h-10 flex items-center justify-center bg-neutral-100 dark:bg-neutral-700 rounded-full text-neutral-500 dark:text-neutral-300 hover:bg-neutral-200 transition">
                <iconify-icon icon="mingcute:close-line" class="text-2xl"></iconify-icon>
            </button>
        </div>

        {{-- Customer Name --}}
        <div class="mb-6">
            <x-ui.input label="Nama Pelanggan" wire:model="nama_costumer" placeholder="Contoh: Budi Santoso"
                :readonly="$status === 'selesai'" />
        </div>

        {{-- Order Items --}}
        <div class="mb-6">
            <div class="flex items-center gap-2 mb-3">
                <iconify-icon icon="mingcute:list-check-line" class="text-blue-600"></iconify-icon>
                <h3 class="text-xs font-black text-neutral-400 uppercase tracking-widest">Daftar Belanja</h3>
            </div>

            <ul class="space-y-3 max-h-[300px] overflow-y-auto pr-1 custom-scrollbar">
                @forelse ($pesanan as $index => $p)
                    <li
                        class="group flex items-center gap-4 bg-neutral-50/50 dark:bg-neutral-900/50 rounded-3xl p-3 border border-neutral-100/50 dark:border-neutral-700/50 hover:border-blue-200 dark:hover:border-blue-900 transition-colors">
                        <img src="{{ asset('storage/' . $p['gambar']) }}" alt="{{ $p['nama_menu'] }}"
                            class="shrink-0 rounded-2xl w-14 h-14 object-cover border border-white dark:border-neutral-800 shadow-sm">

                        <div class="flex-1 min-w-0">
                            <h4
                                class="text-sm font-bold text-neutral-900 dark:text-white line-clamp-1 leading-tight mb-0.5">
                                {{ $p['nama_menu'] }}
                            </h4>
                            @if (!empty($p['display_options']))
                                <p class="text-[10px] text-neutral-400 font-medium italic mb-1">
                                    {{ implode(', ', $p['display_options']) }}
                                </p>
                            @endif

                            <div class="flex items-center gap-2">
                                @if (isset($itemDiscounts[$index]) && $itemDiscounts[$index] > 0)
                                    @php
                                        $hargaItemAsli = $p['harga'];
                                        $hargaDiscounted = round($hargaItemAsli - $itemDiscounts[$index] / $p['qty']);
                                    @endphp
                                    <span
                                        class="text-[10px] text-neutral-400 line-through">Rp{{ number_format($hargaItemAsli, 0, ',', '.') }}</span>
                                    <span
                                        class="text-sm font-black text-blue-600 dark:text-blue-400">Rp{{ number_format($hargaDiscounted, 0, ',', '.') }}</span>
                                @else
                                    <span
                                        class="text-sm font-black text-blue-600 dark:text-blue-400">Rp{{ number_format($p['harga'], 0, ',', '.') }}</span>
                                @endif
                                <span class="text-[10px] font-black text-neutral-300">x{{ $p['qty'] }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col items-center gap-1">
                            <button wire:click="increment('{{ $index }}')" @if ($status === 'selesai') disabled @endif
                                class="w-8 h-8 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-neutral-600 dark:text-neutral-300 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition shadow-sm active:scale-90">
                                <iconify-icon icon="mingcute:add-line" class="text-sm"></iconify-icon>
                            </button>
                            <button wire:click="decrement('{{ $index }}')" @if ($status === 'selesai') disabled @endif
                                class="w-8 h-8 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-neutral-600 dark:text-neutral-300 hover:bg-red-600 hover:text-white hover:border-red-600 transition shadow-sm active:scale-90">
                                <iconify-icon icon="mingcute:minimize-line" class="text-sm"></iconify-icon>
                            </button>
                        </div>
                    </li>
                @empty
                    <li
                        class="flex flex-col items-center justify-center gap-3 py-12 bg-neutral-50 dark:bg-neutral-900/50 rounded-[2rem] border-2 border-dashed border-neutral-200 dark:border-neutral-700">
                        <div
                            class="w-16 h-16 rounded-full bg-white dark:bg-neutral-800 flex items-center justify-center text-neutral-300 border border-neutral-100 dark:border-neutral-700">
                            <iconify-icon icon="mingcute:shopping-cart-2-line" class="text-3xl"></iconify-icon>
                        </div>
                        <span class="text-xs font-bold text-neutral-400 uppercase tracking-widest">Keranjang
                            Kosong</span>
                    </li>
                @endforelse
            </ul>
        </div>

        {{-- Payment Methods --}}
        <div x-data="{ expanded: false }" class="mb-6">
            <div @click="expanded = !expanded" class="flex justify-between items-center mb-3 cursor-pointer group">
                <div class="flex items-center gap-2">
                    <iconify-icon icon="mingcute:card-pay-line" class="text-blue-600"></iconify-icon>
                    <h3
                        class="text-xs font-black text-neutral-400 uppercase tracking-widest group-hover:text-blue-600 transition-colors">
                        Metode Pembayaran</h3>
                </div>
                <iconify-icon :icon="expanded ? 'mingcute:up-line' : 'mingcute:down-line'"
                    class="text-neutral-400 transition-transform duration-300"></iconify-icon>
            </div>

            <div class="grid grid-cols-4 gap-2">
                @foreach ($pembayaran as $pay)
                            @php
                                $icon = 'mingcute:bank-card-line';
                                if (strtolower($pay) == 'tunai') {
                                    $icon = 'mingcute:wallet-line';
                                }
                                if (strtolower($pay) == 'qris') {
                                    $icon = 'mingcute:qrcode-line';
                                }
                                if (strtolower($pay) == 'transfer') {
                                    $icon = 'mingcute:transfer-line';
                                }
                                if (strtolower($pay) == 'debit') {
                                    $icon = 'mingcute:credit-card-line';
                                }
                            @endphp
                            <button type="button" @if ($loop->index >= 4) x-show="expanded" x-transition @endif
                                wire:click="$set('metode_pembayaran','{{ $pay }}')"
                                @click="if('{{ strtolower($pay) }}' === 'tunai') $dispatch('open-tunai-modal')"
                                class="flex flex-col items-center justify-center p-2 rounded-2xl border-2 transition-all duration-300 group
                                        {{ $metode_pembayaran === $pay
                    ? 'bg-blue-600 border-blue-600 text-white shadow-lg shadow-blue-500/20 active:scale-95'
                    : 'bg-white dark:bg-neutral-800 border-neutral-100 dark:border-neutral-700 text-neutral-500 dark:text-neutral-400 hover:border-blue-300 dark:hover:border-blue-900 group-active:scale-95' }}">
                                <iconify-icon icon="{{ $icon }}" class="text-xl mb-1"></iconify-icon>
                                <span
                                    class="text-[9px] font-black uppercase tracking-tighter text-center leading-none">{{ $pay }}</span>
                            </button>
                @endforeach
            </div>
        </div>

        {{-- Voucher & Member --}}
        <div x-data="{ openModal: null }" class="grid grid-cols-2 gap-3 mb-6">
            <button type="button" @click="openModal = 'voucher'"
                class="flex items-center justify-center gap-2 h-12 rounded-2xl bg-neutral-50 dark:bg-neutral-900 border border-neutral-100 dark:border-neutral-700 text-neutral-600 dark:text-neutral-300 hover:bg-white dark:hover:bg-neutral-800 hover:border-blue-200 transition-all shadow-sm group">
                <iconify-icon icon="mingcute:ticket-line"
                    class="text-lg text-neutral-400 group-hover:text-blue-600"></iconify-icon>
                <span
                    class="text-xs font-bold uppercase tracking-widest group-hover:text-neutral-900 dark:group-hover:text-white">Voucher</span>
            </button>
            <button type="button" @click="openModal = 'member'"
                class="relative flex items-center justify-center gap-2 h-12 rounded-2xl bg-neutral-50 dark:bg-neutral-900 border border-neutral-100 dark:border-neutral-700 text-neutral-600 dark:text-neutral-300 hover:bg-white dark:hover:bg-neutral-800 hover:border-blue-200 transition-all shadow-sm group">
                <iconify-icon icon="mingcute:user-star-line"
                    class="text-lg text-neutral-400 group-hover:text-blue-600"></iconify-icon>
                <span
                    class="text-xs font-bold uppercase tracking-widest group-hover:text-neutral-900 dark:group-hover:text-white">Member</span>

                @if(isset($isMember) && $isMember && isset($memMessage))
                    @php
                        preg_match('/\((.*?)\)/', $memMessage, $matches);
                        $badgeName = $matches[1] ?? 'Verified';
                    @endphp
                    <span
                        class="absolute -bottom-2 bg-green-100 text-green-700 text-[9px] font-black uppercase px-2 py-0.5 rounded-full border border-green-200 shadow-sm whitespace-nowrap overflow-hidden text-ellipsis max-w-[90%]">{{ $badgeName }}</span>
                @endif
            </button>

            {{-- Voucher/Member Selection Popups --}}
            <div x-show="openModal !== null" style="display: none;"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-neutral-900/40 backdrop-blur-sm p-4">
                <div @click.away="openModal = null"
                    class="bg-white dark:bg-neutral-800 rounded-[2.5rem] w-full max-w-sm p-8 shadow-2xl relative border border-neutral-100 dark:border-neutral-700">
                    <button @click="openModal = null"
                        class="absolute top-6 right-6 text-neutral-400 hover:text-neutral-600"><iconify-icon
                            icon="mingcute:close-line" class="text-2xl"></iconify-icon></button>

                    <div x-show="openModal === 'voucher'">
                        <div
                            class="w-16 h-16 rounded-3xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 mb-6">
                            <iconify-icon icon="mingcute:ticket-line" class="text-3xl"></iconify-icon>
                        </div>
                        <h3 class="text-xl font-black text-neutral-900 dark:text-white mb-2">Gunakan Voucher</h3>
                        <p class="text-sm text-neutral-500 mb-6">Masukkan kode promo atau pilih dari daftar.</p>
                        <x-ui.input wire:model.live.debounce.500ms="discount" placeholder="KODEPROMO2024"
                            :readonly="$status === 'selesai'" />

                        {{-- Available Vouchers List --}}
                        @if ($status !== 'selesai' && isset($availableDiscountsList) && $availableDiscountsList->count() > 0)
                            <div class="mt-4 mb-2 flex items-center gap-2">
                                <span class="text-[10px] font-black text-neutral-400 uppercase tracking-widest">Voucher
                                    Tersedia</span>
                                <div class="h-px flex-1 bg-neutral-100 dark:bg-neutral-700"></div>
                            </div>
                            <div class="space-y-2 max-h-[160px] overflow-y-auto pr-1 custom-scrollbar">
                                @foreach ($availableDiscountsList as $v)
                                                    <button type="button" wire:click="$set('discount', '{{ $v->kode_diskon }}')"
                                                        class="w-full flex items-center justify-between p-3 rounded-2xl border transition-all text-left group
                                                                    {{ $discount === $v->kode_diskon
                                    ? 'bg-blue-600 border-blue-600 text-white shadow-md'
                                    : 'bg-neutral-50 dark:bg-neutral-900/50 border-neutral-100 dark:border-neutral-700 hover:border-blue-300 dark:hover:border-blue-900 hover:bg-white dark:hover:bg-neutral-800' }}">
                                                        <div>
                                                            <span
                                                                class="block text-[11px] font-black uppercase tracking-tight {{ $discount === $v->kode_diskon ? 'text-white' : 'text-neutral-900 dark:text-white' }}">{{ $v->nama_diskon }}</span>
                                                            <span
                                                                class="text-[9px] font-bold {{ $discount === $v->kode_diskon ? 'text-blue-100' : 'text-neutral-400' }}">Kode:
                                                                {{ $v->kode_diskon }}</span>
                                                        </div>
                                                        <div
                                                            class="text-xs font-black {{ $discount === $v->kode_diskon ? 'text-white' : 'text-blue-600 dark:text-blue-400' }}">
                                                            @if($v->jenis_diskon === 'persentase')
                                                                {{ (int) $v->nilai_diskon }}%
                                                            @else
                                                                Rp{{ number_format($v->nilai_diskon, 0, ',', '.') }}
                                                            @endif
                                                        </div>
                                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-4 mb-6">
                            @if ($discMessage && $status !== 'selesai')
                                <div
                                    class="flex items-center gap-2 {{ $isDiscountVerified || (isset($disc) && $disc['type'] === 'general') ? 'text-green-600' : 'text-amber-600' }}">
                                    <iconify-icon icon="mingcute:information-line" class="text-lg"></iconify-icon>
                                    <span class="text-[11px] font-bold">{{ $discMessage }}</span>
                                </div>
                            @endif
                        </div>

                        @if (isset($disc) && $disc['type'] === 'private' && !$isDiscountVerified && !isset($isMember) || (isset($disc) && $disc['type'] === 'private' && !$isDiscountVerified && isset($isMember) && !$isMember))
                            <x-ui.button
                                wire:click="$dispatch('open-modal', { name: 'verify-discount-modal' }); openModal = null"
                                class="w-full !py-4">Otorisasi Diskon</x-ui.button>
                        @else
                            <x-ui.button @click="openModal = null" class="w-full !py-4">Terapkan</x-ui.button>
                        @endif
                    </div>

                    <div x-show="openModal === 'member'">
                        <div
                            class="w-16 h-16 rounded-3xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 mb-6">
                            <iconify-icon icon="mingcute:user-star-line" class="text-3xl"></iconify-icon>
                        </div>
                        <h3 class="text-xl font-black text-neutral-900 dark:text-white mb-2">Identifikasi Member</h3>
                        <p class="text-sm text-neutral-500 mb-6">Gunakan nomor HP untuk menghubungkan poin member.</p>
                        <x-ui.input wire:model.live.debounce.500ms="member" placeholder="0812xxxx" :readonly="$status === 'selesai'" />

                        <div class="mt-4 mb-6">
                            @if (isset($memMessage) && $memMessage)
                                <div
                                    class="flex items-center gap-2 {{ str_contains($memMessage, 'Tersedia') ? 'text-green-600' : 'text-amber-600' }}">
                                    <iconify-icon icon="mingcute:information-line" class="text-lg"></iconify-icon>
                                    <span class="text-[11px] font-bold">{{ $memMessage }}</span>
                                </div>

                                {{-- Menampilkan Poin dan Favorit Member --}}
                                @if(isset($isMember) && $isMember)
                                    <div class="mt-4 p-4 bg-green-50 rounded-2xl border border-green-100">
                                        <div class="flex justify-between items-center mb-3 pb-3 border-b border-green-200">
                                            <span class="text-xs font-bold text-green-800">Total Poin:</span>
                                            <span class="text-lg font-black text-green-600">{{ $memberPoints ?? 0 }} pts</span>
                                        </div>
                                        @if(isset($memberFavorites) && $memberFavorites->count() > 0)
                                            <p class="text-[11px] font-bold text-green-700 mb-2 uppercase tracking-wide">Menu
                                                Favorit:</p>
                                            <ul class="space-y-1">
                                                @foreach($memberFavorites as $fav)
                                                    <li
                                                        class="text-xs text-green-900 flex justify-between items-center bg-white rounded-lg px-2 py-1 shadow-sm">
                                                        <span>{{ $fav->menu->nama_menu ?? 'Unknown' }}</span>
                                                        <span class="font-bold text-green-600 px-1">{{ $fav->total_qty }}x</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="text-xs text-green-600 italic">Belum ada riwayat pesanan.</p>
                                        @endif
                                    </div>
                                @endif
                            @endif
                        </div>
                        <x-ui.button @click="openModal = null" color="purple" class="w-full !py-4">Verifikasi
                            Member</x-ui.button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Voucher Tag --}}
        @if (
                $status !== 'selesai' &&
                $discount &&
                ($disc === null || $disc['type'] === 'general' || ($disc['type'] === 'private' && ($isDiscountVerified || (isset($isMember) && $isMember))))
            )
            <div
                class="flex items-center justify-between p-3 mb-6 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 rounded-2xl animate-pulse-slow">
                <div class="flex items-center gap-2">
                    <iconify-icon icon="mingcute:ticket-fill" class="text-green-600"></iconify-icon>
                    <span class="text-xs font-black text-green-700 dark:text-green-400">Voucher Aktif:
                        {{ $discount }}</span>
                </div>
                <button wire:click="removeDiscount" class="text-red-500 hover:scale-125 transition-transform"><iconify-icon
                        icon="mingcute:close-circle-line" class="text-lg"></iconify-icon></button>
            </div>
        @endif

        @include('livewire.order.create-order.modal-verfikasi')

        {{-- Cash Modal Logic --}}
        @if ($this->isCash)
            <div x-data="{
                    open: true,
                    uang: '',
                    minTunai: {{ $totalAfterDiscount }},
                    sync() {
                        let clean = this.uang.replace(/\D/g, '');
                        clean = clean.replace(/^0+/, '');
                        this.uang = clean;
                        $wire.set('uang_tunai', clean === '' ? null : parseInt(clean));
                    },
                    get isValid() { return this.uang && parseInt(this.uang) >= this.minTunai; }
                }" x-init="if ($wire.uang_tunai) {
                    uang = String($wire.uang_tunai);
                    open = false;
                }" @open-tunai-modal.window="open = true">

                <div x-show="open" style="display: none;"
                    class="fixed inset-0 z-[100] flex items-center justify-center bg-neutral-900/40 backdrop-blur-sm p-4">
                    <div @click.away="open = false"
                        class="bg-white dark:bg-neutral-800 rounded-[2.5rem] w-full max-w-sm p-8 shadow-2xl relative border border-neutral-100 dark:border-neutral-700">
                        <div
                            class="w-16 h-16 rounded-3xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 mb-6">
                            <iconify-icon icon="mingcute:wallet-line" class="text-3xl"></iconify-icon>
                        </div>
                        <h3 class="text-xl font-black text-neutral-900 dark:text-white mb-2">Pembayaran Tunai</h3>
                        <p class="text-sm text-neutral-500 mb-6">Masukkan jumlah uang yang diterima dari pelanggan.</p>

                        <x-ui.input label="Jumlah Uang" x-model="uang" x-on:input="sync" inputmode="numeric" type="text"
                            placeholder="50.000" />

                        <div class="mt-4 min-h-[20px] mb-6">
                            <template x-if="!isValid">
                                <span class="text-[11px] font-bold text-red-500 italic block">
                                    *Masih kurang Rp {{ number_format($totalAfterDiscount, 0, ',', '.') }}
                                </span>
                            </template>
                        </div>

                        <button type="button" @click="if(isValid) open = false" :disabled="!isValid"
                            class="w-full py-4 text-sm font-bold rounded-2xl bg-blue-600 text-white transition-all shadow-lg shadow-blue-500/30"
                            :class="!isValid ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700 active:scale-95'">
                            Simpan & Lanjutkan
                        </button>
                    </div>
                </div>

                <div class="p-4 mb-6 bg-blue-50/30 dark:bg-blue-900/10 rounded-2xl border border-blue-100 dark:border-blue-900/50 flex justify-between items-center cursor-pointer hover:bg-blue-50 transition-all group"
                    @click="open = true">
                    <span class="text-xs font-black text-neutral-400 uppercase tracking-widest">Tunai Diterima:</span>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-black text-blue-700 dark:text-blue-400"
                            x-text="uang ? 'Rp' + new Intl.NumberFormat('id-ID').format(uang) : 'Rp0'"></span>
                        <iconify-icon icon="mingcute:edit-line"
                            class="text-xl text-neutral-300 group-hover:text-blue-600 transition-colors"></iconify-icon>
                    </div>
                </div>
            </div>
        @endif

        {{-- Final Summary --}}
        <div class="mt-6 pt-6 border-t-2 border-dashed border-neutral-100 dark:border-neutral-700 space-y-4">
            <div class="flex justify-between items-center text-sm">
                <span class="font-bold text-neutral-400 uppercase tracking-widest text-[10px]">Subtotal</span>
                <span class="font-bold text-neutral-800 dark:text-neutral-200">Rp
                    {{ number_format($total, 0, ',', '.') }}</span>
            </div>

            <div class="flex justify-between items-center text-sm">
                <span class="font-bold text-neutral-400 uppercase tracking-widest text-[10px]">Voucher/Diskon</span>
                <span class="font-bold text-green-600">-Rp {{ number_format($discountValue, 0, ',', '.') }}</span>
            </div>

            <div class="flex justify-between items-center text-sm">
                <span class="font-bold text-neutral-400 uppercase tracking-widest text-[10px]">Pajak (Tax)</span>
                <span class="font-bold text-neutral-800 dark:text-neutral-200">Rp
                    {{ number_format(isset($taxValue) ? $taxValue : 0, 0, ',', '.') }}</span>
            </div>

            <div class="flex justify-between items-center pt-4 border-t border-neutral-50 dark:border-neutral-700">
                <span class="font-black text-neutral-700 dark:text-neutral-300">TOTAL AKHIR</span>
                <span class="text-2xl font-black text-blue-700 dark:text-blue-400">Rp
                    {{ number_format($totalAfterDiscount, 0, ',', '.') }}</span>
            </div>

            @if ($this->isCash)
                <div
                    class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-2xl border border-green-100 dark:border-green-800">
                    <span
                        class="text-xs font-black text-green-700 dark:text-green-400 uppercase tracking-widest">Kembalian</span>
                    <span class="text-lg font-black text-green-800 dark:text-green-300">Rp
                        {{ number_format($kembalian, 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="pt-4 space-y-3">
                @if ($status !== 'dibatalkan' && $status !== 'selesai')
                    <button type="button" wire:click="{{ $submit }}"
                        class="w-full px-8 py-4 text-lg bg-blue-600 text-white rounded-2xl shadow-lg shadow-blue-500/30 font-bold transition-all active:scale-95"
                        :disabled="$wire.metode_pembayaran === 'tunai' && (!$wire.uang_tunai || $wire.uang_tunai <
                                {{ $totalAfterDiscount }})" :class="($wire.metode_pembayaran === 'tunai' && (!$wire.uang_tunai || $wire.uang_tunai <
                                {{ $totalAfterDiscount }})) ? 'opacity-50 grayscale cursor-not-allowed' :
                            'hover:bg-blue-700 animate-pulse-slow'">
                        {{ $teks }} Pesanan
                    </button>
                @endif

                @if ($status !== 'dibatalkan')
                    <button @click="$dispatch('open-modal', { name: 'confirm-cancel-modal' })" type="button"
                        class="w-full py-4 rounded-2xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-neutral-400 font-bold uppercase tracking-widest text-[10px] hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all flex items-center justify-center gap-2">
                        <iconify-icon icon="mingcute:delete-2-line" class="text-lg"></iconify-icon>
                        Batal Pesanan
                    </button>
                    @include('livewire.order.create-order.modal-batalkan')
                @endif

                @if ($teks == 'Update' && $status !== 'dibatalkan')
                    <a href="{{ route('struk.print', base64_encode($orderId)) }}"
                        class="block w-full py-4 text-center bg-neutral-100 dark:bg-neutral-700 text-neutral-600 dark:text-neutral-300 font-bold rounded-2xl hover:bg-neutral-200 transition-colors">
                        Cetak Struk
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>