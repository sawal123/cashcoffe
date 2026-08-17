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

        $approval = DiscountApproval::with(['discount', 'kasir'])->find($this->selectedApprovalId);
        if ($approval && $approval->status === 'pending') {
            // Branch isolation server-side: non-superadmin hanya boleh memproses
            // approval dari kasir dengan branch yang sama. Permission `approve all
            // discount` adalah tipe diskon, BUKAN akses semua cabang.
            if (! $user->hasRole('superadmin')) {
                $requester = $approval->kasir;
                if (! $requester || (int) $requester->branch_id !== (int) $user->branch_id) {
                    abort(403, 'Anda tidak dapat memproses persetujuan diskon dari cabang lain.');
                }
            }

            if (!$approval->discount) {
                abort(403, 'Data diskon tidak ditemukan atau sudah tidak tersedia.');
            }

            $discountType = $approval->discount->type;

            if ($discountType === 'general') {
                if (!$user->can('approve general discount') && !$user->can('approve all discount')) {
                    abort(403, 'Anda tidak memiliki akses untuk menyetujui diskon ini.');
                }
            } else {
                if (!$user->can('approve all discount')) {
                    abort(403, 'Anda hanya boleh memproses diskon general.');
                }
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

        if ($user && $user->hasRole('superadmin')) {
            // Superadmin: dapat melihat approval seluruh branch
        } elseif ($user && ($user->can('approve all discount') || $user->can('approve general discount'))) {
            // Branch isolation: hanya approval dari kasir branch yang sama.
            // Permission `approve all discount` BUKAN akses semua cabang.
            $query->whereHas('kasir', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });

            // Can only see general discount approvals (jika tidak punya approve all)
            if (! $user->can('approve all discount')) {
                $query->whereHas('discount', function ($q) {
                    $q->where('type', 'general');
                });
            }
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
