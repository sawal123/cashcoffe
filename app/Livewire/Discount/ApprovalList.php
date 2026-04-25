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

        $approval = DiscountApproval::find($this->selectedApprovalId);
        if ($approval && $approval->status === 'pending') {
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
        $approvals = DiscountApproval::with(['kasir', 'discount', 'approver'])
            ->where('status', $this->statusFilter)
            ->latest()
            ->paginate(10);

        return view('livewire.discount.approval-list', [
            'approvals' => $approvals
        ])->layout('layouts.app', ['title' => 'Discount Approval']);
    }
}
