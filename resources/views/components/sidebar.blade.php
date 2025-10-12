<aside class="sidebar">
    <button type="button" class="sidebar-close-btn !mt-4">
        <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
    </button>
    <div>
        <a href="{{ route('dashboard.index') }}" class="sidebar-logo">
            <img src="{{ asset('logo/logo.png') }}" alt="site logo" class="light-logo">
            <img src="{{ asset('logo/logo.png') }}" alt="site logo" class="dark-logo">
            <img src="{{ asset('logo/logo.png') }}" alt="site logo" class="logo-icon">
        </a>
    </div>
    <div class="sidebar-menu-area">
        <ul class="sidebar-menu" id="sidebar-menu">
            <li>
                <a href="/dashboard" wire:navigate>
                    <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/order" wire:navigate>
                    <iconify-icon icon="solar:cart-5-outline" class="menu-icon"></iconify-icon>
                    <span>Order</span>
                </a>
            </li>
            <li>
                <a href="/menu" wire:navigate>
                    <iconify-icon icon="solar:document-text-outline" class="menu-icon"></iconify-icon>
                    <span>Menu</span>
                </a>
            </li>
            <li>
                <a href="/category" wire:navigate>
                    <iconify-icon icon="solar:document-add-outline" class="menu-icon"></iconify-icon>
                    <span>Category</span>
                </a>
            </li>
            @unlessrole('kasir')
                <li>
                    <a href="/gudang" wire:navigate>
                        <iconify-icon icon="solar:dollar-linear" class="menu-icon"></iconify-icon>
                        <span>Omset</span>
                    </a>
                </li>
            @endunlessrole

            <li>
                <a href="/discount" wire:navigate>
                    <iconify-icon icon="solar:documents-minimalistic-outline" class="menu-icon"></iconify-icon>
                    <span>Discount</span>
                </a>
            </li>
            <li>
                <a href="/gudang" wire:navigate>
                    <iconify-icon icon="solar:documents-minimalistic-outline" class="menu-icon"></iconify-icon>
                    <span>Gudang</span>
                </a>
            </li>
            <li>
                <a href="/meja" wire:navigate>
                    <iconify-icon icon="solar:bedside-table-3-broken" class="menu-icon"></iconify-icon>
                    <span>Meja</span>
                </a>
            </li>

        </ul>
    </div>
</aside>
