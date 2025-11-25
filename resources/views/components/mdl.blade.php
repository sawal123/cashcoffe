<div x-data="{ modalIsOpen: false, selectedId: null }"
    @open-modal.window="
if ($event.detail.name === 'confirm-delete')
{
modalIsOpen = true;
selectedId = $event.detail.id
 }
  if ($event.detail.name === 'detail-order') {
        modalIsOpen = true;
    }
">
    <!-- Modal Overlay -->
    <div x-cloak x-show="modalIsOpen" x-transition.opacity.duration.200ms x-trap.inert.noscroll="modalIsOpen"
        x-on:keydown.esc.window="modalIsOpen = false" x-on:click.self="modalIsOpen = false"
        class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4" role="dialog"
        aria-modal="true" aria-labelledby="defaultModalTitle">

        <!-- Modal Card -->
        <div x-show="modalIsOpen" x-transition:enter="transition ease-out duration-200 delay-100"
            x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-md rounded-2xl border border-neutral-200 bg-white shadow-xl dark:border-neutral-700 dark:bg-neutral-800">
            <div class="flex items-center justify-end  border-neutral-200 p-4 dark:border-neutral-700">
                <button x-on:click="modalIsOpen = false" aria-label="close modal"
                    class="text-gray-500 hover:text-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.6" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <!-- Dialog Header -->
            {{ $slot }}
        </div>
    </div>
</div>
