<div>
    <x-toast />

    <div class="flex gap-2 mb-3">
        <x-droppage :perPage="$perPage" />
        <div class="sm:w-[300px] w-full">
            <x-input wire:model.live="search" place="Cari karyawan..." />
        </div>
    </div>

    <div class="table-responsive mt-2">
        <table class="table basic-border-table mb-2">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Role</th>
                    <th>Status Hari Ini</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($users as $user)
                    @php
                        $absen = $user->absensis->first();
                    @endphp
                    <tr>
                        <td>
                            {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                        </td>

                        <td>{{ $user->name }}</td>

                        <td>
                            <span class="px-3 py-1 rounded-full text-sm bg-info-600 text-white">
                                {{ ucfirst($user->roles->first()->name ?? '-') }}
                            </span>
                        </td>

                        <td>
                            @if ($absen)
                                <span
                                    class="px-3 py-1 rounded-full text-sm
                                {{ $absen->status == 'terlambat' ? 'bg-danger text-white' : 'bg-success-600 text-white' }}">
                                    {{ ucfirst($absen->status) }}
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-full text-sm bg-danger-600 text-danger-600">
                                    Belum Absen
                                </span>
                            @endif
                        </td>

                        <td>{{ $absen->jam_masuk ?? '-' }}</td>
                        <td>{{ $absen->jam_keluar ?? '-' }}</td>

                        <td>
                            <a href="{{ route('absense.show', $user->id) }}" wire:navigate
                                class="w-8 h-8 bg-primary-100 text-primary-600 rounded-full inline-flex items-center justify-center">
                                <iconify-icon icon="lucide:eye"></iconify-icon>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">Data karyawan tidak ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $users->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

</div>
