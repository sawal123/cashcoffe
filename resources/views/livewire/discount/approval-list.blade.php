<div>
    <x-toast />
    <div class="flex gap-2 mb-4">
        <button wire:click="setStatusFilter('pending')" class="px-4 py-2 text-sm font-medium rounded-lg transition {{ $statusFilter === 'pending' ? 'bg-orange-500 text-white' : 'bg-white border text-slate-600 hover:bg-slate-50' }}">Pending</button>
        <button wire:click="setStatusFilter('approved')" class="px-4 py-2 text-sm font-medium rounded-lg transition {{ $statusFilter === 'approved' ? 'bg-green-500 text-white' : 'bg-white border text-slate-600 hover:bg-slate-50' }}">Approved</button>
        <button wire:click="setStatusFilter('rejected')" class="px-4 py-2 text-sm font-medium rounded-lg transition {{ $statusFilter === 'rejected' ? 'bg-red-500 text-white' : 'bg-white border text-slate-600 hover:bg-slate-50' }}">Rejected</button>
    </div>

    <div class="overflow-x-auto whitespace-nowrap">
        <table class="min-w-full leading-normal">
            <thead>
                <tr>
                    <th class="px-5 py-3 border-b-2 border-slate-200 bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Tanggal</th>
                    <th class="px-5 py-3 border-b-2 border-slate-200 bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Kasir</th>
                    <th class="px-5 py-3 border-b-2 border-slate-200 bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Diskon</th>
                    <th class="px-5 py-3 border-b-2 border-slate-200 bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Tipe</th>
                    <th class="px-5 py-3 border-b-2 border-slate-200 bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                    @if($statusFilter !== 'pending')
                    <th class="px-5 py-3 border-b-2 border-slate-200 bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Direspon Oleh</th>
                    <th class="px-5 py-3 border-b-2 border-slate-200 bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Keterangan</th>
                    @endif
                    <th class="px-5 py-3 border-b-2 border-slate-200 bg-slate-50 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approvals as $approval)
                <tr>
                    <td class="px-5 py-5 border-b border-slate-200 bg-white text-sm">
                        {{ $approval->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-5 py-5 border-b border-slate-200 bg-white text-sm font-semibold">
                        {{ $approval->kasir->name ?? 'Kasir tidak diketahui' }}
                    </td>
                    <td class="px-5 py-5 border-b border-slate-200 bg-white text-sm">
                        <span class="text-blue-600 font-medium">{{ $approval->discount->nama_diskon ?? 'Diskon dihapus' }}</span>
                    </td>
                    <td class="px-5 py-5 border-b border-slate-200 bg-white text-sm">
                        {{ ucfirst($approval->discount->jenis_diskon ?? '-') }}
                    </td>
                    <td class="px-5 py-5 border-b border-slate-200 bg-white text-sm">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            {{ $approval->status === 'pending' ? 'bg-orange-100 text-orange-600' : '' }}
                            {{ $approval->status === 'approved' ? 'bg-green-100 text-green-600' : '' }}
                            {{ $approval->status === 'rejected' ? 'bg-red-100 text-red-600' : '' }}
                        ">
                            {{ ucfirst($approval->status) }}
                        </span>
                    </td>
                    @if($statusFilter !== 'pending')
                    <td class="px-5 py-5 border-b border-slate-200 bg-white text-sm">
                        {{ $approval->approver->name ?? '-' }}
                    </td>
                    <td class="px-5 py-5 border-b border-slate-200 bg-white text-sm">
                        {{ $approval->keterangan ?? '-' }}
                    </td>
                    @endif
                    <td class="px-5 py-5 border-b border-slate-200 bg-white text-sm text-center">
                        @if($approval->status === 'pending')
                        <div class="flex gap-2 justify-center">
                            <button wire:click="openModal({{ $approval->id }}, 'approve')" class="px-3 py-1.5 bg-green-50 text-green-600 hover:bg-green-100 rounded-lg text-xs font-semibold transition">Approve</button>
                            <button wire:click="openModal({{ $approval->id }}, 'reject')" class="px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-xs font-semibold transition">Reject</button>
                        </div>
                        @else
                        -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-10 bg-white text-center text-sm text-slate-500">
                        Tidak ada request diskon {{ $statusFilter }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $approvals->links() }}
    </div>

    {{-- Modal Action --}}
    <x-modal name="action-approval-modal" title="Tindak Lanjut Persetujuan">
        <form wire:submit.prevent="submitAction">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Keterangan / Alasan {{ $actionType == 'approve' ? 'Persetujuan' : 'Penolakan' }}
                </label>
                <textarea wire:model="keterangan" class="w-full form-control rounded-xl border border-slate-300 p-3" rows="3" placeholder="Masukkan keterangan (wajib)..." required></textarea>
                @error('keterangan') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>
            
            <div class="flex justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', { name: 'action-approval-modal' })" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-sm font-medium transition">Batal</button>
                <button type="submit" class="px-4 py-2 {{ $actionType == 'approve' ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600' }} text-white rounded-lg text-sm font-medium transition">
                    Konfirmasi {{ ucfirst($actionType) }}
                </button>
            </div>
        </form>
    </x-modal>
</div>
