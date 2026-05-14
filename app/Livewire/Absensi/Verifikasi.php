<?php

namespace App\Livewire\Absensi;

use App\Models\Absensi;
use App\Models\Shift;
use Livewire\Component;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class Verifikasi extends Component
{
    #[Url]
    public $type = 'masuk'; // 'masuk' atau 'keluar'

    public $fotoBase64;
    public $lokasiStr;

    public function submitVerifikasi()
    {
        $user_id = Auth::id();
        $tanggal_hari_ini = now()->toDateString();

        if (!$this->fotoBase64 || !$this->lokasiStr) {
            session()->flash('error', 'Gagal memproses verifikasi. Mohon pastikan deteksi wajah dan lokasi GPS aktif.');
            return;
        }

        $absensi = Absensi::where('user_id', $user_id)
            ->where('tanggal', $tanggal_hari_ini)
            ->first();

        if ($this->type === 'masuk') {
            if ($absensi && $absensi->jam_masuk) {
                session()->flash('error', 'Anda sudah melakukan Clock-in hari ini.');
                return redirect()->to('/absen/clock-in');
            }

            $filename = $this->storeImage($this->fotoBase64);
            $this->handleClockIn($user_id, $tanggal_hari_ini, $filename, $this->lokasiStr);
        } else {
            if (!$absensi || !$absensi->jam_masuk) {
                session()->flash('error', 'Anda belum melakukan Clock-in. Tidak dapat memproses Clock-out.');
                return redirect()->to('/absen/clock-in');
            }

            if ($absensi->jam_keluar) {
                session()->flash('error', 'Anda sudah menyelesaikan absensi pulang hari ini.');
                return redirect()->to('/absen/clock-in');
            }

            $filename = $this->storeImage($this->fotoBase64);
            $this->handleClockOut($absensi, $filename, $this->lokasiStr);
        }

        return redirect()->to('/absen');
    }

    private function handleClockIn($user_id, $tanggal, $filename, $lokasi)
    {
        $shift = Shift::first();
        $jamMasukJadwal = $shift ? $shift->jam_masuk : '08:00:00';
        $jamSekarang = now()->format('H:i:s');

        $status = ($jamSekarang > $jamMasukJadwal) ? 'terlambat' : 'hadir';

        Absensi::create([
            'user_id'   => $user_id,
            'tanggal'   => $tanggal,
            'jam_masuk' => $jamSekarang,
            'foto'      => $filename,
            'lokasi'    => $lokasi,
            'status'    => $status,
        ]);

        session()->flash('success', 'Verifikasi Wajah Berhasil! Anda telah Clock In dengan status: ' . ucfirst($status));
    }

    private function handleClockOut($absensi, $filename, $lokasi)
    {
        $absensi->update([
            'jam_keluar'    => now()->format('H:i:s'),
            'foto_keluar'   => $filename,
            'lokasi_keluar' => $lokasi,
        ]);

        session()->flash('success', 'Verifikasi Pulang Berhasil! Hati-hati di jalan.');
    }

    private function storeImage($foto_base64)
    {
        if (!$foto_base64) return null;

        $image_parts = explode(";base64,", $foto_base64);

        if (count($image_parts) >= 2) {
            $image_base64 = base64_decode($image_parts[1]);
            $filename = 'absensi/' . uniqid() . '.jpg';

            Storage::disk('public')->put($filename, $image_base64);
            return $filename;
        }

        return null;
    }

    public function render()
    {
        return view('livewire.absensi.verifikasi')->layout('layouts.absensi');
    }
}
