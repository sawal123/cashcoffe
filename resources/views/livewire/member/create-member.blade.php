<div>
    <x-toast />
    @php
        $submit = $memberId ? 'update(' . json_encode($memberId) . ')' : 'simpan';
        $button = $memberId ? 'Update' : 'Simpan';
    @endphp
    <form wire:submit.prevent="{{ $submit }}" class="grid grid-cols-12 gap-4">

        {{-- Nama Member --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Nama Member</label>
            <input type="text" wire:model="name" class="form-control" placeholder="Contoh: Budi Santoso" required>
        </div>

        {{-- Email --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Email (optional)</label>
            <input type="email" wire:model="email" class="form-control" placeholder="email@example.com">
        </div>



        {{-- Nomor Telepon --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Nomor Telepon</label>
            <input type="text" wire:model="phone" class="form-control" placeholder="0812xxxxxxxx">
        </div>

        {{-- Alamat --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Alamat (optional)</label>
            <input type="text" wire:model="address" class="form-control" placeholder="Masukkan alamat lengkap">
        </div>

        {{-- Tombol Submit --}}
        <div class="col-span-12">
            <button class="btn btn-primary-600" type="submit">{{ $button }} Member</button>
        </div>

    </form>

</div>
