<div>
    <div class="table-responsive">
        <table class="table basic-border-table mb-2">
            <thead>
                <tr>
                    <th class="border-r border-neutral-200 dark:border-neutral-600">Nama Bahan</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600">Stok</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600">Satuan</th>
                </tr>
            </thead>

            <tbody>
                @foreach($items as $i)
                <tr>
                    <td class="border-r border-neutral-200 dark:border-neutral-600">{{ $i->nama_bahan }}</td>
                    <td class="border-r border-neutral-200 dark:border-neutral-600">{{ $i->stok }}</td>
                    <td class="border-r border-neutral-200 dark:border-neutral-600">{{ $i->satuan }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</div>
