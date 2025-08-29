<div class="sm:flex  justify-between lg:flex-row gap-4">
    <x-toast />
    {{-- Kiri: Produk --}}

    <div class="flex-1 min-w-0 order-1 lg:order-1">
        <div class="sm:w-[300px] w-ful">
            <div class="flex gap-2">
                <x-droppage perPage="{{ $perPage }}" />
                <div class="sm:w-[300px] w-ful">
                    <x-input wire:model.live="search" place="Cari..." />
                </div>
            </div>
        </div>
        <div class="grid w-full grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($menus as $item)
                <article wire:click="addPesanan({{ $item->id }})"
                    class="hover:shadow-xl   cursor-pointer group flex flex-col rounded-xl overflow-hidden border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="h-40 md:h-52 overflow-hidden">
                        <img src="{{ asset('storage/' . $item->gambar) }}"
                            class="object-cover w-full h-full transition duration-500 ease-out group-hover:scale-105"
                            alt="Produk" />
                    </div>
                    <div class="p-3">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                            {{ $item->nama_menu }}
                        </p>
                        <p class="text-base font-bold text-slate-900 dark:text-white">
                            Rp{{ number_format($item->harga, 0, ',', '.') }}
                        </p>
                    </div>
                </article>
            @endforeach
        </div>
        {{ $menus->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    {{-- Kanan: Pesanan --}}
    <div
        class="sm:w-[300px] w-ful shrink-0 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800 shadow-sm p-2 h-fit order-2 lg:order-2">
        <p class="font-bold text-lg mb-2 text-slate-800 dark:text-white">Pesanan Item</p>
        <hr class="border-slate-200 dark:border-slate-700 mb-3">
        <select id="mejas_id" wire:model="mejas_id"
            class="form-select w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
            required>
            <option value="" class="text-neutral-500">-- Pilih Meja --</option>
            @foreach ($mejas as $meja)
                <option value="{{ $meja->id }}" class="text-neutral-800 dark:text-neutral-200">
                    {{ $meja->nama }}
                </option>
            @endforeach
        </select>
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
        @if ($orderId)
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
        <div class="flex justify-between gap-4 items-center mt-2">
            <span class="text-sm dark:text-slate-200 text-slate-900">Status: </span>
            <select id="status" wire:model="status"
                class="form-select w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
                required>

                <option value="diproses" class="text-neutral-800 dark:text-neutral-200">
                    Diproses
                </option>
                <option value="selesai" class="text-neutral-800 dark:text-neutral-200">
                    Selesai
                </option>
                <option value="dibatalkan" class="text-neutral-800 dark:text-neutral-200">
                    Dibatalkan
                </option>

            </select>
        </div>

        @endif

        <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
            <div class="flex justify-between mb-1">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Total</span>
                <span class="text-sm font-bold text-slate-900 dark:text-white">
                    Rp{{ number_format(collect($pesanan)->sum(fn($p) => $p['harga'] * $p['qty']), 0, ',', '.') }}
                </span>
            </div>
            <button wire:click="{{ $submit }}"
                class="w-full bg-blue-600 mt-2 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">
                {{ $teks }} Pesanan
            </button>
        </div>

    </div>
