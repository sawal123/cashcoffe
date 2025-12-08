<div>
    {{-- If your happiness depends on money, you will never be happy with yourself. --}}

    <div class="bg-white dark:bg-neutral-800 rounded-xl p-4 mb-4">
        <h5 class="text-lg font-semibold mb-1">{{ $user->name }}</h5>
        <p class="text-sm text-neutral-500">{{ $user->email }}</p>
    </div>

    <div class="flex gap-2 mb-3">
        <select wire:model.live="month" class="form-select">
            @foreach (range(1, 12) as $m)
                <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
            @endforeach
        </select>

        <select wire:model.live="year" class="form-select">
            @foreach (range(now()->year - 2, now()->year) as $y)
                <option value="{{ $y }}">{{ $y }}</option>
            @endforeach
        </select>
    </div>

    <div class="table-responsive">
        <table class="table basic-border-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tanggal</th>
                    <th>Masuk</th>
                    <th>Keluar</th>
                    <th>Status</th>
                    <th>Foto</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($absensis as $item)
                    <tr>
                        <td>{{ ($absensis->currentPage() - 1) * $absensis->perPage() + $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}</td>
                        <td>{{ $item->jam_masuk ?? '-' }}</td>
                        <td>{{ $item->jam_keluar ?? '-' }}</td>

                        <td>
                            <span
                                class="px-4 py-1 rounded-full text-sm
                            {{ $item->status == 'terlambat' ? 'bg-warning-100 text-warning-600' : 'bg-success-100 text-success-600' }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>

                        <td>
                            @if ($item->foto)
                                <a href="{{ Storage::url($item->foto) }}" target="_blank"
                                    class="text-primary-600 underline">
                                    Lihat
                                </a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">Tidak ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $absensis->links(data: ['scroll' => false]) }}
    </div>


</div>
