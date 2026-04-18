@props(['headers' => []])

<div
    {{ $attributes->merge(['class' => 'bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-3xl overflow-hidden shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-neutral-50 dark:bg-neutral-900/50 border-b border-neutral-200 dark:border-neutral-700">
                    @foreach ($headers as $header)
                        <th
                            class="px-6 py-5 text-xs font-bold text-neutral-500 uppercase tracking-widest {{ is_array($header) && isset($header['align']) ? 'text-' . $header['align'] : '' }}">
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
