<?php

namespace App\Livewire\Transaksi;

use Carbon\Carbon;
use App\Models\Pesanan;
use Livewire\Component;
use Livewire\WithPagination;

class Transaksi extends Component
{
    use WithPagination;
    public $pembayaran = ['tunai', 'qris', 'transfer', 'kartu', 'shopeefood', 'gofood', 'grabfood', 'komplemen'];
    public $search = '';
    public $filterPembayaran = '';
    public $dateFrom;
    public $dateTo;
    public $perPage = 20;
    public $totalPerMetode = [];
    public $totalOmset = 0;
    protected $queryString = [
        'search' => ['except' => ''],
        'filterPembayaran' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function updating($field)
    {
        // reset pagination setiap kali filter berubah
        if (in_array($field, ['search', 'filterPembayaran', 'dateFrom', 'dateTo'])) {
            $this->resetPage();
        }
    }
    public function render()
    {
        $query = Pesanan::query()
            ->with('user')
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('kode', 'like', "%{$this->search}%")
                        ->orWhere('nama', 'like', "%{$this->search}%")
                        ->orWhereHas('user', function ($userQuery) {
                            $userQuery->where('name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->filterPembayaran, function ($q) {
                if ($this->filterPembayaran === 'belum') {
                    $q->whereNull('metode_pembayaran');
                } else {
                    $q->where('metode_pembayaran', $this->filterPembayaran);
                }
            })
            ->when($this->dateFrom && $this->dateTo, function ($q) {
                $q->whereBetween('created_at', [
                    \Carbon\Carbon::parse($this->dateFrom)->startOfDay(),
                    \Carbon\Carbon::parse($this->dateTo)->endOfDay(),
                ]);
            });

        // Hitung total per metode pembayaran
        $this->totalPerMetode = $query->clone()
            ->where('status', 'selesai')
            ->whereNotNull('metode_pembayaran')
            ->selectRaw('metode_pembayaran, SUM(total - discount_value) as total')
            ->groupBy('metode_pembayaran')
            ->pluck('total', 'metode_pembayaran')
            ->toArray();

        // Hitung total omset keseluruhan
        $this->totalOmset = $query->clone()
            ->where('status', 'selesai')
            ->where('metode_pembayaran', 'komplemen')
            ->whereNotNull('metode_pembayaran')
            ->sum(\DB::raw('total - discount_value'));
        $orders = $query->latest()->paginate($this->perPage);

        return view('livewire.transaksi.transaksi', [
            'orders' => $orders
        ]);
    }
}
