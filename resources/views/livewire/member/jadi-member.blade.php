<div class="flex h-screen h-[100dvh] w-full justify-center overflow-hidden bg-[#f5f6fb] text-white">
    <main class="flex h-full w-full max-w-[430px] flex-col overflow-hidden bg-white">
        @if ($step === 'intro')
            <section class="flex min-h-0 flex-1 items-center justify-center px-6 pb-5 pt-7"
                aria-label="Ilustrasi promosi member">
                <img class="block max-h-[39dvh] w-[min(82vw,330px)] object-contain"
                    src="{{ asset('icon_screen/image 28.png') }}" alt="Ilustrasi pelanggan menikmati Temuan Space">
            </section>

            <section
                class="flex h-[46dvh] min-h-[296px] flex-none flex-col items-center rounded-t-[28px] bg-[#4f5bec] px-6 pb-[max(24px,env(safe-area-inset-bottom))] pt-10 text-center">
                <h1
                    class="m-0 text-[25px] font-extrabold leading-tight tracking-normal text-white max-[360px]:text-[22px]">
                    Jadi Member<br>Temuan Space</h1>
                <p
                    class="mt-4 max-w-[320px] text-[15px] font-semibold leading-relaxed text-white/90 max-[360px]:text-[13px]">
                    Nikmati promo dan benefit khusus yang tersedia untuk member Temuan Space.</p>

                <button
                    class="mt-9 inline-flex h-14 w-full max-w-[240px] items-center justify-center rounded-xl border-0 bg-white text-[17px] font-extrabold text-[#4f5bec] shadow-[0_10px_22px_rgba(28,38,150,0.18)] transition duration-150 hover:-translate-y-px hover:shadow-[0_12px_26px_rgba(28,38,150,0.24)] active:translate-y-0 disabled:cursor-wait disabled:opacity-90 max-[360px]:max-w-[220px] max-[360px]:text-[16px]"
                    type="button" wire:click="showForm" wire:loading.attr="disabled" wire:target="showForm"
                    x-data="{ pending: false }" x-on:click="pending = true" x-bind:disabled="pending">
                    <span x-show="!pending">Lanjut</span>
                    <span class="inline-flex items-center justify-center" x-cloak x-show="pending">
                        <span
                            class="h-5 w-5 animate-spin rounded-full border-2 border-solid border-[#4f5bec]/25 border-t-[#4f5bec]"></span>
                    </span>
                </button>
            </section>
        @elseif ($step === 'form')
            <section class="flex min-h-0 flex-1 items-center justify-center px-6 pb-5 pt-7"
                aria-label="Ilustrasi isi data member">
                <img class="block max-h-[38dvh] w-[min(76vw,300px)] object-contain"
                    src="{{ asset('icon_screen/image 29.png') }}" alt="Ilustrasi isi data member">
            </section>

            <section
                class="flex h-[46dvh] min-h-[296px] flex-none flex-col items-center rounded-t-[28px] bg-[#4f5bec] px-6 pb-[max(24px,env(safe-area-inset-bottom))] pt-8 text-center">
                <h1
                    class="m-0 text-[24px] font-extrabold leading-tight tracking-normal text-white max-[360px]:text-[21px]">
                    Lengkapi Data Member</h1>

                <form class="mt-6 flex w-full max-w-[336px] flex-col gap-3.5 max-[390px]:max-w-[calc(100%-48px)]"
                    wire:submit.prevent="submitProfile">
                    <input
                        class="box-border h-16 w-full max-w-full rounded-xl border-0 bg-[#dfe2f4] px-5 text-[17px] font-extrabold text-[#4f5bec] shadow-[inset_0_0_0_1px_rgba(255,255,255,0.32)] outline-none placeholder:text-[#9aa2cf] focus:ring-2 focus:ring-white/60 max-[360px]:h-14 max-[360px]:text-[16px]"
                        type="text" wire:model="name" placeholder="Nama lengkap" autocomplete="name">

                    <div class="grid w-full max-w-full grid-cols-[80px_minmax(0,1fr)] gap-3">
                        <div
                            class="box-border flex h-16 min-w-0 items-center justify-center rounded-xl bg-[#dfe2f4] text-[17px] font-extrabold text-[#4f5bec] shadow-[inset_0_0_0_1px_rgba(255,255,255,0.32)] max-[360px]:h-14 max-[360px]:text-[16px]">
                            {{ $countryCode }}</div>
                        <input
                            class="box-border h-16 w-full min-w-0 max-w-full rounded-xl border-0 bg-[#dfe2f4] px-5 text-[17px] font-extrabold text-[#4f5bec] shadow-[inset_0_0_0_1px_rgba(255,255,255,0.32)] outline-none placeholder:text-[#9aa2cf] focus:ring-2 focus:ring-white/60 max-[360px]:h-14 max-[360px]:text-[16px]"
                            type="tel" wire:model="phone" placeholder="Nomor WhatsApp" inputmode="tel"
                            autocomplete="tel">
                    </div>

                    @error('name')
                        <p class="m-0 text-[13px] font-bold leading-snug text-white">{{ $message }}</p>
                    @enderror

                    @error('phone')
                        <p class="m-0 text-[13px] font-bold leading-snug text-white">{{ $message }}</p>
                    @enderror

                    <button
                        class="mx-auto mt-4 inline-flex h-14 w-full max-w-[240px] items-center justify-center rounded-xl border-0 bg-white text-[17px] font-extrabold text-[#4f5bec] shadow-[0_10px_22px_rgba(28,38,150,0.18)] transition duration-150 hover:-translate-y-px hover:shadow-[0_12px_26px_rgba(28,38,150,0.24)] active:translate-y-0 disabled:cursor-wait disabled:opacity-90 max-[360px]:max-w-[220px] max-[360px]:text-[16px]"
                        type="submit" wire:loading.attr="disabled" wire:target="submitProfile">
                        <span wire:loading.remove wire:target="submitProfile">Lanjut</span>
                        <span class="inline-flex items-center justify-center" wire:loading wire:target="submitProfile">
                            <span
                                class="h-5 w-5 animate-spin rounded-full border-2 border-solid border-[#4f5bec]/25 border-t-[#4f5bec]"></span>
                        </span>
                    </button>
                </form>
            </section>
        @elseif ($step === 'otp')
            <section class="flex min-h-0 flex-1 items-center justify-center px-6 pb-5 pt-7"
                aria-label="Ilustrasi verifikasi OTP">
                <img class="block max-h-[37dvh] w-[min(82vw,320px)] object-contain"
                    src="{{ asset('icon_screen/image 30.png') }}" alt="Ilustrasi verifikasi OTP">
            </section>

            <section
                class="flex h-[46dvh] min-h-[296px] flex-none flex-col items-center rounded-t-[28px] bg-[#4f5bec] px-6 pb-[max(24px,env(safe-area-inset-bottom))] pt-8 text-center">
                <h1
                    class="m-0 text-[24px] font-extrabold leading-tight tracking-normal text-white max-[360px]:text-[21px]">
                    Masukkan Kode OTP</h1>
                <p
                    class="mt-3 max-w-[320px] text-[14px] font-semibold leading-relaxed text-white/90 max-[360px]:text-[13px]">
                    Kode telah dikirim ke WhatsApp {{ $otpSentTo ? '+' . $otpSentTo : '' }}.</p>

                <form class="mt-5 flex w-full max-w-[336px] flex-col gap-3.5 max-[390px]:max-w-[calc(100%-48px)]"
                    wire:submit.prevent="verifyOtp" x-data="{
                        digits: ['', '', '', '', '', ''],
                        syncOtp() {
                            const value = this.digits.join('');
                            this.$wire.set('otp', value, false);
                        },
                        handleInput(index, event) {
                            const value = event.target.value.replace(/\D/g, '');

                            if (value.length > 1) {
                                value.slice(0, 6).split('').forEach((digit, offset) => {
                                    if (index + offset < 6) {
                                        this.digits[index + offset] = digit;
                                    }
                                });

                                this.syncOtp();
                                this.$nextTick(() => {
                                    const nextIndex = Math.min(index + value.length, 5);
                                    this.$refs[`otp${nextIndex}`].focus();
                                });

                                return;
                            }

                            this.digits[index] = value;
                            this.syncOtp();

                            if (value && index < 5) {
                                this.$nextTick(() => this.$refs[`otp${index + 1}`].focus());
                            }
                        },
                        handlePaste(index, event) {
                            const value = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);

                            if (!value) {
                                return;
                            }

                            value.split('').forEach((digit, offset) => {
                                if (index + offset < 6) {
                                    this.digits[index + offset] = digit;
                                }
                            });

                            this.syncOtp();
                            this.$nextTick(() => {
                                const nextIndex = Math.min(index + value.length, 5);
                                this.$refs[`otp${nextIndex}`].focus();
                            });
                        },
                        handleBackspace(index, event) {
                            if (event.key !== 'Backspace') {
                                return;
                            }

                            if (this.digits[index]) {
                                this.digits[index] = '';
                                this.syncOtp();
                                return;
                            }

                            if (index > 0) {
                                this.$refs[`otp${index - 1}`].focus();
                            }
                        }
                    }">
                    <div class="grid w-full grid-cols-6 gap-2.5 max-[360px]:gap-2">
                        @for ($index = 0; $index < 6; $index++)
                            <input
                                class="box-border h-14 w-full min-w-0 rounded-xl border-0 bg-[#dfe2f4] p-0 text-center text-[22px] font-extrabold text-[#4f5bec] shadow-[inset_0_0_0_1px_rgba(255,255,255,0.32)] outline-none placeholder:text-[#9aa2cf] focus:ring-2 focus:ring-white/70 max-[360px]:h-12 max-[360px]:text-[20px]"
                                type="tel" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                                autocomplete="{{ $index === 0 ? 'one-time-code' : 'off' }}"
                                x-ref="otp{{ $index }}" x-model="digits[{{ $index }}]"
                                x-on:input="handleInput({{ $index }}, $event)"
                                x-on:keydown="handleBackspace({{ $index }}, $event)"
                                x-on:paste.prevent="handlePaste({{ $index }}, $event)">
                        @endfor
                    </div>

                    @error('otp')
                        <p class="m-0 text-[13px] font-bold leading-snug text-white">{{ $message }}</p>
                    @enderror

                    @error('phone')
                        <p class="m-0 text-[13px] font-bold leading-snug text-white">{{ $message }}</p>
                    @enderror

                    <button
                        class="mx-auto mt-2 inline-flex h-14 w-full max-w-[240px] items-center justify-center rounded-xl border-0 bg-white text-[17px] font-extrabold text-[#4f5bec] shadow-[0_10px_22px_rgba(28,38,150,0.18)] transition duration-150 hover:-translate-y-px hover:shadow-[0_12px_26px_rgba(28,38,150,0.24)] active:translate-y-0 disabled:cursor-wait disabled:opacity-90 max-[360px]:max-w-[220px] max-[360px]:text-[16px]"
                        type="submit" wire:loading.attr="disabled" wire:target="verifyOtp">
                        <span wire:loading.remove wire:target="verifyOtp">Verifikasi</span>
                        <span class="inline-flex items-center justify-center" wire:loading wire:target="verifyOtp">
                            <span
                                class="h-5 w-5 animate-spin rounded-full border-2 border-solid border-[#4f5bec]/25 border-t-[#4f5bec]"></span>
                        </span>
                    </button>
                </form>

                <button
                    class="mt-3 border-0 bg-transparent p-0 text-[14px] font-extrabold text-white/95 underline-offset-4 hover:underline disabled:cursor-wait disabled:opacity-70"
                    type="button" wire:click="resendOtp" wire:loading.attr="disabled" wire:target="resendOtp">
                    <span wire:loading.remove wire:target="resendOtp">Kirim ulang OTP</span>
                    <span class="inline-flex items-center justify-center" wire:loading wire:target="resendOtp">
                        <span
                            class="h-4 w-4 animate-spin rounded-full border-2 border-solid border-white/30 border-t-white"></span>
                    </span>
                </button>
            </section>
        @else
            <section class="flex min-h-0 flex-1 items-center justify-center px-6 pb-5 pt-7"
                aria-label="Ilustrasi berhasil jadi member">
                <img class="block max-h-[40dvh] w-[min(82vw,320px)] object-contain"
                    src="{{ asset('icon_screen/image 31.png') }}" alt="Ilustrasi berhasil jadi member">
            </section>

            <section
                class="flex h-[46dvh] min-h-[296px] flex-none flex-col items-center rounded-t-[28px] bg-[#4f5bec] px-6 pb-[max(24px,env(safe-area-inset-bottom))] pt-10 text-center">
                <h1
                    class="m-0 text-[25px] font-extrabold leading-tight tracking-normal text-white max-[360px]:text-[22px]">
                    Pendaftaran Berhasil</h1>
                <p
                    class="mt-4 max-w-[320px] text-[15px] font-semibold leading-relaxed text-white/90 max-[360px]:text-[13px]">
                    Saat datang ke Temuan Space, cukup sebutkan nomor yang sudah kamu daftarkan.</p>

                <a
                    href="https://wa.me/?text={{ rawurlencode('Yuk jadi member Temuan Space: https://temuanspace.com/jadi-member') }}"
                    target="_blank" rel="noopener"
                    class="mt-10 inline-flex h-14 w-full max-w-[240px] items-center justify-center rounded-xl border-0 bg-white text-[17px] font-extrabold text-[#4f5bec] shadow-[0_10px_22px_rgba(28,38,150,0.18)] transition duration-150 hover:-translate-y-px hover:shadow-[0_12px_26px_rgba(28,38,150,0.24)] active:translate-y-0 disabled:cursor-wait disabled:opacity-90 max-[360px]:max-w-[220px] max-[360px]:text-[16px]"
                    role="button">
                    Ajak Teman
                </a>
            </section>
        @endif
    </main>
</div>
