<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Struk {{ $pesanan->kode }}</title>
    <style>
        /* Reset dasar untuk printer thermal */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            /* Font standar thermal */
            font-size: 12px;
            line-height: 1.2;
            width: 58mm;
            /* Sesuaikan dengan lebar kertas (biasanya 58mm atau 80mm) */
            padding: 2mm;
        }

        .center {
            text-align: center;
            margin-bottom: 5px;
        }

        .bold {
            font-weight: bold;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            vertical-align: top;
            padding: 1px 0;
        }

        /* Definisi kolom agar tidak berantakan */
        .col-nama {
            width: 50%;
            text-align: left;
        }

        .col-qty {
            width: 15%;
            text-align: center;
        }

        .col-harga {
            width: 35%;
            text-align: right;
        }

        .right {
            text-align: right;
        }

        @media print {
            @page {
                size: 58mm auto;
                /* Biarkan tinggi otomatis */
                margin: 0;
            }

            body {
                width: 58mm;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print();">
    <div class="center">
        <img src="{{ asset('logo/logo.png') }}" style="max-width:60px; filter: grayscale(1);"><br>
        <div class="bold">Temuan Space</div>
        <div style="font-size: 10px;">Jl. Tenis No.30, Ps. Merah Bar., Medan</div>
        <div>{{ date('d/m/Y H:i') }}</div>
    </div>

    <div class="separator"></div>

    <table>
        <tr>
            <td>Inv</td>
            <td>: {{ $pesanan->kode }}</td>
        </tr>
        <tr>
            <td>Kasir</td>
            <td>: {{ $pesanan->user->name }}</td>
        </tr>
        <tr>
            <td>Cust</td>
            <td>: {{ $pesanan->nama ?? '-' }}</td>
        </tr>
    </table>

    <div class="separator"></div>

    <table>
        {{-- {{ $pesanan->items }} --}}
        @foreach ($pesanan->items as $item)
            <tr>
                <td class="col-nama">{{ $item->menu->nama_menu }}</td>
                <td class="col-qty">{{ $item->qty }}</td>
                <td class="col-harga">{{ number_format($item->subtotal) }}</td>
            </tr>
            {{-- Tampilkan variant jika ada --}}
            @if ($item->variants->count() > 0)
                <tr>
                    <td colspan="3" style="padding-left: 5px; font-size: 10px; color: #555;">
                        @foreach ($item->variants as $variant)
                            <div>◦ {{ $variant->nama_opsi }}
                                @if($variant->extra_price > 0)
                                    +{{ number_format($variant->extra_price) }}
                                @endif
                            </div>
                        @endforeach
                    </td>
                </tr>
            @endif
        @endforeach
    </table>

    <div class="separator"></div>

    <table>
        <tr>
            <td class="bold">Sub Total</td>
            <td class="right">{{ number_format($pesanan->total) }}</td>
        </tr>
        @if ($pesanan->discount_value > 0)
            <tr>
                <td>Discount</td>
                <td class="right">-{{ number_format($pesanan->discount_value) }}</td>
            </tr>
        @endif
        <tr>
            <td class="bold" style="font-size: 14px;">TOTAL</td>
            <td class="right bold" style="font-size: 14px;">
                {{ number_format($pesanan->total - $pesanan->discount_value) }}</td>
        </tr>
        <tr>
            <td>Bayar</td>
            <td class="right">{{ strtoupper($pesanan->metode_pembayaran) }}</td>
        </tr>
    </table>

    @if ($pesanan->metode_pembayaran === 'tunai')
        <div class="separator"></div>
        <table>
            <tr>
                <td>Cash</td>
                <td class="right">{{ number_format($pesanan->uang_tunai) }}</td>
            </tr>
            <tr>
                <td>Kembali</td>
                <td class="right">{{ number_format($pesanan->kembalian) }}</td>
            </tr>
        </table>
    @endif

    <div class="separator"></div>
    <div class="center" style="margin-top: 10px;">*** Terima Kasih ***</div>
    <br>
</body>

</html>
