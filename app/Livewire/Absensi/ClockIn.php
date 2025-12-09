<?php

namespace App\Livewire\Absensi;

use App\Models\Absensi;
use App\Models\Shift;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ClockIn extends Component
{
    // Pastikan tombol di Blade memanggil method ini: wire:click="submitClockIn"
    public function submitClockIn()
    {
        $this->dispatch('takePhotoAndLocation');
    }

    #[On('clockInData')]
    public function receiveClockInData($foto, $lokasi)
    {
        $user_id = Auth::id();
        $tanggal_hari_ini = now()->toDateString();

        // 1. CEK DATA HARI INI
        $absensi = Absensi::where('user_id', $user_id)
            ->where('tanggal', $tanggal_hari_ini)
            ->first();

        if (!$absensi) {
            // === LOGIKA CLOCK IN (MASUK) ===
            // Upload foto hanya jika validasi lolos
            $filename = $this->storeImage($foto);
            $this->handleClockIn($user_id, $tanggal_hari_ini, $filename, $lokasi);
        } else {
            // === LOGIKA CLOCK OUT (PULANG) ===

            // Cek 1: Apakah sudah clock out sebelumnya?
            if ($absensi->jam_keluar) {
                session()->flash('error', 'Anda sudah menyelesaikan absensi hari ini!');
                return;
            }

            // Upload foto baru dilakukan di sini agar tidak jadi file sampah jika validasi atas gagal
            $filename = $this->storeImage($foto);
            $this->handleClockOut($absensi, $filename, $lokasi);
        }

        return redirect()->to('/absen');
    }

    // --- LOGIKA CLOCK IN ---
    private function handleClockIn($user_id, $tanggal, $filename, $lokasi)
    {
        $shift = Shift::first();
        // Logic shift sederhana: Jika tidak ada setting shift, default jam 8 pagi
        $jamMasukJadwal = $shift ? $shift->jam_masuk : '08:00:00';
        $jamSekarang = now()->format('H:i:s');

        $status = ($jamSekarang > $jamMasukJadwal) ? 'terlambat' : 'hadir';

        Absensi::create([
            'user_id'   => $user_id,
            'tanggal'   => $tanggal,
            'jam_masuk' => $jamSekarang,
            'foto'      => $filename, // Pastikan kolom di DB bernama 'foto'
            'lokasi'    => $lokasi,   // Pastikan kolom di DB bernama 'lokasi'
            'status'    => $status,
        ]);

        session()->flash('success', 'Berhasil Clock In! Status: ' . ucfirst($status));
    }

    // --- LOGIKA CLOCK OUT ---
    private function handleClockOut($absensi, $filename, $lokasi)
    {
        $absensi->update([
            'jam_keluar'    => now()->format('H:i:s'),
            'foto_keluar'   => $filename, // Pastikan kolom ini sudah dibuat di DB
            'lokasi_keluar' => $lokasi,   // Pastikan kolom ini sudah dibuat di DB
            'status'        => 'complete',
        ]);

        session()->flash('success', 'Berhasil Clock Out! Hati-hati di jalan.');
    }

    // --- HELPER UPLOAD GAMBAR ---
    private function storeImage($foto_base64)
    {
        if (!$foto_base64) return null;

        $image_parts = explode(";base64,", $foto_base64);

        if (count($image_parts) >= 2) {
            $image_base64 = base64_decode($image_parts[1]);
            $filename = 'absensi/' . uniqid() . '.jpg';

            // Pastikan folder storage publik tersedia
            Storage::disk('public')->put($filename, $image_base64);
            return $filename;
        }

        return null;
    }

    public function render()
    {
        // Kirim data lengkap absen hari ini ke View
        // Agar kita bisa disable tombol jika status == complete
        $absensiHariIni = Absensi::where('user_id', Auth::id())
            ->where('tanggal', now()->toDateString())
            ->first();

        return view('livewire.absensi.clock-in', [
            'absensi' => $absensiHariIni
        ])->layout('layouts.absensi');
    }
}
