<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title }}</h6>
        <x-breadcrumb :title="$title" />
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <section class="xl:col-span-2 bg-white dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600 rounded-lg">
            <div class="p-5 border-b border-neutral-200 dark:border-neutral-600">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-1">Printer Bluetooth</h2>
                        <p class="text-sm text-neutral-500 dark:text-neutral-300" data-printer-support-text>Memeriksa dukungan browser...</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-primary-600/10 text-primary-600 flex items-center justify-center">
                        <iconify-icon icon="solar:printer-2-bold" class="text-2xl"></iconify-icon>
                    </div>
                </div>
            </div>

            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div class="border border-neutral-200 dark:border-neutral-600 rounded-lg p-4">
                        <p class="text-xs uppercase text-neutral-400 font-semibold mb-2">Status</p>
                        <div class="flex items-center gap-2">
                            <span data-printer-status-dot class="w-3 h-3 rounded-full bg-neutral-300"></span>
                            <p data-printer-status class="font-semibold text-neutral-800 dark:text-white">Belum terhubung</p>
                        </div>
                    </div>

                    <div class="border border-neutral-200 dark:border-neutral-600 rounded-lg p-4">
                        <p class="text-xs uppercase text-neutral-400 font-semibold mb-2">Printer</p>
                        <p data-printer-name class="font-semibold text-neutral-800 dark:text-white">Belum dipilih</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="button" data-pair-printer-btn class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-700 text-sm font-semibold transition">
                        <iconify-icon icon="solar:bluetooth-circle-bold" class="text-lg"></iconify-icon>
                        Pair Printer
                    </button>

                    <button type="button" data-test-printer-btn class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-success-600 text-white hover:bg-success-700 text-sm font-semibold transition">
                        <iconify-icon icon="solar:printer-minimalistic-bold" class="text-lg"></iconify-icon>
                        Test Print
                    </button>

                    <button type="button" data-disconnect-printer-btn class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-neutral-300 dark:border-neutral-500 text-neutral-700 dark:text-neutral-100 hover:bg-neutral-100 dark:hover:bg-neutral-600 text-sm font-semibold transition">
                        <iconify-icon icon="solar:plug-circle-bold" class="text-lg"></iconify-icon>
                        Disconnect
                    </button>

                    <button type="button" data-reset-printer-btn class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-danger-300 text-danger-600 hover:bg-danger-50 text-sm font-semibold transition">
                        <iconify-icon icon="solar:restart-circle-bold" class="text-lg"></iconify-icon>
                        Reset
                    </button>
                </div>

                <div data-printer-message class="mt-4 text-sm text-neutral-500 dark:text-neutral-300 min-h-5"></div>
            </div>
        </section>

        <aside class="bg-white dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600 rounded-lg">
            <div class="p-5 border-b border-neutral-200 dark:border-neutral-600">
                <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Koneksi</h2>
            </div>
            <div class="p-5 space-y-4 text-sm">
                <div class="flex items-start gap-3">
                    <span class="mt-1 w-2 h-2 rounded-full bg-primary-600 flex-shrink-0"></span>
                    <p class="text-neutral-600 dark:text-neutral-300">Gunakan Chrome atau Edge Android.</p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="mt-1 w-2 h-2 rounded-full bg-primary-600 flex-shrink-0"></span>
                    <p class="text-neutral-600 dark:text-neutral-300">Halaman harus dibuka lewat HTTPS atau localhost.</p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="mt-1 w-2 h-2 rounded-full bg-primary-600 flex-shrink-0"></span>
                    <p class="text-neutral-600 dark:text-neutral-300">Printer yang dipilih akan dipakai juga di halaman struk.</p>
                </div>
            </div>
        </aside>
    </div>
</div>

@script
<script>
    (() => {
        const root = $wire.$el;
        const storageKey = 'cashcoffe_printer_name';
        const printerProfiles = [
            {
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

        const supportText = root.querySelector('[data-printer-support-text]');
        const statusText = root.querySelector('[data-printer-status]');
        const statusDot = root.querySelector('[data-printer-status-dot]');
        const printerName = root.querySelector('[data-printer-name]');
        const message = root.querySelector('[data-printer-message]');
        const pairBtn = root.querySelector('[data-pair-printer-btn]');
        const testBtn = root.querySelector('[data-test-printer-btn]');
        const disconnectBtn = root.querySelector('[data-disconnect-printer-btn]');
        const resetBtn = root.querySelector('[data-reset-printer-btn]');

        let bluetoothDevice = null;
        let bluetoothCharacteristic = null;

        function setMessage(text, isError = false) {
            message.textContent = text;
            message.classList.toggle('text-danger-600', isError);
            message.classList.toggle('dark:text-danger-400', isError);
            message.classList.toggle('text-neutral-500', !isError);
            message.classList.toggle('dark:text-neutral-300', !isError);
        }

        function setConnectionState(state, name = null) {
            statusText.textContent = state;
            printerName.textContent = name || localStorage.getItem(storageKey) || 'Belum dipilih';
            statusDot.className = 'w-3 h-3 rounded-full ' + (state === 'Terhubung' ? 'bg-success-600' : 'bg-neutral-300');
        }

        function setButtons(disabled) {
            [pairBtn, testBtn, disconnectBtn, resetBtn].forEach((button) => {
                button.disabled = disabled;
                button.classList.toggle('opacity-60', disabled);
                button.classList.toggle('cursor-not-allowed', disabled);
            });
        }

        function disableBluetoothActions() {
            pairBtn.disabled = true;
            testBtn.disabled = true;
            pairBtn.classList.add('opacity-60', 'cursor-not-allowed');
            testBtn.classList.add('opacity-60', 'cursor-not-allowed');
        }

        function checkSupport() {
            const savedPrinter = localStorage.getItem(storageKey);
            printerName.textContent = savedPrinter || 'Belum dipilih';

            if (!navigator.bluetooth) {
                supportText.textContent = 'Browser tidak mendukung Web Bluetooth.';
                setMessage('Gunakan Chrome/Edge Android untuk pairing printer Bluetooth.', true);
                disableBluetoothActions();
                return;
            }

            if (!window.isSecureContext) {
                supportText.textContent = 'Koneksi belum aman.';
                setMessage('Web Bluetooth hanya aktif di HTTPS atau localhost.', true);
                disableBluetoothActions();
                return;
            }

            supportText.textContent = 'Browser siap untuk pairing printer.';
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
                    // Coba profil berikutnya.
                }
            }

            for (const profile of printerProfiles) {
                try {
                    const service = await server.getPrimaryService(profile.service);
                    const characteristics = await service.getCharacteristics();
                    const writable = characteristics.find((characteristic) => {
                        return characteristic.properties.write || characteristic.properties.writeWithoutResponse;
                    });

                    if (writable) {
                        return writable;
                    }
                } catch (error) {
                    // Coba profil berikutnya.
                }
            }

            throw new Error('Service Bluetooth printer belum dikenali.');
        }

        async function pickDevice(forcePicker = false) {
            if (!forcePicker && navigator.bluetooth.getDevices) {
                const devices = await navigator.bluetooth.getDevices();
                const savedPrinterName = localStorage.getItem(storageKey);
                bluetoothDevice = devices.find((device) => device.name === savedPrinterName) || devices[0] || null;
            }

            if (!bluetoothDevice || forcePicker) {
                bluetoothDevice = await navigator.bluetooth.requestDevice({
                    acceptAllDevices: true,
                    optionalServices: printerProfiles.map((profile) => profile.service),
                });
            }

            if (!bluetoothDevice.gatt) {
                throw new Error('Perangkat ini tidak menyediakan koneksi GATT Bluetooth.');
            }

            localStorage.setItem(storageKey, bluetoothDevice.name || 'Printer Bluetooth');
            setConnectionState('Belum terhubung', bluetoothDevice.name || 'Printer Bluetooth');
        }

        async function connectPrinter(forcePicker = false) {
            await pickDevice(forcePicker);

            if (!bluetoothDevice.gatt.connected) {
                setMessage('Menghubungkan ke printer...');
                const server = await bluetoothDevice.gatt.connect();
                bluetoothCharacteristic = await findWritableCharacteristic(server);
            }

            setConnectionState('Terhubung', bluetoothDevice.name || 'Printer Bluetooth');
            setMessage('Printer siap digunakan.');
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

        function buildTestPrintBytes() {
            const encoder = new TextEncoder();
            const now = new Intl.DateTimeFormat('id-ID', {
                dateStyle: 'short',
                timeStyle: 'short',
            }).format(new Date());
            const text = [
                'CASHCOFFE',
                'TEST PRINTER',
                'Bluetooth pairing OK',
                now,
                '',
                '',
                '',
            ].join('\n');
            const commands = [0x1B, 0x40, 0x1B, 0x61, 0x01];
            const feedAndCut = [0x0A, 0x0A, 0x1D, 0x56, 0x42, 0x00];
            const textBytes = encoder.encode(text);
            const bytes = new Uint8Array(commands.length + textBytes.length + feedAndCut.length);

            bytes.set(commands, 0);
            bytes.set(textBytes, commands.length);
            bytes.set(feedAndCut, commands.length + textBytes.length);

            return bytes;
        }

        pairBtn.addEventListener('click', async () => {
            try {
                setButtons(true);
                setMessage('Mencari printer Bluetooth...');
                await connectPrinter(true);
            } catch (error) {
                setMessage(error.message || 'Gagal pairing printer.', true);
            } finally {
                setButtons(false);
            }
        });

        testBtn.addEventListener('click', async () => {
            try {
                setButtons(true);
                const characteristic = await connectPrinter(false);
                setMessage('Mengirim test print...');
                await writePrinterBytes(characteristic, buildTestPrintBytes());
                setMessage('Test print berhasil dikirim.');
            } catch (error) {
                setMessage(error.message || 'Gagal test print.', true);
            } finally {
                setButtons(false);
            }
        });

        disconnectBtn.addEventListener('click', () => {
            if (bluetoothDevice?.gatt?.connected) {
                bluetoothDevice.gatt.disconnect();
            }

            bluetoothCharacteristic = null;
            setConnectionState('Belum terhubung');
            setMessage('Koneksi printer diputus.');
        });

        resetBtn.addEventListener('click', () => {
            if (bluetoothDevice?.gatt?.connected) {
                bluetoothDevice.gatt.disconnect();
            }

            bluetoothDevice = null;
            bluetoothCharacteristic = null;
            localStorage.removeItem(storageKey);
            setConnectionState('Belum terhubung', 'Belum dipilih');
            setMessage('Pilihan printer direset.');
        });

        checkSupport();
    })();
</script>
@endscript
