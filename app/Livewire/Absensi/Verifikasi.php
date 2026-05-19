<?php

namespace App\Livewire\Absensi;

use App\Models\Absensi;
use App\Models\Shift;
use App\Models\UserShift;
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
        $user = Auth::user();
        $user_id = $user->id;
        $tanggal_hari_ini = now()->toDateString();

        if (!$this->fotoBase64 || !$this->lokasiStr) {
            session()->flash('error', 'Gagal memproses verifikasi. Mohon pastikan deteksi wajah dan lokasi GPS aktif.');
            return;
        }

        // Geofencing Check
        if ($user->branch && $user->branch->latitude && $user->branch->longitude) {
            $coords = explode(',', $this->lokasiStr);
            if (count($coords) == 2) {
                $lat1 = floatval(trim($coords[0]));
                $lon1 = floatval(trim($coords[1]));
                $lat2 = floatval($user->branch->latitude);
                $lon2 = floatval($user->branch->longitude);
                $distance = $this->getDistance($lat1, $lon1, $lat2, $lon2);
                $radius = $user->branch->radius ?? $user->branch->radius_meter ?? 20;

                if ($distance > $radius) {
                    session()->flash('error', 'Anda berada di luar radius cabang (' . $radius . 'm). Jarak Anda: ' . round($distance) . 'm');
                    return redirect()->to('/absen/clock-in');
                }
            }
        }

        $absensi = Absensi::where('user_id', $user_id)
            ->where('tanggal', $tanggal_hari_ini)
            ->whereNull('jam_keluar')
            ->whereNotNull('jam_masuk')
            ->orderBy('jam_masuk', 'desc')
            ->first();

        if ($this->type === 'masuk') {
            // Kita sudah menangani pengecekan ganda di handleClockIn
            $filename = $this->storeImage($this->fotoBase64);
            $redirect = $this->handleClockIn($user, $tanggal_hari_ini, $filename, $this->lokasiStr);
            if ($redirect) {
                return $redirect;
            }
        } else {
            if (!$absensi) {
                session()->flash('error', 'Anda belum melakukan Clock-in untuk shift yang aktif atau semua shift hari ini sudah selesai.');
                return redirect()->to('/absen/clock-in');
            }

            $filename = $this->storeImage($this->fotoBase64);
            $redirect = $this->handleClockOut($absensi, $filename, $this->lokasiStr);
            if ($redirect) {
                return $redirect;
            }
        }

        return redirect()->to('/absen');
    }

    private function getDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // in meters
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);
    
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
    
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
          cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }

    private function handleClockIn($user, $tanggal, $filename, $lokasi)
    {
        $jamSekarang = now()->format('H:i:s');
        $currentTime = \Carbon\Carbon::createFromFormat('H:i:s', $jamSekarang);

        // Cari semua plotting shift untuk user hari ini
        $allUserShifts = UserShift::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('tanggal', $tanggal)
            ->get();

        if ($allUserShifts->isEmpty()) {
            session()->flash('error', 'Jadwal shift Anda untuk hari ini belum diatur oleh Owner.');
            return redirect()->to('/absen/clock-in');
        }

        // Cari shift yang belum di-absen hari ini
        $availableShift = null;
        foreach ($allUserShifts as $us) {
            $alreadyClocked = Absensi::where('user_id', $user->id)
                ->whereDate('tanggal', $tanggal)
                ->where('shift_id', $us->shift_id)
                ->exists();

            if (!$alreadyClocked) {
                // Jika ada irisan jam (Critical Check), pilih yang paling mendekati jam masuknya
                // Namun, kita tetap mengikuti plotting owner secara ketat.
                if (!$availableShift) {
                    $availableShift = $us;
                } else {
                    // Jika ada lebih dari satu plotting, pilih yang jam masuknya paling mendekati jam sekarang
                    $diffExisting = $currentTime->diffInMinutes(\Carbon\Carbon::parse($availableShift->shift->jam_masuk));
                    $diffCurrent = $currentTime->diffInMinutes(\Carbon\Carbon::parse($us->shift->jam_masuk));
                    if ($diffCurrent < $diffExisting) {
                        $availableShift = $us;
                    }
                }
            }
        }

        if (!$availableShift) {
            session()->flash('error', 'Semua jadwal shift Anda untuk hari ini sudah selesai di-absen.');
            return redirect()->to('/absen/clock-in');
        }

        $shift = $availableShift->shift;
        $jamMasukJadwal = $shift->jam_masuk;
        $maksimalTelat = $shift->maksimal_telat_menit ?? 60;
        $dendaTelat = $shift->denda_telat ?? 20000;

        // LOGIKA BARU: Validasi Earliest Check-In (Maksimal N menit sebelum shift)
        $waktuSekarang = \Carbon\Carbon::now();
        $jamMasukShift = \Carbon\Carbon::parse($availableShift->tanggal)->copy()->setTimeFromTimeString($jamMasukJadwal);
        $batasAwalMenit = $shift->batas_awal_absen_menit ?? 60;
        $batasAwalAbsen = $jamMasukShift->copy()->subMinutes($batasAwalMenit);

        if ($waktuSekarang->lt($batasAwalAbsen)) {
            session()->flash('error', 'Gagal melakukan absensi. Anda hanya diperbolehkan absen masuk maksimal ' . $this->formatBatasMenit($batasAwalMenit) . ' sebelum shift dimulai.');
            return redirect()->to('/absen/clock-in');
        }
        
        // Cek jika ini Double Shift (baik via ID 3 atau flag is_double_shift)
        $isDouble = ($shift->id == 3 || $availableShift->is_double_shift);

        $scheduledTime = \Carbon\Carbon::createFromFormat('H:i:s', $jamMasukJadwal);

        $status = 'hadir';
        if ($currentTime->gt($scheduledTime)) {
            $diffInMinutes = $currentTime->diffInMinutes($scheduledTime);
            
            if ($diffInMinutes > $maksimalTelat) {
                // Catat sebagai Alpha karena telat parah
                Absensi::create([
                    'user_id'   => $user->id,
                    'tanggal'   => $tanggal,
                    'shift_id'  => $shift->id,
                    'status'    => 'alpha',
                    'is_double_shift' => $isDouble,
                    'keterangan'=> 'Telat parah (' . $diffInMinutes . ' mnt) pada shift ' . $shift->nama_shift,
                ]);
                $user->increment('potongan_alpha', $dendaTelat);
                session()->flash('error', 'Anda terlambat ' . $diffInMinutes . ' menit pada ' . $shift->nama_shift . '. Absen ditolak (Status: Alpha).');
                return redirect()->to('/absen/clock-in');
            } else {
                $status = 'terlambat';
                $user->increment('potongan_terlambat', $dendaTelat);
            }
        }

        Absensi::create([
            'user_id'   => $user->id,
            'tanggal'   => $tanggal,
            'shift_id'  => $shift->id,
            'jam_masuk' => $jamSekarang,
            'foto'      => $filename,
            'lokasi'    => $lokasi,
            'status'    => $status,
            'is_double_shift' => $isDouble,
        ]);

        $msg = 'Berhasil Absen ' . $shift->nama_shift . '! Status: ' . ucfirst($status);
        if ($isDouble) {
            $msg .= ' (Double Shift Terdeteksi - Gaji 2x)';
        }
        if ($status === 'terlambat') {
            $msg .= ' (Denda: Rp ' . number_format($dendaTelat, 0, ',', '.') . ')';
        }
        session()->flash('success', $msg);
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
        $user = Auth::user();
        $branchLat = $user->branch->latitude ?? -6.200000;
        $branchLng = $user->branch->longitude ?? 106.816666;
        $branchRadius = $user->branch->radius ?? $user->branch->radius_meter ?? 20;

        return view('livewire.absensi.verifikasi', compact('branchLat', 'branchLng', 'branchRadius'))->layout('layouts.absensi');
    }

    private function formatBatasMenit($menit)
    {
        if ($menit >= 60) {
            $jam = floor($menit / 60);
            $sisaMenit = $menit % 60;
            return $jam . ' jam' . ($sisaMenit > 0 ? ' ' . $sisaMenit . ' menit' : '');
        }
        return $menit . ' menit';
    }
}
