<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 24px;
        }

        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            line-height: 1.35;
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #111827;
            margin-bottom: 12px;
            padding-bottom: 8px;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 4px;
        }

        .meta {
            color: #6b7280;
            font-size: 11px;
        }

        .menu-block {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 8px;
            padding: 8px 10px;
            page-break-inside: avoid;
        }

        .menu-title {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .category {
            color: #6b7280;
            font-size: 11px;
            margin-bottom: 6px;
        }

        ol {
            margin: 0;
            padding-left: 20px;
        }

        li {
            margin-bottom: 2px;
        }

        .empty {
            color: #9ca3af;
            font-style: italic;
        }

        .footer {
            color: #9ca3af;
            font-size: 10px;
            margin-top: 20px;
            text-align: right;
        }
    </style>
</head>
<body>
    @php
        $formatQty = function ($value) {
            return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
        };
    @endphp

    <div class="header">
        <h1>{{ $title }}</h1>
        <div class="meta">Dicetak: {{ $printedAt }}</div>
    </div>

    @forelse ($menus as $menu)
        <div class="menu-block">
            <div class="menu-title">{{ $menu->nama_menu }}</div>
            <div class="category">Kategori: {{ $menu->category->nama ?? '-' }}</div>

            @if ($menu->ingredients->isNotEmpty())
                <ol>
                    @foreach ($menu->ingredients as $ingredient)
                        <li>
                            {{ $ingredient->nama_bahan }}
                            {{ $formatQty($ingredient->pivot->qty) }} {{ $ingredient->satuan->nama_satuan ?? '' }}
                        </li>
                    @endforeach
                </ol>
            @else
                <div class="empty">Belum ada komposisi.</div>
            @endif
        </div>
    @empty
        <div class="empty">Belum ada data menu.</div>
    @endforelse

    <div class="footer">Cash Coffee - Menu & Komposisi</div>
</body>
</html>
