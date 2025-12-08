<aside class="sidebar">
    <button type="button" class="sidebar-close-btn !mt-4">
        <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
    </button>
    <div>
        <a href="{{ route('dashboard.index') }}" class="sidebar-logo">
            <img src="{{ asset('logo/logow.png') }}" alt="site logo" class="light-logo">
            <img src="{{ asset('logo/logow.png') }}" style="width: 50ppx" alt="site logo" class="dark-logo">
            <img src="{{ asset('logo/logow.png') }}" alt="site logo" class="logo-icon">
        </a>
    </div>
    <style>
        /* Normal title style */
        .menu-title-link {
            padding: 12px 20px;
            font-size: 12px;
            font-weight: bold;
            color: rgb(155, 155, 155) !important;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: transparent !important;
            pointer-events: none;
            /* tidak bisa diklik */
        }

        .menu-title-link .color {
            color: #A4A4A4FF !important;
        }

        /* Jangan ikut hover/active */
        .menu-title-link:hover,
        .menu-title-link:focus {
            background: transparent !important;
            color: #888 !important;
        }

        /* Hilangkan saat collapsed */
        .sidebar-menu-area.collapsed .menu-title-link,
        .sidebar-menu-area.collapsed .menu-title {
            display: none !important;
        }
    </style>
    <div class="sidebar-menu-area">
        <ul class="sidebar-menu" id="sidebar-menu">

            <!-- GROUP: Dashboard -->
            <li class="menu-title">
                <a href="#" class="menu-title-link" onclick="return false;">
                    <span class="color">MAIN</span>
                </a>
            </li>

            <li>
                <a href="/dashboard" wire:navigate>
                    <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- GROUP: Menu & Order -->

            <li class="menu-title">
                <a href="#" class="menu-title-link" onclick="return false;">
                    <span class="color">Menu & Order</span>
                </a>
            </li>

            <li>
                <a href="/order" wire:navigate>
                    <iconify-icon icon="solar:cart-3-bold" class="menu-icon"></iconify-icon>
                    <span>Order</span>
                </a>
            </li>

            <li>
                <a href="/menu" wire:navigate>
                    <iconify-icon icon="solar:book-broken" class="menu-icon"></iconify-icon>
                    <span>Menu</span>
                </a>
            </li>

            <li>
                <a href="/menu-ingredient" wire:navigate>
                    <iconify-icon icon="solar:chef-hat-broken" class="menu-icon"></iconify-icon>
                    <span>Kompisi Menu</span>
                </a>
            </li>

            <li>
                <a href="/category" wire:navigate>
                    <iconify-icon icon="solar:tag-broken" class="menu-icon"></iconify-icon>
                    <span>Category</span>
                </a>
            </li>


            <li class="menu-title">
                <a href="#" class="menu-title-link" onclick="return false;">
                    <span class="color">Keuangan</span>
                </a>
            </li>

            <li>
                <a href="/transaksi" wire:navigate>
                    <iconify-icon icon="solar:wallet-bold" class="menu-icon"></iconify-icon>
                    <span>Transaksi</span>
                </a>
            </li>

            <li>
                <a href="/member" wire:navigate>
                    <iconify-icon icon="solar:users-group-two-rounded-bold" class="menu-icon"></iconify-icon>
                    <span>Member</span>
                </a>
            </li>

            <li>
                <a href="/omset" wire:navigate>
                    <iconify-icon icon="solar:graph-up-bold" class="menu-icon"></iconify-icon>
                    <span>Omset</span>
                </a>
            </li>

            <li>
                <a href="/pengeluaran" wire:navigate>
                    <iconify-icon icon="solar:money-bag-broken" class="menu-icon"></iconify-icon>
                    <span>Pengeluaran</span>
                </a>
            </li>

            <li>
                <a href="/discount" wire:navigate>
                    <iconify-icon icon="solar:chat-round-money-broken" class="menu-icon"></iconify-icon>
                    <span>Discount</span>
                </a>
            </li>
            @unlessrole('kasir')
                <!-- GROUP: Gudang -->
                <li class="menu-title">
                    <a href="#" class="menu-title-link" onclick="return false;">
                        <span class="color">Gudang</span>
                    </a>

                <li>
                    <a href="/stock-dapur" wire:navigate>
                        <iconify-icon icon="solar:box-bold" class="menu-icon"></iconify-icon>
                        <span>Stock Dapur</span>
                    </a>
                </li>

                <li>
                    <a href="/riwayat-stock" wire:navigate>
                        <iconify-icon icon="solar:history-bold" class="menu-icon"></iconify-icon>
                        <span>Riwayat Stock</span>
                    </a>
                </li>

                <li>
                    <a href="/gudang" wire:navigate>
                        <iconify-icon icon="solar:box-bold" class="menu-icon"></iconify-icon>
                        <span>Gudang</span>
                    </a>
                </li>

                <li>
                    <a href="/riwayat-gudang" wire:navigate>
                        <iconify-icon icon="solar:history-bold" class="menu-icon"></iconify-icon>
                        <span>Riwayat Gudang</span>
                    </a>
                </li>
            @endunlessrole
            <!-- GROUP: User -->
            @unlessrole('kasir')
             <li class="menu-title">
                    <a href="#" class="menu-title-link" onclick="return false;">
                        <span class="color">Absensi</span>
                    </a>
                <li>
                    <a href="/absense" wire:navigate>
                        <iconify-icon icon="solar:user-id-bold" class="menu-icon"></iconify-icon>
                        <span>Absensi</span>
                    </a>
                </li>


                <li class="menu-title">
                    <a href="#" class="menu-title-link" onclick="return false;">
                        <span class="color">User Management</span>
                    </a>
                <li>
                    <a href="/user" wire:navigate>
                        <iconify-icon icon="solar:user-id-bold" class="menu-icon"></iconify-icon>
                        <span>User</span>
                    </a>
                </li>
            @endunlessrole

        </ul>
    </div>
</aside>
