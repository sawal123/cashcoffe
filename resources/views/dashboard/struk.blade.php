<!DOCTYPE html>
<html>

<head>
    @php
        $receiptStoreName = trim((string) ($webSetting->app_name ?? 'Temuan Space')) ?: 'Temuan Space';
        $receiptLogo = $webSetting->logo ?? 'logo/logo.png';

        // Diskon item/kategori tersimpan per-item dan sudah mengurangi subtotal item,
        // sehingga tidak terlihat di pesanan.discount_value (yang khusus diskon global).
        $itemDiscountTotal = (int) $pesanan->items->sum('discount_value');
        $discountModel = $pesanan->discount;
        $itemDiscountLabel = match ($discountModel?->scope) {
            'category' => 'Disc Kategori',
            'item' => 'Disc Item',
            default => 'Discount',
        };

        if ($itemDiscountTotal > 0 && filled($discountModel?->nama_diskon)) {
            $itemDiscountLabel .= ' (' . $discountModel->nama_diskon . ')';
        }
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $pesanan->kode }} - {{ $receiptStoreName }}</title>
    <style>
        /* Reset dasar untuk printer thermal */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Inter, Arial, sans-serif;
            width: auto;
            min-width: 280px;
            min-height: 100vh;
            padding: 16px;
            margin: 0;
            overflow: auto;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, .08), transparent 32rem),
                #f3f6fb;
        }

        .receipt-page {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 860px;
            margin: 0 auto;
            gap: 16px;
        }

        .print-panel {
            order: 1;
            margin: 0 auto;
            width: 100%;
            max-width: 560px;
            padding: 16px;
            border: 1px solid rgba(148, 163, 184, .32);
            border-radius: 16px;
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 18px 44px rgba(15, 23, 42, .10);
            backdrop-filter: blur(10px);
        }

        .receipt-stage {
            order: 2;
            display: flex;
            justify-content: center;
            padding: 8px 0 6px;
        }

        @media (min-width: 680px) {
            .receipt-page {
                flex-direction: row-reverse;
                align-items: flex-start;
                justify-content: center;
                gap: 24px;
            }

            .print-panel {
                flex: 1;
                min-width: 300px;
                max-width: 400px;
                position: sticky;
                top: 12px;
                margin: 0;
            }

            .receipt-stage {
                flex: 1;
                min-width: 280px;
            }
        }

        .receipt-wrapper {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.2;
            width: 48mm;
            margin: 0 auto;
            padding: 2mm;
            /* Memberikan ruang kosong di bawah agar tidak terpotong saat di-tear */
            margin-bottom: 15mm;
            background: #fff;
            box-shadow: 0 16px 42px rgba(15, 23, 42, .12);
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


        .print-panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .print-panel-kicker {
            margin-bottom: 4px;
            font-size: 11px;
            font-weight: 800;
            color: #2563eb;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .print-panel-title {
            font-size: 20px;
            font-weight: 700;
            line-height: 1.15;
            color: #0f172a;
        }

        .print-panel-meta {
            margin-top: 6px;
            font-size: 12px;
            color: #64748b;
        }

        .print-panel-badge {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 13px;
            font-weight: 900;
            letter-spacing: .04em;
        }

        .print-status-card {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            min-height: 56px;
            margin-bottom: 14px;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
        }

        .print-status-dot {
            flex: 0 0 auto;
            width: 10px;
            height: 10px;
            margin-top: 5px;
            border-radius: 999px;
            background: #94a3b8;
            box-shadow: 0 0 0 4px rgba(148, 163, 184, .18);
        }

        .print-status-card.is-success .print-status-dot {
            background: #16a34a;
            box-shadow: 0 0 0 4px rgba(22, 163, 74, .16);
        }

        .print-status-card.is-error .print-status-dot {
            background: #dc2626;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, .14);
        }

        .print-status-label {
            display: block;
            margin-bottom: 2px;
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .print-status {
            min-height: 18px;
            font-size: 13px;
            line-height: 1.4;
            color: #334155;
        }

        .print-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .print-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            border: 0;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            color: #fff;
            background: #2563eb;
            box-shadow: 0 10px 22px rgba(37, 99, 235, .18);
            transition: transform .16s ease, box-shadow .16s ease, background .16s ease;
        }

        .print-btn.secondary {
            color: #0f172a;
            background: #eef2f7;
            box-shadow: none;
        }

        .print-btn.success {
            background: #16a34a;
            box-shadow: 0 10px 22px rgba(22, 163, 74, .18);
        }

        .print-btn.wide {
            grid-column: 1 / -1;
        }

        .print-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, .16);
        }

        .print-btn:disabled {
            cursor: not-allowed;
            opacity: .55;
        }

        .print-btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .18);
            font-size: 10px;
            font-weight: 900;
        }

        .print-btn.secondary .print-btn-icon {
            background: #dbe3ee;
            color: #334155;
        }

        @media (max-width: 420px) {
            body {
                padding: 10px;
            }

            .print-panel {
                padding: 14px;
                border-radius: 14px;
            }

            .print-panel-title {
                font-size: 18px;
            }

            .print-actions {
                grid-template-columns: 1fr;
            }

            .print-btn.wide {
                grid-column: auto;
            }
        }

        @media print {
            @page {
                size: 58mm auto;
                margin: 0;
            }

            body {
                width: 48mm;
                margin: 0;
                padding: 0;
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }

            /* Mencegah pemotongan konten dan pemisahan halaman */
            html,
            body {
                height: auto !important;
                overflow: visible !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .receipt-page,
            .receipt-stage {
                display: block;
                max-width: none;
                margin: 0;
                padding: 0;
            }

            .receipt-wrapper {
                width: 48mm;
                margin: 0;
                padding: 2mm;
                background: #fff;
                box-shadow: none;
            }
        }
    </style>
</head>

<body class="bg-white">
    <main class="receipt-page">
        <section class="print-panel no-print" aria-label="Kontrol cetak struk">
            <div class="print-panel-header">
                <div>
                    <div class="print-panel-kicker">Printer</div>
                    <div class="print-panel-title">Cetak Struk</div>
                    <div class="print-panel-meta">Invoice {{ $pesanan->kode }} · {{ date('d/m/Y H:i') }}</div>
                </div>
                <div class="print-panel-badge">BT</div>
            </div>

            <div class="print-status-card" id="printStatusCard">
                <span class="print-status-dot" aria-hidden="true"></span>
                <div>
                    <span class="print-status-label">Status</span>
                    <div class="print-status" id="printStatus">Siapkan printer thermal Bluetooth dalam mode pairing.
                    </div>
                </div>
            </div>

            <div class="print-actions">
                <button type="button" class="print-btn" id="pairBluetoothBtn">
                    <span class="print-btn-icon" aria-hidden="true">BT</span>
                    Pair
                </button>
                <button type="button" class="print-btn success" id="printBluetoothBtn">
                    <span class="print-btn-icon" aria-hidden="true">PR</span>
                    Cetak Bluetooth
                </button>
                <button type="button" class="print-btn secondary" id="printBrowserBtn">
                    <span class="print-btn-icon" aria-hidden="true">WB</span>
                    Cetak Browser
                </button>
                <button type="button" class="print-btn secondary" id="closePrintBtn" onclick="if (window.parent && window.parent !== window) { window.parent.dispatchEvent(new CustomEvent('close-receipt-modal')); } else { window.history.back(); }">
                    <span class="print-btn-icon" aria-hidden="true">✕</span>
                    Tutup
                </button>
            </div>
        </section>

        <div class="receipt-stage">
            <div class="receipt-wrapper">
                <div class="center">
                    <img id="logo" src="{{ asset($receiptLogo) }}" width="60"
                        style="display: block; margin: 0 auto 5px;"><br>
                    <div class="bold">{{ $receiptStoreName }}</div>
                    <div style="font-size: 10px;">Jl. Laksana No.98, Kotamatsum III, Kec. Medan</div>
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
                    <tr>
                        <td>Type</td>
                        <td>: {{ $pesanan->salesChannel->nama_channel ?? 'Dine In' }}</td>
                    </tr>
                    <tr>
                        <td>Ket</td>
                        <td>: {{ $pesanan->status === 'selesai' ? 'Selesai' : ucwords($pesanan->status) }}</td>
                    </tr>
                </table>

                <div class="separator"></div>

                <table>
                    @foreach ($pesanan->items as $item)
                        <tr>
                            <td class="col-nama">{{ $item->menu->nama_menu }}</td>
                            <td class="col-qty">{{ $item->qty }}</td>
                            <td class="col-harga">{{ number_format($item->subtotal) }}</td>
                        </tr>
                        @if ($item->discount_value > 0)
                            <tr>
                                <td colspan="3" style="padding-left: 5px; font-size: 10px; color: #555;">
                                    Harga <s>{{ number_format($item->subtotal + $item->discount_value) }}</s>
                                    &middot; Disc -{{ number_format($item->discount_value) }}
                                </td>
                            </tr>
                        @endif
                        @if ($item->variants->count() > 0)
                            <tr>
                                <td colspan="3" style="padding-left: 5px; font-size: 10px; color: #555;">
                                    @foreach ($item->variants as $variant)
                                        <div>- {{ $variant->nama_opsi }}
                                            @if ($variant->extra_price > 0)
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
                        <td class="right">{{ number_format($pesanan->total + $itemDiscountTotal) }}</td>
                    </tr>
                    @if ($itemDiscountTotal > 0)
                        <tr>
                            <td>{{ $itemDiscountLabel }}</td>
                            <td class="right">-{{ number_format($itemDiscountTotal) }}</td>
                        </tr>
                    @endif
                    @if ($pesanan->discount_value > 0)
                        <tr>
                            <td>Discount</td>
                            <td class="right">-{{ number_format($pesanan->discount_value) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="bold" style="font-size: 14px;">TOTAL</td>
                        <td class="right bold" style="font-size: 14px;">
                            {{ number_format($pesanan->total - $pesanan->discount_value) }}
                        </td>
                    </tr>
                    <tr>
                        <td>Bayar</td>
                        <td class="right">
                            {{ $pesanan->paymentMethod ? strtoupper($pesanan->paymentMethod->nama_metode) : 'BELUM BAYAR' }}
                        </td>
                    </tr>
                </table>

                @if ($pesanan->paymentMethod && $pesanan->paymentMethod->kode_metode === 'tunai')
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
            </div>
        </div>
    </main>

    @php
        $receiptItems = $pesanan->items
            ->map(function ($item) {
                return [
                    'name' => $item->menu->nama_menu,
                    'qty' => (int) $item->qty,
                    'subtotal' => (int) $item->subtotal,
                    'discountValue' => (int) $item->discount_value,
                    'variants' => $item->variants
                        ->map(function ($variant) {
                            return [
                                'name' => $variant->nama_opsi,
                                'extraPrice' => (int) $variant->extra_price,
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        $receiptData = [
            'storeName' => $receiptStoreName,
            'address' => 'Jl. Tenis No.30, Ps. Merah Bar., Medan',
            'date' => now()->format('d/m/Y H:i'),
            'invoice' => $pesanan->kode,
            'cashier' => $pesanan->user->name,
            'customer' => $pesanan->nama ?? '-',
            'orderType' => $pesanan->salesChannel->nama_channel ?? 'Dine In',
            'status' => $pesanan->status === 'selesai' ? 'Selesai' : ucwords($pesanan->status),
            'items' => $receiptItems,
            'subtotal' => (int) $pesanan->total + $itemDiscountTotal,
            'itemDiscount' => $itemDiscountTotal,
            'itemDiscountLabel' => $itemDiscountLabel,
            'discount' => (int) $pesanan->discount_value,
            'total' => (int) ($pesanan->total - $pesanan->discount_value),
            'paymentMethod' => $pesanan->paymentMethod
                ? strtoupper($pesanan->paymentMethod->nama_metode)
                : 'BELUM BAYAR',
            'isCash' => $pesanan->paymentMethod && $pesanan->paymentMethod->kode_metode === 'tunai',
            'cash' => (int) $pesanan->uang_tunai,
            'change' => (int) $pesanan->kembalian,
        ];
    @endphp

    <script>
        const receiptData = {{ Illuminate\Support\Js::from($receiptData) }};

        const printerProfiles = [{
                service: '0000ffe0-0000-1000-8000-00805f9b34fb',
                characteristic: '0000ffe1-0000-1000-8000-00805f9b34fb',
            },
            {
                service: '0000fff0-0000-1000-8000-00805f9b34fb',
                characteristic: '0000fff2-0000-1000-8000-00805f9b34fb',
            },
            {
                service: '000018f0-0000-1000-8000-00805f9b34fb',
                characteristic: '00002af1-0000-1000-8000-00805f9b34fb',
            },
            {
                service: 'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
                characteristic: 'bef8d6c9-9c21-4c9e-b632-bd58c1009f9f',
            },
        ];

        const statusEl = document.getElementById('printStatus');
        const statusCard = document.getElementById('printStatusCard');
        const pairBluetoothBtn = document.getElementById('pairBluetoothBtn');
        const printBluetoothBtn = document.getElementById('printBluetoothBtn');
        const printBrowserBtn = document.getElementById('printBrowserBtn');
        let bluetoothDevice = null;
        let bluetoothCharacteristic = null;

        function setStatus(message, isError = false) {
            statusEl.textContent = message;
            statusEl.style.color = isError ? '#b91c1c' : '#475569';
            statusCard.classList.toggle('is-error', isError);
            statusCard.classList.toggle('is-success', !isError && /terhubung|berhasil|siap/i.test(message));
        }

        function normalizeText(value) {
            return String(value ?? '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^\x20-\x7E\n]/g, '');
        }

        function money(value) {
            return new Intl.NumberFormat('id-ID').format(Number(value || 0));
        }

        function center(text, width = 32) {
            text = normalizeText(text).slice(0, width);
            const left = Math.floor((width - text.length) / 2);
            return `${' '.repeat(Math.max(left, 0))}${text}`;
        }

        function line(left, right = '', width = 32) {
            left = normalizeText(left);
            right = normalizeText(right);
            const gap = width - left.length - right.length;

            if (gap >= 1) {
                return `${left}${' '.repeat(gap)}${right}`;
            }

            return `${left.slice(0, Math.max(width - right.length - 1, 1))} ${right}`.slice(0, width);
        }

        function splitText(text, width) {
            const words = normalizeText(text).split(/\s+/).filter(Boolean);
            const rows = [];
            let current = '';

            words.forEach((word) => {
                if (!current) {
                    current = word.slice(0, width);
                    return;
                }

                if (`${current} ${word}`.length <= width) {
                    current += ` ${word}`;
                    return;
                }

                rows.push(current);
                current = word.slice(0, width);
            });

            if (current) {
                rows.push(current);
            }

            return rows.length ? rows : [''];
        }

        function itemLines(item) {
            const nameWidth = 17;
            const qty = String(item.qty || 0).padStart(3, ' ');
            const price = money(item.subtotal).padStart(10, ' ');
            const names = splitText(item.name, nameWidth);
            const rows = [`${names[0].padEnd(nameWidth, ' ')}${qty}${price}`];

            names.slice(1).forEach((name) => rows.push(`  ${name}`));

            const itemDiscount = Number(item.discountValue || 0);

            if (itemDiscount > 0) {
                const gross = money(item.subtotal + itemDiscount);
                rows.push(`  Harga ${gross} -> ${money(item.subtotal)}`);
            }

            item.variants.forEach((variant) => {
                const extra = variant.extraPrice > 0 ? ` +${money(variant.extraPrice)}` : '';
                splitText(`- ${variant.name}${extra}`, 30).forEach((variantLine) => rows.push(`  ${variantLine}`));
            });

            return rows;
        }

        function mergeBytes(parts) {
            const totalLength = parts.reduce((total, part) => total + part.length, 0);
            const bytes = new Uint8Array(totalLength);
            let offset = 0;

            parts.forEach((part) => {
                bytes.set(part, offset);
                offset += part.length;
            });

            return bytes;
        }

        function commandBytes(...bytes) {
            return Uint8Array.from(bytes);
        }

        async function logoRasterBytes() {
            const logo = document.getElementById('logo');

            if (!logo) {
                return null;
            }

            try {
                if (!logo.complete) {
                    await new Promise((resolve) => {
                        logo.onload = resolve;
                        logo.onerror = resolve;
                    });
                }

                if (logo.decode) {
                    await logo.decode().catch(() => null);
                }

                const naturalWidth = logo.naturalWidth || logo.width;
                const naturalHeight = logo.naturalHeight || logo.height;

                if (!naturalWidth || !naturalHeight) {
                    return null;
                }

                const targetWidth = 160;
                const targetHeight = Math.max(1, Math.round(targetWidth * naturalHeight / naturalWidth));
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');

                canvas.width = targetWidth;
                canvas.height = targetHeight;
                context.fillStyle = '#fff';
                context.fillRect(0, 0, targetWidth, targetHeight);
                context.imageSmoothingEnabled = true;
                context.drawImage(logo, 0, 0, targetWidth, targetHeight);

                const image = context.getImageData(0, 0, targetWidth, targetHeight);
                const widthBytes = Math.ceil(targetWidth / 8);
                const raster = new Uint8Array(widthBytes * targetHeight);

                for (let y = 0; y < targetHeight; y++) {
                    for (let x = 0; x < targetWidth; x++) {
                        const pixelIndex = (y * targetWidth + x) * 4;
                        const alpha = image.data[pixelIndex + 3];
                        const red = image.data[pixelIndex];
                        const green = image.data[pixelIndex + 1];
                        const blue = image.data[pixelIndex + 2];
                        const luminance = (red * 0.299) + (green * 0.587) + (blue * 0.114);

                        if (alpha > 32 && luminance < 180) {
                            raster[(y * widthBytes) + (x >> 3)] |= 0x80 >> (x & 7);
                        }
                    }
                }

                return mergeBytes([
                    commandBytes(
                        0x1D, 0x76, 0x30, 0x00,
                        widthBytes & 0xFF,
                        (widthBytes >> 8) & 0xFF,
                        targetHeight & 0xFF,
                        (targetHeight >> 8) & 0xFF,
                    ),
                    raster,
                    commandBytes(0x0A),
                ]);
            } catch (error) {
                return null;
            }
        }

        async function buildEscPosBytes() {
            const encoder = new TextEncoder();
            const divider = '-'.repeat(32);
            const parts = [];
            const appendCommand = (...bytes) => parts.push(commandBytes(...bytes));
            const appendText = (text, options = {}) => {
                if (options.align === 'center') {
                    appendCommand(0x1B, 0x61, 0x01);
                } else if (options.align === 'left') {
                    appendCommand(0x1B, 0x61, 0x00);
                }

                if (options.bold) {
                    appendCommand(0x1B, 0x45, 0x01, 0x1B, 0x47, 0x01, 0x1B, 0x21, 0x08);
                }

                parts.push(encoder.encode(`${normalizeText(text)}\n`));

                if (options.bold) {
                    appendCommand(0x1B, 0x45, 0x00, 0x1B, 0x47, 0x00, 0x1B, 0x21, 0x00);
                }
            };

            appendCommand(0x1B, 0x40); // init
            appendCommand(0x1B, 0x74, 0x00); // default code page
            appendCommand(0x1B, 0x61, 0x01); // center

            const logoBytes = await logoRasterBytes();

            if (logoBytes) {
                parts.push(logoBytes);
            }

            appendText(receiptData.storeName, {
                bold: true,
                align: 'center'
            });
            splitText(receiptData.address, 32).forEach((row) => appendText(row, {
                align: 'center'
            }));
            appendText(receiptData.date, {
                align: 'center'
            });

            appendText(divider, {
                align: 'left'
            });
            appendText(line('Inv', `: ${receiptData.invoice}`));
            appendText(line('Kasir', `: ${receiptData.cashier}`));
            appendText(line('Cust', `: ${receiptData.customer}`));
            appendText(line('Type', `: ${receiptData.orderType}`));
            appendText(line('Ket', `: Pesanan ${receiptData.status}`));
            appendText(divider);

            receiptData.items.forEach((item) => {
                itemLines(item).forEach((row) => appendText(row));
            });

            appendText(divider);
            appendText(line('Sub Total', money(receiptData.subtotal)));

            if (receiptData.itemDiscount > 0) {
                appendText(line(receiptData.itemDiscountLabel, `-${money(receiptData.itemDiscount)}`));
            }

            if (receiptData.discount > 0) {
                appendText(line('Discount', `-${money(receiptData.discount)}`));
            }

            appendText(line('TOTAL', money(receiptData.total)), {
                bold: true
            });
            appendText(line('Bayar', receiptData.paymentMethod));

            if (receiptData.isCash) {
                appendText(divider);
                appendText(line('Cash', money(receiptData.cash)));
                appendText(line('Kembali', money(receiptData.change)));
            }

            appendText(divider);
            appendText('*** Terima Kasih ***', {
                align: 'center'
            });
            appendCommand(0x1B, 0x70, 0x00, 0x19, 0xFA); // open cash drawer
            appendCommand(0x0A, 0x0A, 0x0A);
            appendCommand(0x1D, 0x56, 0x42, 0x00); // partial cut, ignored by printers without cutter

            return mergeBytes(parts);
        }

        async function findWritableCharacteristic(server) {
            for (const profile of printerProfiles) {
                try {
                    const service = await server.getPrimaryService(profile.service);
                    const characteristic = await service.getCharacteristic(profile.characteristic);

                    if (characteristic.properties.write || characteristic.properties.writeWithoutResponse) {
                        return characteristic;
                    }
                } catch (error) {
                    // Coba profil printer berikutnya.
                }
            }

            for (const profile of printerProfiles) {
                try {
                    const service = await server.getPrimaryService(profile.service);
                    const characteristics = await service.getCharacteristics();
                    const writable = characteristics.find((characteristic) => {
                        return characteristic.properties.write || characteristic.properties
                            .writeWithoutResponse;
                    });

                    if (writable) {
                        return writable;
                    }
                } catch (error) {
                    // Coba profil printer berikutnya.
                }
            }

            throw new Error('Service Bluetooth printer belum dikenali.');
        }

        function onGattDisconnected() {
            bluetoothCharacteristic = null;
            if (window.parent && window.parent !== window) {
                window.parent.__cashcoffe_bluetooth_characteristic = null;
            }
            const name = bluetoothDevice?.name || localStorage.getItem('cashcoffe_printer_name') || 'Printer Bluetooth';
            setStatus(`Koneksi terputus: ${name}. Klik Cetak Bluetooth untuk menghubungkan kembali.`);
        }

        async function connectBluetoothPrinter(forcePair = false) {
            if (!navigator.bluetooth) {
                throw new Error(
                    'Browser belum mendukung Web Bluetooth. Gunakan Chrome/Edge Android lewat HTTPS atau localhost.'
                );
            }

            if (!window.isSecureContext) {
                throw new Error('Web Bluetooth wajib memakai HTTPS atau localhost.');
            }

            // 1. Coba ambil instance perangkat yang sudah tersimpan di memori (window / window.parent / window.top)
            if (!forcePair && !bluetoothDevice) {
                if (window.parent && window.parent !== window && window.parent.__cashcoffe_bluetooth_device) {
                    bluetoothDevice = window.parent.__cashcoffe_bluetooth_device;
                } else if (window.top && window.top !== window && window.top.__cashcoffe_bluetooth_device) {
                    bluetoothDevice = window.top.__cashcoffe_bluetooth_device;
                } else if (window.__cashcoffe_bluetooth_device) {
                    bluetoothDevice = window.__cashcoffe_bluetooth_device;
                }
            }

            // 2. Jika belum ada di memori, cari dari daftar perangkat yang telah diizinkan browser (getDevices)
            if (!forcePair && !bluetoothDevice) {
                const bt = (window.top && window.top.navigator && window.top.navigator.bluetooth) ||
                           (window.parent && window.parent.navigator && window.parent.navigator.bluetooth) ||
                           navigator.bluetooth;

                if (bt && bt.getDevices) {
                    try {
                        const devices = await bt.getDevices();
                        const savedPrinterId = localStorage.getItem('cashcoffe_printer_id');
                        const savedPrinterName = localStorage.getItem('cashcoffe_printer_name');

                        bluetoothDevice = devices.find((device) => device.id === savedPrinterId) ||
                                          devices.find((device) => device.name && device.name === savedPrinterName) ||
                                          (devices.length === 1 ? devices[0] : null);
                    } catch (e) {
                        console.warn('Gagal membaca getDevices():', e);
                    }
                }
            }

            // 3. Hanya tampilkan popup dialog sandingkan (requestDevice) jika forcePair ATAU memang belum pernah disandingkan sama sekali
            if (!bluetoothDevice || forcePair) {
                bluetoothDevice = await navigator.bluetooth.requestDevice({
                    acceptAllDevices: true,
                    optionalServices: printerProfiles.map((profile) => profile.service),
                });

                if (bluetoothDevice.id) {
                    localStorage.setItem('cashcoffe_printer_id', bluetoothDevice.id);
                }
                localStorage.setItem('cashcoffe_printer_name', bluetoothDevice.name || 'Printer Bluetooth');
            }

            // Simpan referensi ke window global & parent agar tetap aktif antar pencetakan struk
            window.__cashcoffe_bluetooth_device = bluetoothDevice;
            if (window.parent && window.parent !== window) {
                window.parent.__cashcoffe_bluetooth_device = bluetoothDevice;
            }
            if (window.top && window.top !== window) {
                window.top.__cashcoffe_bluetooth_device = bluetoothDevice;
            }

            if (!bluetoothDevice.gatt) {
                throw new Error('Perangkat ini tidak menyediakan koneksi GATT Bluetooth.');
            }

            bluetoothDevice.removeEventListener('gattserverdisconnected', onGattDisconnected);
            bluetoothDevice.addEventListener('gattserverdisconnected', onGattDisconnected);

            // 4. Hubungkan ke GATT jika belum terhubung
            if (!bluetoothDevice.gatt.connected) {
                setStatus('Menghubungkan ke printer...');
                const server = await bluetoothDevice.gatt.connect();
                bluetoothCharacteristic = await findWritableCharacteristic(server);
            } else {
                if (!bluetoothCharacteristic && window.parent && window.parent.__cashcoffe_bluetooth_characteristic) {
                    bluetoothCharacteristic = window.parent.__cashcoffe_bluetooth_characteristic;
                }
                if (!bluetoothCharacteristic) {
                    bluetoothCharacteristic = await findWritableCharacteristic(bluetoothDevice.gatt);
                }
            }

            if (window.parent && window.parent !== window) {
                window.parent.__cashcoffe_bluetooth_characteristic = bluetoothCharacteristic;
            }

            setStatus(`Terhubung: ${bluetoothDevice.name || 'Printer Bluetooth'}`);
            return bluetoothCharacteristic;
        }

        async function writePrinterBytes(characteristic, bytes) {
            const chunkSize = 20;

            for (let offset = 0; offset < bytes.length; offset += chunkSize) {
                const chunk = bytes.slice(offset, offset + chunkSize);

                if (characteristic.properties.writeWithoutResponse && characteristic.writeValueWithoutResponse) {
                    await characteristic.writeValueWithoutResponse(chunk);
                } else if (characteristic.writeValueWithResponse) {
                    await characteristic.writeValueWithResponse(chunk);
                } else {
                    await characteristic.writeValue(chunk);
                }

                await new Promise((resolve) => setTimeout(resolve, 20));
            }
        }

        async function pairBluetoothPrinter() {
            try {
                pairBluetoothBtn.disabled = true;
                setStatus('Mencari printer Bluetooth...');
                await connectBluetoothPrinter(true);
            } catch (error) {
                setStatus(error.message || 'Gagal pairing printer Bluetooth.', true);
            } finally {
                pairBluetoothBtn.disabled = false;
            }
        }

        async function printBluetoothReceipt() {
            try {
                printBluetoothBtn.disabled = true;
                const characteristic = await connectBluetoothPrinter(false);

                setStatus('Mengirim struk ke printer...');
                await writePrinterBytes(characteristic, await buildEscPosBytes());
                setStatus('Struk berhasil dikirim ke printer.');
            } catch (error) {
                setStatus(error.message || 'Gagal mencetak lewat Bluetooth.', true);
            } finally {
                printBluetoothBtn.disabled = false;
            }
        }

        function printWithBrowser() {
            setTimeout(() => {
                window.print();
            }, 300);
        }

        pairBluetoothBtn.addEventListener('click', pairBluetoothPrinter);
        printBluetoothBtn.addEventListener('click', printBluetoothReceipt);
        printBrowserBtn.addEventListener('click', printWithBrowser);

        window.onload = async function() {
            // Cek apakah ada printer yang masih aktif di memori atau tersimpan di browser
            let existingDevice = (window.parent && window.parent.__cashcoffe_bluetooth_device) ||
                                 (window.top && window.top.__cashcoffe_bluetooth_device) ||
                                 bluetoothDevice;

            if (!existingDevice) {
                const bt = (window.top && window.top.navigator && window.top.navigator.bluetooth) ||
                           (window.parent && window.parent.navigator && window.parent.navigator.bluetooth) ||
                           navigator.bluetooth;

                if (bt && bt.getDevices) {
                    try {
                        const devices = await bt.getDevices();
                        const savedPrinterId = localStorage.getItem('cashcoffe_printer_id');
                        const savedPrinterName = localStorage.getItem('cashcoffe_printer_name');

                        existingDevice = devices.find((device) => device.id === savedPrinterId) ||
                                         devices.find((device) => device.name && device.name === savedPrinterName) ||
                                         (devices.length === 1 ? devices[0] : null);

                        if (existingDevice) {
                            bluetoothDevice = existingDevice;
                            if (window.parent && window.parent !== window) {
                                window.parent.__cashcoffe_bluetooth_device = existingDevice;
                            }
                        }
                    } catch (e) {
                        console.warn(e);
                    }
                }
            } else {
                bluetoothDevice = existingDevice;
            }

            const displayName = bluetoothDevice?.name || localStorage.getItem('cashcoffe_printer_name');
            if (displayName) {
                if (bluetoothDevice && bluetoothDevice.gatt && bluetoothDevice.gatt.connected) {
                    setStatus(`Terhubung: ${displayName} (Siap Cetak)`);
                } else {
                    setStatus(`Printer tersanding: ${displayName}. Klik Cetak Bluetooth untuk langsung mencetak.`);
                }
            }

            const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent) || window.matchMedia(
                '(pointer: coarse)').matches;
            const params = new URLSearchParams(window.location.search);
            const shouldAutoPrint = params.get('auto') === '1' && !isMobile;

            if (!shouldAutoPrint) {
                return;
            }

            const logo = document.getElementById('logo');
            const startPrint = () => {
                // Berikan jeda sedikit lagi untuk memastikan rendering font selesai
                printWithBrowser();
            };

            if (logo.complete) {
                startPrint();
            } else {
                logo.onload = startPrint;
                // Fallback jika logo error atau terlalu lama
                setTimeout(startPrint, 2000);
            }
        };

        window.onafterprint = function() {};
    </script>
</body>

</html>
