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
