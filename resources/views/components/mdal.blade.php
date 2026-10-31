@props([
    'name',
    'showClose' => true,
    'maxWidth' => 'max-w-md',
    'panelClass' => '',
    'overlayClass' => '',
    'wireModel' => null,
]) {{-- 1. Terima atribut 'name' sebagai props --}}

<div x-data="{
        modalIsOpen: @if($wireModel) $wire.entangle(@js($wireModel)) @else false @endif,
        selectedId: null,
        closeModal() {
            this.modalIsOpen = false;
            @if($wireModel)
                this.$wire.set(@js($wireModel), false, false);
            @endif
        }
    }"
    x-init="$watch('modalIsOpen', value => {
        window.dispatchEvent(new CustomEvent('toggle-blur', { detail: value }));
        document.body.classList.toggle('overflow-hidden', value);
    })"
    {{-- 2. Logic dibuat dinamis mencocokkan event name dengan props name --}}
    x-on:open-modal.window="
        if (typeof $event.detail === 'string' ? $event.detail === '{{ $name }}' : $event.detail?.name === '{{ $name }}') {
            modalIsOpen = true;
            if ($event.detail?.id) {
                selectedId = $event.detail.id;
            }
        }
    "
    {{-- Tambahan: Listener untuk menutup modal dari Livewire --}}
    x-on:close-modal.window="
        if (typeof $event.detail === 'string' ? $event.detail === '{{ $name }}' : $event.detail?.name === '{{ $name }}') {
            closeModal();
        }
    "
    >

    @teleport('body')
        <div x-cloak
            x-show="modalIsOpen || @if($wireModel) $wire.{{ $wireModel }} @else false @endif"
            @if($wireModel) wire:show="{{ $wireModel }}" @endif
            x-transition.opacity.duration.200ms
            x-on:keydown.esc.window="closeModal()"
            x-on:click.self="closeModal()"
            class="fixed inset-0 z-[999999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto {{ $overlayClass }}"
            role="dialog"
            aria-modal="true"
            aria-labelledby="defaultModalTitle">

            <div x-show="modalIsOpen || @if($wireModel) $wire.{{ $wireModel }} @else false @endif"
                @if($wireModel) wire:show="{{ $wireModel }}" @endif
                x-transition:enter="transition ease-out duration-200 delay-100"
                x-transition:enter-start="opacity-0 scale-75"
                x-transition:enter-end="opacity-100 scale-100"
                class="w-full {{ $maxWidth }} {{ $panelClass }} rounded-2xl border border-neutral-200 bg-white shadow-2xl dark:border-neutral-700 dark:bg-neutral-800 my-auto">

                @if($showClose)
                    <div class="flex items-center justify-end border-neutral-200 p-4 dark:border-neutral-700">
                        <button x-on:click="closeModal()" aria-label="close modal"
                            class="text-gray-500 hover:text-gray-800 dark:text-neutral-400 dark:hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.6" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif

                {{ $slot }}
            </div>
        </div>
    @endteleport
</div>
