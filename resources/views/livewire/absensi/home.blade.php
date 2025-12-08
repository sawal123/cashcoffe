<div>
    <style>
        .white {
            color: white;
        }

        .black {
            color: black;
        }
    </style>

    <section class="container my-4">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <!-- Header Biru -->
            <div class="p-4" style="background:#0798E3;">
                <div class=" justify-content-between align-items-center white">
                    <h5 class="mb-0 fw-semibold">
                        {{ $today }}
                    </h5>

                    <div class="d-flex align-items-center gap-2">
                        Pukul :<span class="fw-semibold" id="realtimeClock"> {{ $time }}</span>
                    </div>
                </div>
                <!-- CLOCK IN & CLOCK OUT -->
                @php
                $isLate = false;

                if ($absensiToday?->jam_masuk && $shift) {
                $isLate = \Carbon\Carbon::parse($absensiToday->jam_masuk)
                ->gt(\Carbon\Carbon::parse($shift->jam_masuk));
                }

                $bgColor = 'red';

                if ($absensiToday?->jam_masuk) {
                $bgColor = $isLate ? '#dc3545' : '#0798E3'; // merah | biru
                }
                @endphp

                <div class="row mt-4 g-2">
                    <div class="col-12 col-md-6">
                        <div class="rounded-4 bg-white p-3 text-center">
                            <p class="fw-semibold mb-2 d-flex justify-content-center gap-2 ">
                                <img src="{{ asset('absensi/svg/login.svg') }}" alt="">
                                CLOCK IN
                            </p>

                            <div class="rounded-3 px-3 py-2 fw-bold text-white" style="background: {{ $bgColor }};">

                                {{ $absensiToday?->jam_masuk
                                ? \Carbon\Carbon::parse($absensiToday->jam_masuk)->format('H : i : s')
                                : '-- : -- : --' }}
                            </div>



                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="rounded-4 bg-white p-3 text-center">
                            <p class="fw-semibold mb-2 d-flex justify-content-center gap-2">
                                <img src="{{ asset('absensi/svg/logout.svg') }}" alt="">
                                CLOCK OUT
                            </p>

                            <div class="rounded-3 px-3 py-2 fw-bold white"
                                style="background: {{ $absensiToday?->jam_keluar ? '#0798E3' : 'red' }};">
                                {{ $absensiToday?->jam_keluar ?
                                \Carbon\Carbon::parse($absensiToday->jam_keluar)->format('H : i : s') : '-- : -- : --'
                                }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <!-- Statistik -->
            {{-- <div class="bg-white p-4">
                <div class="row text-center">
                    <div class="col-4">
                        <p class="m-0 fw-semibold">Your Absence</p>
                        <h4 class="fw-bold mt-2">27</h4>
                    </div>

                    <div class="col-4 border-start border-end">
                        <p class="m-0 fw-semibold">Late Clock In</p>
                        <h4 class="fw-bold mt-2">01</h4>
                    </div>

                    <div class="col-4">
                        <p class="m-0 fw-semibold">No Clock In</p>
                        <h4 class="fw-bold mt-2">03</h4>
                    </div>
                </div>
            </div> --}}

        </div>
        @if (!$absensiToday?->jam_masuk)
        <a href="/absen/clock-in" class="btn btn-primary mt-5">
            Clock In
        </a>
        @else
        <a href="/absen/clock-in" class="btn btn-primary mt-5">
            Clock Out
        </a>
        @endif


    </section>

    <script>
        setInterval(() => {
            const now = new Date();

            // format HH.MM
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');

            document.getElementById('realtimeClock').innerText = `${hours}.${minutes} WIB`;
        }, 1000);
    </script>



</div>
