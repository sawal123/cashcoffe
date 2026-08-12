<?php

namespace App\Livewire\Discount;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DiscountApproval;
use Illuminate\Support\Facades\Auth;

class ApprovalList extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';

    public $keterangan = '';
    public $selectedApprovalId = null;
    public $actionType = null;
    public $statusFilter = 'pending';

    public function setStatusFilter($status)
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function openModal($id, $type)
    {
        $this->selectedApprovalId = $id;
        $this->actionType = $type;
        $this->keterangan = '';
        $this->dispatch('open-modal', name: 'action-approval-modal');
    }

    public function submitAction()
    {
        $this->validate([
            'keterangan' => 'required|string|max:255'
        ]);

        $user = Auth::user();
        if (!$user || (!$user->can('approve general discount') && !$user->can('approve all discount'))) {
            abort(403, 'Anda tidak memiliki akses untuk memproses persetujuan diskon.');
        }

        $approval = DiscountApproval::with('discount')->find($this->selectedApprovalId);
        if ($approval && $approval->status === 'pending') {
            $discountType = $approval->discount->type ?? 'general';

            if ($discountType !== 'general' && !$user->can('approve all discount')) {
                abort(403, 'Anda hanya boleh memproses diskon general.');
            }

            $approval->update([
                'status' => $this->actionType === 'approve' ? 'approved' : 'rejected',
                'approved_by' => Auth::id(),
                'keterangan' => $this->keterangan
            ]);

            $this->dispatch('close-modal', name: 'action-approval-modal');
            $this->dispatch('showToast', message: 'Request ' . ucfirst($this->actionType) . 'd!', type: 'success', title: 'Berhasil');
        }
    }

    public function render()
    {
        $user = Auth::user();

        $query = DiscountApproval::with(['kasir', 'discount', 'approver'])
            ->where('status', $this->statusFilter);

        if ($user && $user->can('approve all discount')) {
            // Can see all approvals
        } elseif ($user && $user->can('approve general discount')) {
            // Can only see general discount approvals
            $query->whereHas('discount', function ($q) {
                $q->where('type', 'general');
            });
        } else {
            // No approval permission at all
            $query->whereRaw('1 = 0');
        }

        $approvals = $query->latest()->paginate(10);

        return view('livewire.discount.approval-list', [
            'approvals' => $approvals
        ])->layout('layouts.app', ['title' => 'Discount Approval']);
    }
}
