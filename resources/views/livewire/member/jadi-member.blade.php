<div class="flex h-screen h-[100dvh] w-full justify-center overflow-hidden bg-[#f5f6fb] text-white">
    <main class="flex h-full w-full max-w-[430px] flex-col overflow-hidden bg-white">
        @if ($step === 'intro')
            <section class="flex min-h-0 flex-1 items-center justify-center px-6 pb-5 pt-7"
                aria-label="Ilustrasi promosi member">
                <img class="block max-h-[39dvh] w-[min(82vw,330px)] object-contain"
                    src="{{ asset('icon_screen/image 28.png') }}" alt="Ilustrasi pelanggan menikmati Temuan Space">
            </section>

            <section
                class="flex h-[46dvh] min-h-[296px] flex-none flex-col items-center rounded-t-[32px] bg-[#4f5bec] px-6 pb-[max(24px,env(safe-area-inset-bottom))] pt-10 text-center shadow-[0_-18px_44px_rgba(31,36,120,0.30)]"
                style="background-image: radial-gradient(135% 105% at 50% -12%, #8a7dff 0%, #5d5df1 36%, #4f5bec 62%, #2a2ea6 100%);">
                <h1
                    class="m-0 text-[26px] font-extrabold leading-tight tracking-tight text-white drop-shadow-[0_2px_8px_rgba(20,22,80,0.35)] max-[360px]:text-[22px]">
                    Jadi Member<br>Temuan Space</h1>
                <p
                    class="mt-4 max-w-[320px] text-[15px] font-medium leading-relaxed text-white/85 max-[360px]:text-[13px]">
                    Nikmati promo dan benefit khusus yang tersedia untuk member Temuan Space.</p>

                <button
                    class="mt-9 inline-flex h-14 w-full max-w-[240px] items-center justify-center rounded-2xl border-0 bg-white text-[17px] font-extrabold text-[#4f5bec] shadow-[0_14px_30px_rgba(20,24,110,0.35)] transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_18px_36px_rgba(20,24,110,0.45)] active:translate-y-0 active:scale-[0.98] disabled:cursor-wait disabled:opacity-90 max-[360px]:max-w-[220px] max-[360px]:text-[16px]"
                    type="button" wire:click="showForm" wire:loading.attr="disabled" wire:target="showForm">
                    <span wire:loading.remove wire:target="showForm">Lanjut</span>
                    <span class="inline-flex items-center justify-center" wire:loading wire:target="showForm">
                        <span
                            class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-solid border-[#4f5bec]/25 border-t-[#4f5bec]"></span>
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
                class="flex h-[46dvh] min-h-[296px] flex-none flex-col items-center rounded-t-[32px] bg-[#4f5bec] px-6 pb-[max(24px,env(safe-area-inset-bottom))] pt-8 text-center shadow-[0_-18px_44px_rgba(31,36,120,0.30)]"
                style="background-image: radial-gradient(135% 105% at 50% -12%, #8a7dff 0%, #5d5df1 36%, #4f5bec 62%, #2a2ea6 100%);">
                <h1
                    class="m-0 text-[25px] font-extrabold leading-tight tracking-tight text-white drop-shadow-[0_2px_8px_rgba(20,22,80,0.35)] max-[360px]:text-[21px]">
                    Lengkapi Data Member</h1>

                <form class="mt-6 flex w-full max-w-[336px] flex-col gap-3.5 max-[390px]:max-w-[calc(100%-48px)]"
                    wire:submit.prevent="submitProfile">
                    <input
                        class="box-border h-14 w-full max-w-full rounded-2xl border-0 bg-white/95 px-5 text-[16px] font-semibold text-slate-900 shadow-[0_10px_24px_rgba(18,22,90,0.22)] outline-none transition duration-200 placeholder:font-medium placeholder:text-slate-400 focus:bg-white focus:shadow-[0_0_0_4px_rgba(138,125,255,0.40)] max-[360px]:text-[15px]"
                        type="text" wire:model="name" placeholder="Nama lengkap" autocomplete="name">

                    <div
                        class="box-border flex h-14 w-full max-w-full items-stretch overflow-hidden rounded-2xl bg-white/95 shadow-[0_10px_24px_rgba(18,22,90,0.22)] transition duration-200 focus-within:bg-white focus-within:shadow-[0_0_0_4px_rgba(138,125,255,0.40)] max-[360px]:h-14">
                        <span
                            class="flex w-[66px] shrink-0 items-center justify-center border-r border-solid border-slate-200 text-[15px] font-bold text-slate-500 max-[360px]:w-[60px] max-[360px]:text-[14px]">{{ $countryCode }}</span>
                        <input
                            class="h-full w-full min-w-0 flex-1 border-0 bg-transparent px-4 text-[16px] font-semibold text-slate-900 outline-none placeholder:font-medium placeholder:text-slate-400 max-[360px]:text-[15px]"
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
                        class="mx-auto mt-4 inline-flex h-14 w-full max-w-[240px] items-center justify-center rounded-2xl border-0 bg-white text-[17px] font-extrabold text-[#4f5bec] shadow-[0_14px_30px_rgba(20,24,110,0.35)] transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_18px_36px_rgba(20,24,110,0.45)] active:translate-y-0 active:scale-[0.98] disabled:cursor-wait disabled:opacity-90 max-[360px]:max-w-[220px] max-[360px]:text-[16px]"
                        type="submit" wire:loading.attr="disabled" wire:target="submitProfile">
                        <span wire:loading.remove wire:target="submitProfile">Lanjut</span>
                        <span class="inline-flex items-center justify-center" wire:loading wire:target="submitProfile">
                            <span
                                class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-solid border-[#4f5bec]/25 border-t-[#4f5bec]"></span>
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
                class="flex h-[46dvh] min-h-[296px] flex-none flex-col items-center rounded-t-[32px] bg-[#4f5bec] px-6 pb-[max(24px,env(safe-area-inset-bottom))] pt-8 text-center shadow-[0_-18px_44px_rgba(31,36,120,0.30)]"
                style="background-image: radial-gradient(135% 105% at 50% -12%, #8a7dff 0%, #5d5df1 36%, #4f5bec 62%, #2a2ea6 100%);">
                <h1
                    class="m-0 text-[25px] font-extrabold leading-tight tracking-tight text-white drop-shadow-[0_2px_8px_rgba(20,22,80,0.35)] max-[360px]:text-[21px]">
                    Masukkan Kode OTP</h1>
                <p
                    class="mt-3 max-w-[320px] text-[14px] font-medium leading-relaxed text-white/85 max-[360px]:text-[13px]">
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
                                class="box-border h-14 w-full min-w-0 rounded-2xl border-0 bg-white/95 p-0 text-center text-[22px] font-extrabold text-slate-900 shadow-[0_8px_20px_rgba(18,22,90,0.20)] outline-none transition duration-150 focus:bg-white focus:shadow-[0_0_0_4px_rgba(138,125,255,0.45)] max-[360px]:h-12 max-[360px]:text-[20px]"
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
                        class="mx-auto mt-2 inline-flex h-14 w-full max-w-[240px] items-center justify-center rounded-2xl border-0 bg-white text-[17px] font-extrabold text-[#4f5bec] shadow-[0_14px_30px_rgba(20,24,110,0.35)] transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_18px_36px_rgba(20,24,110,0.45)] active:translate-y-0 active:scale-[0.98] disabled:cursor-wait disabled:opacity-90 max-[360px]:max-w-[220px] max-[360px]:text-[16px]"
                        type="submit" wire:loading.attr="disabled" wire:target="verifyOtp">
                        <span wire:loading.remove wire:target="verifyOtp">Verifikasi</span>
                        <span class="inline-flex items-center justify-center" wire:loading wire:target="verifyOtp">
                            <span
                                class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-solid border-[#4f5bec]/25 border-t-[#4f5bec]"></span>
                        </span>
                    </button>
                </form>

                <button
                    class="mt-3 border-0 bg-transparent p-0 text-[14px] font-bold text-white/90 underline-offset-4 transition duration-150 hover:text-white hover:underline disabled:cursor-wait disabled:opacity-70"
                    type="button" wire:click="resendOtp" wire:loading.attr="disabled" wire:target="resendOtp">
                    <span wire:loading.remove wire:target="resendOtp">Kirim ulang OTP</span>
                    <span class="inline-flex items-center justify-center" wire:loading wire:target="resendOtp">
                        <span
                            class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-white/30 border-t-white"></span>
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
                class="flex h-[46dvh] min-h-[296px] flex-none flex-col items-center rounded-t-[32px] bg-[#4f5bec] px-6 pb-[max(24px,env(safe-area-inset-bottom))] pt-10 text-center shadow-[0_-18px_44px_rgba(31,36,120,0.30)]"
                style="background-image: radial-gradient(135% 105% at 50% -12%, #8a7dff 0%, #5d5df1 36%, #4f5bec 62%, #2a2ea6 100%);">
                <h1
                    class="m-0 text-[26px] font-extrabold leading-tight tracking-tight text-white drop-shadow-[0_2px_8px_rgba(20,22,80,0.35)] max-[360px]:text-[22px]">
                    Pendaftaran Berhasil</h1>
                <p
                    class="mt-4 max-w-[320px] text-[15px] font-medium leading-relaxed text-white/85 max-[360px]:text-[13px]">
                    Saat datang ke Temuan Space, cukup sebutkan nomor yang sudah kamu daftarkan.</p>

                <a href="https://wa.me/?text={{ rawurlencode('Yuk jadi member Temuan Space: https://temuanspace.com/jadi-member') }}"
                    target="_blank" rel="noopener"
                    class="mt-10 inline-flex h-14 w-full max-w-[240px] items-center justify-center rounded-2xl border-0 bg-white text-[17px] font-extrabold text-[#4f5bec] shadow-[0_14px_30px_rgba(20,24,110,0.35)] transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_18px_36px_rgba(20,24,110,0.45)] active:translate-y-0 active:scale-[0.98] disabled:cursor-wait disabled:opacity-90 max-[360px]:max-w-[220px] max-[360px]:text-[16px]"
                    role="button">
                    Ajak Teman
                </a>
            </section>
        @endif
    </main>
</div>
