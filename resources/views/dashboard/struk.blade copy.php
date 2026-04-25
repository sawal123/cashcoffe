<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #{{ $pesanan->kode }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #1a1a1a;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        .receipt {
            background: #fff;
            width: 300px;
            padding: 20px 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        }

        /* -------- HEADER -------- */
        .header {
            text-align: center;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #aaa;
        }

        .brand {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #111;
        }

        .brand span {
            color: #2563eb;
        }

        .header p {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
            line-height: 1.5;
        }

        /* -------- META INFO -------- */
        .meta {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #aaa;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #444;
            margin-bottom: 3px;
        }

        .meta-row .label {
            color: #888;
            white-space: nowrap;
            margin-right: 8px;
        }

        .meta-row .value {
            text-align: right;
            font-weight: bold;
            color: #111;
        }

        /* -------- ITEMS -------- */
        .section-title {
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
            margin-bottom: 6px;
        }

        .items {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #aaa;
        }

        .item {
            margin-bottom: 8px;
        }

        .item-name {
            font-weight: bold;
            font-size: 11px;
            color: #111;
        }

        .item-variant {
            font-size: 10px;
            color: #888;
            font-style: italic;
            margin-top: 1px;
        }

        .item-price-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #444;
            margin-top: 2px;
        }

        /* -------- TOTALS -------- */
        .totals {
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 2px solid #111;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 4px;
        }

        .total-row.highlight {
            font-weight: 900;
            font-size: 14px;
            color: #111;
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px dashed #aaa;
        }

        .total-row .green {
            color: #16a34a;
        }

        /* -------- PAYMENT -------- */
        .payment {
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px dashed #aaa;
        }

        .change-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 4px;
        }

        /* -------- FOOTER -------- */
        .footer {
            text-align: center;
            font-size: 10px;
            color: #888;
            line-height: 1.7;
        }

        .footer .thank-you {
            font-size: 12px;
            font-weight: 900;
            color: #111;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .divider {
            border: none;
            border-top: 1px dashed #ccc;
            margin: 10px 0;
        }

        /* -------- PRINT BUTTON -------- */
        .print-btn {
            display: block;
            width: 100%;
            margin-top: 16px;
            padding: 10px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .print-btn:hover {
            background: #1d4ed8;
        }

        /* -------- PRINT MEDIA -------- */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .receipt {
                box-shadow: none;
                width: 100%;
                padding: 8px 4px;
            }

            .print-btn {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">

        {{-- HEADER --}}
        <div class="header">
            <div class="brand">Cash<span>Coffe</span></div>
            <p>Sistem Kasir Digital</p>
            @if ($pesanan->meja)
                <p>Meja: <strong>{{ $pesanan->meja->nama_meja ?? $pesanan->meja->kode }}</strong></p>
            @endif
        </div>

        {{-- META INFO --}}
        <div class="meta">
            <div class="meta-row">
                <span class="label">No. Struk</span>
                <span class="value">#{{ $pesanan->kode }}</span>
            </div>
            <div class="meta-row">
                <span class="label">Tanggal</span>
                <span class="value">{{ $pesanan->created_at->format('d/m/Y H:i') }}</span>
            </div>
            @if ($pesanan->nama)
                <div class="meta-row">
                    <span class="label">Pelanggan</span>
                    <span class="value">{{ $pesanan->nama }}</span>
                </div>
            @endif
            @if ($pesanan->user)
                <div class="meta-row">
                    <span class="label">Kasir</span>
                    <span class="value">{{ $pesanan->user->name }}</span>
                </div>
            @endif
            <div class="meta-row">
                <span class="label">Status</span>
                <span class="value">{{ strtoupper($pesanan->status) }}</span>
            </div>
        </div>

        {{-- ORDER ITEMS --}}
        <div class="items">
            <div class="section-title">Daftar Pesanan</div>
            @foreach ($pesanan->items as $item)
                <div class="item">
                    <div class="item-name">{{ $item->menu->nama_menu ?? $item->menus->nama_menu ?? 'Menu' }}</div>

                    {{-- Variant Options --}}
                    @if ($item->variants && $item->variants->count() > 0)
                        <div class="item-variant">
                            {{ $item->variants->pluck('nama_opsi')->filter()->join(', ') }}
                        </div>
                    @endif

                    <div class="item-price-row">
                        <span>{{ $item->qty }}x Rp{{ number_format($item->harga_satuan ?? 0, 0, ',', '.') }}</span>
                        <span>Rp{{ number_format(($item->subtotal ?? ($item->harga_satuan * $item->qty)), 0, ',', '.') }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- TOTALS --}}
        @php
            $subtotal  = $pesanan->total ?? 0;
            $discount  = $pesanan->discount_value ?? 0;
            $total     = max(0, $subtotal - $discount);
        @endphp

        <div class="totals">
            <div class="total-row">
                <span>Subtotal</span>
                <span>Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
            </div>
            @if ($discount > 0)
                <div class="total-row">
                    <span>Diskon
                        @if ($pesanan->discount)
                            ({{ $pesanan->discount->kode_diskon }})
                        @endif
                    </span>
                    <span class="green">-Rp{{ number_format($discount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="total-row highlight">
                <span>TOTAL</span>
                <span>Rp{{ number_format($total, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- PAYMENT --}}
        <div class="payment">
            <div class="section-title">Pembayaran</div>
            <div class="change-row">
                <span>Metode</span>
                <span><strong>{{ strtoupper($pesanan->metode_pembayaran ?? 'BELUM BAYAR') }}</strong></span>
            </div>
            @if (strtolower($pesanan->metode_pembayaran ?? '') === 'tunai' && isset($pesanan->uang_tunai) && $pesanan->uang_tunai > 0)
                <div class="change-row">
                    <span>Tunai</span>
                    <span>Rp{{ number_format($pesanan->uang_tunai, 0, ',', '.') }}</span>
                </div>
                <div class="change-row">
                    <span>Kembalian</span>
                    <span><strong>Rp{{ number_format(max(0, $pesanan->uang_tunai - $total), 0, ',', '.') }}</strong></span>
                </div>
            @endif
        </div>

        {{-- FOOTER --}}
        <div class="footer">
            <div class="thank-you">Terima Kasih!</div>
            <p>Semoga hari Anda menyenangkan</p>
            <hr class="divider">
            <p>Dicetak: {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>

        {{-- PRINT BUTTON (hidden on print) --}}
        <button class="print-btn" onclick="window.print()">
            🖨 Cetak Struk
        </button>

    </div>

    <script>
        // Auto print saat halaman dimuat
        window.addEventListener('load', function () {
            // Sedikit delay agar konten render sempurna
            setTimeout(function () {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
