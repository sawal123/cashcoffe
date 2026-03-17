<?php

namespace App\Livewire;

use App\Models\DiscountApproval;
use Livewire\Component;

class AdminDiscountNotif extends Component
{
    public function approveDiscount($id)
    {
        $approval = DiscountApproval::find($id);
        if ($approval) {
            $approval->update(['status' => 'approved']);
            $this->dispatch('showToast', message: 'Diskon berhasil disetujui', type: 'success');
        }
        // $this->dispatch('showToast', message: 'Diskon Disetujui Admin!', type: 'success');
    }

    public function rejectDiscount($id)
    {
        $approval = DiscountApproval::find($id);
        if ($approval) {
            $approval->update(['status' => 'rejected']);
            $this->dispatch('showToast', message: 'Diskon ditolak', type: 'info');
        }
    }

    public function render()
    {
        // Ambil data yang statusnya masih pending
        // Pastikan relasi 'discount' dan 'kasir' sudah ada di model DiscountApproval
        $pendingApprovals = DiscountApproval::with(['discount', 'kasir'])
            ->where('status', 'pending')
            ->latest()
            ->get();
        $historyApprovals = DiscountApproval::with(['discount', 'kasir'])
            ->whereIn('status', ['approved', 'rejected'])
            ->latest('updated_at') // Urutkan berdasarkan waktu di-acc/tolak
            ->take(10)
            ->get();

        return view('livewire.admin-discount-notif', [
            'pendingApprovals' => $pendingApprovals,
            'historyApprovals' => $historyApprovals,
        ]);
    }
}
