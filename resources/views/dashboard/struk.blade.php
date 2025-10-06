<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Struk {{ $pesanan->kode }}</title>
    <style>
        body {
            font-family: monospace;
            font-size: 11px;
            line-height: 1.2;
            max-width: 50mm;
            /* <<< penting */
            margin: auto;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .left {
            text-align: left;
        }

        .bold {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 2px 0;
            vertical-align: top;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 4px 0;
        }

        @media print {
            @page {
                size: 50mm auto;
                margin: 2mm;
            }

            body {
                font-family: monospace;
                font-size: 11px;
                line-height: 1.2;
            }

            .center {
                text-align: center;
            }

            .right {
                text-align: right;
            }

            .left {
                text-align: left;
            }

            .bold {
                font-weight: bold;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            td {
                padding: 2px 0;
                vertical-align: top;
            }

            .separator {
                border-top: 1px dashed #000;
                margin: 4px 0;
            }
        }
    </style>
</head>

<body onload="window.print(); window.close();">

    <div class="center">
        <img src="{{ asset('logo.png') }}" style="max-width:40px;"><br>
        <div class="bold">Temuan Space</div>
        <div>{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>
    </div>

    <div class="separator"></div>

    <div>
        Kode : {{ $pesanan->kode }} <br>
        Meja : {{ $pesanan->meja->nama ?? '-' }}
    </div>

    <div class="separator"></div>

    <table>
        @foreach ($pesanan->items as $item)
            <tr>
                <td class="left">{{ $item->menu->nama_menu }}</td>
                <td class="center">{{ $item->qty }}</td>
                <td class="right">{{ number_format($item->subtotal) }}</td>
            </tr>
        @endforeach
    </table>

    <div class="separator"></div>

    <table>
        <tr>
            <td class="bold">TOTAL</td>
            <td class="right bold">{{ number_format($pesanan->total) }}</td>
        </tr>
        <tr>
            <td>Bayar</td>
            <td class="right">{{ $pesanan->metode_pembayaran ?? '-' }}</td>
        </tr>
    </table>

    <div class="separator"></div>

    <div class="center">*** Terima Kasih ***</div>

</body>

</html>
