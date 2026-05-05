@props(['headers' => []])

<div class="table-container">
    <div {{ $attributes->merge(['class' => 'responsive-table bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-3xl shadow-sm']) }}>
        <table class="w-full text-sm text-left border-collapse main-table">
            <thead
                class="text-xs text-neutral-500 uppercase tracking-widest bg-neutral-50 dark:bg-neutral-900/50 border-b border-neutral-200 dark:border-neutral-700">
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col"
                            class="px-6 py-4 font-bold {{ is_array($header) && isset($header['align']) ? 'text-' . $header['align'] : '' }}">
                            {{ is_array($header) ? $header['name'] : $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-700">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>

<style>
    /* Desktop Style */
    .table-container {
        width: 100%;
        overflow: hidden;
    }

    .responsive-table {
        overflow-x: auto;
        max-width: 100%;
        display: block;
    }

    .main-table {
        min-width: 800px;
    }

    /* Mobile Style (Card View) */
    @media (max-width: 768px) {
        .responsive-table {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            overflow: visible !important;
        }

        .main-table {
            min-width: 100% !important;
            /* Reset lebar 800px */
        }

        .responsive-table table,
        .responsive-table thead,
        .responsive-table tbody,
        .responsive-table th,
        .responsive-table td,
        .responsive-table tr {
            display: block !important;
            width: 100% !important;
        }

        .responsive-table thead {
            display: none !important;
        }

        .responsive-table tr {
            background: white !important;
            margin-bottom: 1rem !important;
            border-radius: 1.5rem !important;
            padding: 1rem !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
        }

        .dark .responsive-table tr {
            background: #262626 !important;
            border-color: #404040 !important;
        }

        .responsive-table td {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 0.75rem 0 !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
            text-align: right !important;
            min-height: 2.5rem !important;
            gap: 1rem !important;
            /* Beri jarak antara label dan nilai */
        }

        .dark .responsive-table td {
            border-bottom-color: rgba(255, 255, 255, 0.05) !important;
        }

        .responsive-table td:last-child {
            border-bottom: none !important;
        }

        .responsive-table td::before {
            content: attr(data-label);
            font-weight: 800 !important;
            text-transform: uppercase !important;
            font-size: 0.65rem !important;
            letter-spacing: 0.05em !important;
            color: #71717a !important;
            text-align: left !important;
            flex-shrink: 0 !important;
            /* Label tidak boleh mengecil */
        }
    }
</style>