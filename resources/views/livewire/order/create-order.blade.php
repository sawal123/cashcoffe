<div class="sm:flex  justify-between lg:flex-row gap-4">
    <x-toast />
    {{-- Kiri: Produk --}}

    <div class="flex-1 min-w-0 order-1 lg:order-1" id="menu">
        <div class="sm:w-[300px] w-ful mb-2">
            <div class="flex gap-2">
                <x-droppage perPage="{{ $perPage }}" />
                <div class="sm:w-[300px] w-ful">
                    <x-input wire:model.live="search" place="Cari..." />
                </div>
            </div>
        </div>
        <div class="grid w-full grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($menus as $item)
                @php
                    // echo $item;
                    $harga = $item->h_promo == '0' ? $item->harga : $item->h_promo;
                @endphp
                <article wire:click="addPesanan({{ $item->id }})"
                    class="hover:shadow-xl   cursor-pointer group flex flex-col rounded-xl overflow-hidden border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="h-40 md:h-52  overflow-hidden">
                        <img src="{{ asset('storage/' . $item->gambar) }}"
                            class="object-cover w-full h-full transition duration-500 ease-out group-hover:scale-105"
                            alt="Produk" />
                    </div>
                    <div class="p-3">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                            {{ $item->nama_menu }}
                        </p>
                        <p class="text-base font-bold text-slate-900 dark:text-white">
                            Rp{{ number_format($harga, 0, ',', '.') }}
                        </p>
                    </div>
                </article>
            @endforeach
        </div>
        {{ $menus->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    {{-- Kanan: Pesanan --}}
    <div id="pesan"
        class="sm:w-[300px] w-ful shrink-0 border border-slate-200 mt-2 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800 shadow-sm p-2 h-fit order-2 lg:order-2">
        <div class="flex items-center justify-between mb-3">
            <p class="font-bold text-lg mb-2 text-slate-800 dark:text-white">Pesanan Item</p>
            @if ($orderId)
                <span
                    class="inline-flex items-center rounded-md {{ $status == 'selesai' ? 'bg-gray-500' : 'bg-red-500 ' }} px-2 py-1 text-xs font-medium text-gray-600 inset-ring inset-ring-gray-500/10">Pesanan
                    {{ ucfirst(strtolower($status)) }}
                </span>
            @endif
        </div>
        <hr class="border-slate-200 dark:border-slate-700 mb-3">
        <div class="flex justify-between mt-2 gap-4 items-center">
            <div class="md:col-span-6 col-span-12">
                <div class=" w-ful">

                    <x-input wire:model="nama_costumer" place="Nama Costumer" />

                </div>
            </div>
        </div>
        <ul class="space-y-3 my-3">
            @forelse ($pesanan as $index=>$p)
                <li class="flex items-center justify-between gap-2">
                    <img src="{{ asset('storage/' . $p['gambar']) }}" alt="Produk"
                        class="shrink-0 rounded-md w-12 h-12 object-cover">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $p['nama_menu'] }}</p>
                        <span
                            class="text-sm font-bold text-slate-900 dark:text-white">Rp{{ number_format($p['harga'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button wire:click="decrement({{ $p['id'] }})"
                            class="w-7 h-7 flex items-center transition duration-300 hover:bg-gray-600 justify-center rounded-md border">
                            -
                        </button>
                        <span class="w-6 text-center text-sm font-semibold text-slate-800 dark:text-white">
                            {{ $p['qty'] }}
                        </span>
                        <button wire:click="increment({{ $p['id'] }})"
                            class="w-7 h-7 flex items-center transition duration-300 hover:bg-gray-600 justify-center rounded-md border">
                            +
                        </button>
                    </div>
                </li>
            @empty
                <li class="text-sm text-slate-500">Belum ada pesanan</li>
            @endforelse
        </ul>
        <hr class="border-slate-200 dark:border-slate-700 mb-3">
        <div class="flex justify-between gap-4 items-center">
            <span class="text-sm dark:text-slate-200 text-slate-900">Pembayaran: </span>
            <select id="metode_pembayaran" wire:model="metode_pembayaran"
                class="form-select w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
                required>
                <option value="" class="text-neutral-500">..Pembayaran..</option>
                @foreach ($pembayaran as $pay)
                    <option value="{{ $pay }}" class="text-neutral-800 dark:text-neutral-200">
                        {{ $pay }}
                    </option>
                @endforeach
            </select>
        </div>

        @if ($status != null)
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
        @endif
        <div class="flex justify-between mt-2 gap-4 items-center">
            <div class="md:col-span-6 col-span-12">
                <label class="text-sm dark:text-slate-200 text-slate-900">Voucher : </label>
                <div class=" w-ful">

                    <x-input wire:model.live="discount" place="Masukan Code Voucher" />
                    @if ($discMessage)
                        <p class="text-xs text-slate-500 italic mt-1">{{ $discMessage }}</p>
                    @endif
                </div>
            </div>
        </div>
        {{-- @endif --}}

        <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
            <div class="flex justify-between mb-1">
                <span class="text-sm font-medium text-slate-700">Subtotal</span>
                <span class="text-sm font-bold text-slate-900">
                    Rp {{ number_format($total, 0, ',', '.') }}
                </span>
            </div>

            <div class="flex justify-between mb-1">
                <span class="text-sm font-medium text-slate-700">Discount</span>
                <span class="text-sm font-bold text-green-700">
                    - Rp {{ number_format($discountValue, 0, ',', '.') }}
                </span>
            </div>

            <hr class="my-1">

            <div class="flex justify-between mb-1">
                <span class="text-sm font-medium text-slate-700">Total</span>
                <span class="text-lg font-bold text-slate-900">
                    Rp {{ number_format($totalAfterDiscount, 0, ',', '.') }}
                </span>
            </div>



            @if ($status == 'dibatalkan')
                <button disabled
                    class="w-full bg-gray-600 mt-2 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">
                    {{ $teks }} Pesanan
                </button>
            @else
                <button wire:click="{{ $submit }}"
                    class="w-full bg-blue-600 mt-2 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">
                    {{ $teks }} Pesanan
                </button>
                @if ($teks == 'Update')
                    <a href="{{ route('struk.print', base64_encode($orderId)) }}"
                        class="w-full bg-slate-600 text-center mt-2 hover:bg-slate-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">
                        Cetak Struk
                    </a>
                @endif
            @endif

        </div>

        <button id="scrollButton"
            class=" lg:hidden fixed bottom-6 right-6 w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-lg transition-all duration-300 hover:bg-blue-700 z-50">
            <iconify-icon id="scrollIcon" icon="solar:round-arrow-down-broken" class="text-2xl"></iconify-icon>
        </button>
        <style>
            /* Tombol selalu muncul di mobile */
            #scrollButton {
                display: flex;
            }

            /* Hilang otomatis di desktop (layar >= 1024px) */
            @media (min-width: 700px) {
                #scrollButton {
                    display: none !important;
                }
            }
        </style>
        <script>
            document.addEventListener("livewire:navigated", function() {
                const scrollButton = document.getElementById('scrollButton');
                const scrollIcon = document.getElementById('scrollIcon');
                const menuSection = document.getElementById('menu');
                const pesanSection = document.getElementById('pesan');
                const navbarOffset = 80; // 🔧 ubah sesuai tinggi navbar kamu

                // Fungsi scroll dengan offset
                function scrollToWithOffset(element) {
                    const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
                    const offsetPosition = elementPosition - navbarOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }

                // Fungsi deteksi posisi scroll
                function checkScrollPosition() {
                    const menuRect = menuSection.getBoundingClientRect();
                    const pesanRect = pesanSection.getBoundingClientRect();

                    if (pesanRect.top < window.innerHeight / 2) {
                        scrollIcon.setAttribute('icon', 'solar:round-arrow-up-broken');
                        scrollButton.dataset.target = 'menu';
                    } else {
                        scrollIcon.setAttribute('icon', 'solar:round-arrow-down-broken');
                        scrollButton.dataset.target = 'pesan';
                    }
                }

                // Klik tombol
                scrollButton.addEventListener('click', function() {
                    const target = scrollButton.dataset.target;
                    if (target === 'pesan') {
                        scrollToWithOffset(pesanSection);
                    } else {
                        scrollToWithOffset(menuSection);
                    }
                });

                // Jalankan saat scroll & load awal
                window.addEventListener('scroll', checkScrollPosition);
                checkScrollPosition();
            });
            // document.addEventListener('DOMContentLoaded', function() {

            // });
        </script>



    </div>
