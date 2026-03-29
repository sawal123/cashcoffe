<div x-data="{ showCartMobile: false }" class="w-full sm:w-[300px] shrink-0 order-2 lg:order-2 lg:sticky lg:top-14 h-fit">
    
    <style>
        @media (max-width: 639px) {
            .mobile-hide-cart { display: none !important; }
            .mobile-fullscreen-cart {
                position: fixed !important; inset: 0 !important; z-index: 9999 !important;
                width: 100% !important; height: 100vh !important; overflow-y: auto !important;
                padding-bottom: 90px !important; margin: 0 !important; border-radius: 0 !important;
                max-width: none !important;
            }
        }
    </style>

    <!-- Mobile Bottom Navigation Bar -->
    @if($status !== 'selesai' && $status !== 'dibatalkan')
    <div class="sm:hidden fixed bottom-0 left-0 right-0 p-3.5 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 shadow-[0_-8px_15px_-3px_rgba(0,0,0,0.1)] z-40 transition-transform duration-300" 
         x-show="!showCartMobile"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full">
        <div class="flex justify-between items-center max-w-lg mx-auto gap-3">
            <div class="flex flex-col">
                <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Total Pembayaran</span>
                <span class="text-[15px] font-bold text-blue-700 dark:text-blue-400 leading-tight">Rp {{ number_format($totalAfterDiscount, 0, ',', '.') }}</span>
            </div>
            
            <button type="button" @click="showCartMobile = true" class="flex-1 max-w-[180px] bg-orange-500 hover:bg-orange-600 text-white font-bold h-11 rounded-xl flex items-center justify-center gap-1.5 transition duration-300 shadow-md">
                <iconify-icon icon="solar:cart-large-minimalistic-bold" class="text-xl"></iconify-icon>
                <span class="text-sm">Lihat Pesanan</span>
                @if(count($pesanan) > 0)
                <span class="bg-white text-orange-600 font-bold text-[10px] px-1.5 py-0.5 rounded-full ml-1">{{ count($pesanan) }}</span>
                @endif
            </button>
        </div>
    </div>
    @endif

    <div id="pesan"
        class="border border-slate-200 mt-2 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800 shadow-sm p-3 h-fit transition-all duration-300"
        :class="showCartMobile ? 'mobile-fullscreen-cart animate-fade-in-up' : 'mobile-hide-cart'"
    >

        <!-- Mobile Header inside Modal -->
        <div class="sm:hidden flex items-center justify-between mb-4 pb-3 border-b border-slate-200 dark:border-slate-700" x-show="showCartMobile">
            <div>
                <h2 class="font-bold text-lg text-slate-900 dark:text-white leading-tight">Ringkasan Pesanan</h2>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ count($pesanan) }} Item Dipilih</span>
            </div>
            <button type="button" @click="showCartMobile = false; setTimeout(() => { showCartMobile = false }, 100)" class="w-8 h-8 flex items-center justify-center bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-600 shadow-sm rounded-full text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition relative z-10">
                <iconify-icon icon="lucide:x" class="text-xl"></iconify-icon>
            </button>
        </div>

        <!-- Desktop Header -->
        <div class="hidden sm:flex items-center justify-between mb-4">
            <p class="font-bold text-lg text-slate-900 dark:text-white">Ringkasan Pesanan</p>
            <span class="inline-flex items-center rounded-md bg-orange-500 text-white px-2.5 py-1 text-xs font-semibold">
                {{ count($pesanan) }} Item
            </span>
        </div>

    <div class="mb-4">
        <x-inputsmall wire:model="nama_costumer" placeholder="Nama costumer" :readonly="$status === 'selesai'"  />
    </div>

    <p class="font-bold text-sm mb-2 text-slate-900 dark:text-white">Item Dipilih</p>


    <ul class="space-y-2 mb-4 max-h-[220px] overflow-y-auto pr-1 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent">
        @forelse ($pesanan as $index=>$p)
            <li class="flex items-center justify-between gap-3 bg-white dark:bg-slate-800 rounded-xl p-2.5 border border-slate-200/60 dark:border-slate-700">
                <img src="{{ asset('storage/' . $p['gambar']) }}" alt="Produk"
                    class="shrink-0 rounded-lg w-12 h-12 object-cover bg-slate-100">

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-900 dark:text-slate-200 truncate leading-tight mb-0.5">
                        {{ $p['nama_menu'] }}
                    </p>
                    <span class="text-[13px] font-bold text-blue-600 dark:text-blue-400">
                        Rp {{ number_format($p['harga'], 0, ',', '.') }}
                    </span>
                </div>

                <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-slate-700 rounded-lg p-1 border border-slate-100 dark:border-slate-600">
                    <button wire:click="decrement({{ $p['id'] }})"
                        @if ($status === 'selesai') disabled @endif
                        class="w-6 h-6 flex items-center justify-center rounded bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-200 transition hover:bg-slate-300 {{ $status === 'selesai' ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <iconify-icon icon="lucide:minus" class="text-xs"></iconify-icon>
                    </button>

                    <span class="w-5 text-center text-xs font-bold text-slate-800 dark:text-white">
                        {{ $p['qty'] }}
                    </span>

                    <button wire:click="increment({{ $p['id'] }})"
                        @if ($status === 'selesai') disabled @endif
                        class="w-6 h-6 flex items-center justify-center rounded bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-200 transition hover:bg-slate-300 {{ $status === 'selesai' ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <iconify-icon icon="lucide:plus" class="text-xs"></iconify-icon>
                    </button>
                </div>
            </li>
        @empty
            <li class="flex flex-col items-center justify-center gap-2 text-center py-10 bg-slate-50 dark:bg-slate-800 rounded-xl">
                <iconify-icon icon="solar:cart-large-minimalistic-linear" class="text-4xl text-slate-300"></iconify-icon>
                <span class="text-sm font-semibold text-slate-400">Keranjang masih kosong</span>
            </li>
        @endforelse
    </ul>

    <div x-data="{ expanded: false }" class="bg-[#F4F7FB] dark:bg-slate-800 rounded-xl p-3 mb-4">
        <div @click="expanded = !expanded" class="flex justify-between items-center mb-3 cursor-pointer select-none">
            <span class="text-xs text-slate-500 font-medium">Metode pembayaran:</span>
            <iconify-icon :icon="expanded ? 'lucide:chevron-up' : 'lucide:chevron-down'" class="text-slate-400 text-sm transition-transform duration-200"></iconify-icon>
        </div>

        <div class="grid grid-cols-4 gap-1.5">
            @foreach ($pembayaran as $pay)
                @php
                    $icon = 'solar:wallet-money-linear';
                    if(strtolower($pay) == 'qris') $icon = 'solar:qr-code-linear';
                    if(strtolower($pay) == 'debit' || strtolower($pay) == 'kartu') $icon = 'solar:card-linear';
                    if(strtolower($pay) == 'transfer') $icon = 'solar:card-transfer-linear';
                @endphp
                <button type="button" 
                    @if($loop->index >= 4) x-show="expanded" x-transition @endif
                    wire:click="$set('metode_pembayaran','{{ $pay }}')"
                    @click="if('{{ strtolower($pay) }}' === 'tunai') $dispatch('open-tunai-modal')"
                    class="flex flex-col items-center justify-center gap-1 p-1.5 sm:p-2 rounded-lg border text-[10px] sm:text-xs font-medium transition duration-200 overflow-hidden
                    {{ $metode_pembayaran === $pay ? 'bg-white border-blue-200 text-blue-600 shadow-sm dark:bg-slate-100 dark:text-blue-700' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
                    <iconify-icon icon="{{ $icon }}" class="text-lg sm:text-xl {{ $metode_pembayaran === $pay ? 'text-blue-600 dark:text-blue-700' : 'text-slate-400 dark:text-slate-400' }}"></iconify-icon>
                    <span class="truncate w-full text-center">{{ ucfirst($pay) }}</span>
                </button>
            @endforeach
        </div>

        <select id="metode_pembayaran" wire:model.live="metode_pembayaran" class="sr-only" aria-hidden="true">
            <option value="">Pilih Pembayaran…</option>
            @foreach ($pembayaran as $pay)
                <option value="{{ $pay }}">{{ $pay }}</option>
            @endforeach
        </select>
    </div>


    {{-- @if ($status != null)
        <div class="flex justify-between gap-4 items-center mt-2">
            <span class="text-sm dark:text-slate-200 text-slate-900">Status: </span>
            <select id="status" wire:model="status"
                class="form-select w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
                required>
                <option value="selesai" class="text-neutral-800 dark:text-neutral-200">
                    Selesai
                </option>
                <option value="dibatalkan" class="text-neutral-800 dark:text-neutral-200">
                    Dibatalkan
                </option>

            </select>
        </div>
    @endif --}}

    <div x-data="{ openModal: null }" class="space-y-3 mb-4">
        <div class="grid grid-cols-2 gap-2">
            <button type="button" @click="openModal = 'voucher'"
                class="flex items-center justify-center gap-2 h-10 rounded-lg border border-slate-200 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-200 text-[13px] font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition duration-200">
                <iconify-icon icon="solar:ticket-sale-linear" class="text-lg"></iconify-icon>
                Voucher
            </button>
            <button type="button" @click="openModal = 'member'"
                class="flex items-center justify-center gap-2 h-10 rounded-lg border border-slate-200 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-200 text-[13px] font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition duration-200">
                <iconify-icon icon="solar:users-group-two-rounded-linear" class="text-lg"></iconify-icon>
                Member
            </button>
        </div>

        <!-- Alpine Modal for Voucher/Member -->
        <div x-show="openModal !== null" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div @click.away="openModal = null" class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-sm p-5 shadow-2xl">
                <div x-show="openModal === 'voucher'">
                    <x-inputsmall wire:model.live.debounce.500ms="discount" placeholder="Masukan code voucher"
                        :readonly="$status === 'selesai'" />
                    @if ($discMessage && $status !== 'selesai' && !$isDiscountVerified && (!isset($disc) || $disc['type'] !== 'general'))
                        <p class="text-[11px] text-slate-400 italic mb-2 mt-1">*{{ $discMessage }}</p>
                    @elseif($discMessage && $status !== 'selesai')
                        <p class="text-[11px] text-green-500 italic mb-2 mt-1">*{{ $discMessage }}</p>
                    @else
                        <p class="text-[11px] text-slate-400 italic mb-2 mt-1">*Masukan kode voucher yang valid</p>
                    @endif
                    
                    @if (isset($disc) && $disc['type'] === 'private' && !$isDiscountVerified)
                        <button type="button" @click="$dispatch('open-modal', { name: 'verify-discount-modal' }); openModal = null"
                            class="w-full bg-orange-500 hover:bg-orange-700 text-white font-bold h-11 rounded-xl transition duration-300 mt-2 text-sm">
                            Verifikasi Diskon
                        </button>
                    @else
                        <button type="button" @click="openModal = null"
                            class="w-full bg-orange-500 hover:bg-orange-700 text-white font-bold h-11 rounded-xl transition duration-300 mt-2 text-sm">
                            Submit
                        </button>
                    @endif
                </div>

                <div x-show="openModal === 'member'">
                    <x-inputsmall wire:model.live="member" placeholder="Masukan no Hp costumer"
                        :readonly="$status === 'selesai'" />
                    @if ($memMessage)
                        <p class="text-[11px] text-slate-400 italic mb-2 mt-1">*{{ $memMessage }}</p>
                    @else
                        <p class="text-[11px] text-slate-400 italic mb-2 mt-1">*Belum terdaftar sebagai member</p>
                    @endif
                    <button type="button" @click="openModal = null"
                        class="w-full bg-orange-500 hover:bg-orange-700 text-white font-bold h-11 rounded-xl transition duration-300 mt-2 text-sm">
                        Submit
                    </button>
                </div>
            </div>
        </div>

        <div class="flex gap-2 items-center">
            @if ($status !== 'selesai')
                @if ($discount && ($disc === null || $disc['type'] === 'general' || ($disc['type'] === 'private' && $isDiscountVerified)))
                    <div class="flex-1 flex items-center justify-between px-3 h-10 bg-green-50 rounded-lg border border-green-200">
                        <span class="text-xs font-semibold text-green-700">Voucher: {{ $discount }}</span>
                        <button wire:click="removeDiscount" type="button" class="text-red-500 hover:text-red-700 p-1">
                            <iconify-icon icon="lucide:x" class="text-sm"></iconify-icon>
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </div>

    @include('livewire.order.create-order.modal-verfikasi')

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
            get isValid() {
                return this.uang && parseInt(this.uang) >= this.minTunai;
            }
        }" x-init="if ($wire.uang_tunai) { uang = String($wire.uang_tunai); open = false; }"
        @open-tunai-modal.window="open = true">
            
            <div x-show="open" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
                <div @click.away="open = false" class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-sm p-5 shadow-2xl relative">
                    <button type="button" @click="open = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                        <iconify-icon icon="lucide:x" class="text-xl"></iconify-icon>
                    </button>
                    
                    <h3 class="font-bold text-lg mb-4 text-slate-900 dark:text-white">Nominal Uang Tunai</h3>

                    <x-inputsmall label="Jumlah Uang" x-model="uang" x-on:input="sync" inputmode="numeric" type="text" placeholder="Contoh: 50000" />

                    <div class="mt-1 min-h-[20px] mb-2">
                        <span x-show="!isValid"
                            class="text-[11px] text-red-500 italic block">
                            *Uang tunai masih kurang (Min. Rp {{ number_format($totalAfterDiscount, 0, ',', '.') }})
                        </span>
                    </div>

                    <button type="button" 
                        @click="if(isValid) open = false"
                        :class="!isValid ? 'opacity-50 cursor-not-allowed bg-slate-300 dark:bg-slate-700 text-slate-500' : 'bg-orange-500 hover:bg-orange-700 text-white'"
                        :disabled="!isValid"
                        class="w-full font-bold h-11 rounded-xl transition duration-300 text-sm">
                        Simpan Nominal
                    </button>
                </div>
            </div>
            
            <!-- Indikator Uang Tunai yang dimasukkan -->
            <div class="p-3.5 mt-2 bg-blue-50/50 dark:bg-slate-800/80 rounded-xl border border-blue-100 dark:border-slate-700 flex justify-between items-center cursor-pointer hover:bg-blue-50 dark:hover:bg-slate-700 transition" @click="open = true">
                <span class="text-sm font-semibold text-slate-600 dark:text-slate-400">Uang Tunai:</span>
                <div class="flex items-center gap-1.5">
                    <span class="text-[15px] font-bold text-blue-700 dark:text-blue-400" x-text="uang ? 'Rp ' + new Intl.NumberFormat('id-ID').format(uang) : 'Rp 0'"></span>
                    <iconify-icon icon="solar:pen-new-square-linear" class="text-lg text-blue-500 dark:text-blue-400"></iconify-icon>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-4 pt-4 border-t border-dashed border-slate-300 dark:border-slate-700 space-y-2.5">
        {{-- SUBTOTAL --}}
        <div class="flex justify-between items-center">
            <span class="text-sm text-slate-500 dark:text-slate-400">
                Subtotal
            </span>
            <span class="text-sm font-bold text-blue-700 dark:text-blue-400">
                Rp {{ number_format($total, 0, ',', '.') }}
            </span>
        </div>

        {{-- DISCOUNT --}}
        <div class="flex justify-between items-center">
            <span class="text-sm text-slate-500 dark:text-slate-400">
                Diskon
            </span>
            <span class="text-sm font-bold text-blue-700 dark:text-blue-400">
                Rp {{ number_format($discountValue, 0, ',', '.') }}
            </span>
        </div>

        {{-- TAX --}}
        <div class="flex justify-between items-center">
            <span class="text-sm text-slate-500 dark:text-slate-400">
                Tax
            </span>
            <span class="text-sm font-bold text-blue-700 dark:text-blue-400">
                Rp {{ number_format(isset($taxValue) ? $taxValue : 0, 0, ',', '.') }}
            </span>
        </div>

        <hr class="border-t border-dashed border-slate-300 dark:border-slate-700 my-3">

        {{-- TOTAL --}}
        <div class="flex justify-between items-center">
            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">
                Total pembayaran
            </span>
            <span class="text-xl font-bold text-blue-700 dark:text-blue-500">
                Rp {{ number_format($totalAfterDiscount, 0, ',', '.') }}
            </span>
        </div>

        {{-- KEMBALIAN (HANYA CASH) --}}
        @if ($this->isCash)
            <div class="flex justify-between items-center pt-2">
                <span class="text-sm font-bold text-slate-600 dark:text-slate-300">
                    Kembalian
                </span>
                <span
                    class="text-lg font-bold {{ $kembalian > 0 ? 'text-green-600' : 'text-slate-900 dark:text-white' }}">
                    Rp {{ number_format($kembalian, 0, ',', '.') }}
                </span>
            </div>
        @endif

        @if ($status !== 'dibatalkan')
            @if ($status !== 'selesai')
                <button wire:click="{{ $submit }}" x-data
                    :disabled="$wire.metode_pembayaran === 'tunai' &&
                        (!$wire.uang_tunai || $wire.uang_tunai < {{ $totalAfterDiscount }})"
                    :class="($wire.metode_pembayaran === 'tunai' &&
                        (!$wire.uang_tunai || $wire.uang_tunai < {{ $totalAfterDiscount }})) ?
                    'opacity-50 cursor-not-allowed bg-gray-400' :
                    'bg-[#FF5A1F] hover:bg-[#E04D18]'"
                    class="w-full mt-4 text-white font-bold py-3 px-4 rounded-xl transition duration-300 text-sm">
                    {{ $teks }} Pesanan
                </button>
            @endif

            @if ($status !== 'dibatalkan')
                <button @click="$dispatch('open-modal', { name: 'confirm-cancel-modal' })" type="button"
                    class="w-full mt-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-400 font-semibold py-3 px-4 rounded-xl transition duration-300 flex items-center justify-center gap-2 text-sm shadow-sm dark:bg-slate-800 dark:border-slate-700">
                    <iconify-icon icon="solar:trash-bin-trash-linear" class="text-lg"></iconify-icon>
                    Batalkan
                </button>
                @include('livewire.order.create-order.modal-batalkan')
            @endif

            @if ($teks == 'Update')
                <a href="{{ route('struk.print', base64_encode($orderId)) }}"
                    class="w-full bg-slate-600 text-center mt-2 hover:bg-slate-700 text-white font-semibold py-2 px-4 rounded-xl transition duration-300">
                    Cetak Struk
                </a>
            @endif
        @endif

    </div>

    </div> <!-- End inner container (#pesan) -->
</div> <!-- End outer Alpine wrapper -->
