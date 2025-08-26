@props(['perPage' => null])
<div class="relative inline-block text-left">
     <button data-dropdown-toggle="paginateDropdown" data-dropdown-placement="bottom"
         class="text-primary-600 h-[41.6px] focus:bg-primary-600 hover:bg-primary-700 border border-primary-600 hover:text-white focus:text-white font-medium rounded-lg px-4 text-center inline-flex items-center"
         type="button">
         {{ $perPage }}
         <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
             viewBox="0 0 10 6">
             <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                 d="m1 1 4 4 4-4" />
         </svg>
     </button>

     <!-- Dropdown menu -->
     <div id="paginateDropdown"
         class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-2xl w-44 dark:bg-gray-700">
         <ul class="py-2 text-base text-gray-700 dark:text-gray-200">
             @foreach ([5, 10, 50, 100] as $size)
                 <li>
                     <a href="javascript:void(0)" wire:click="$set('perPage', {{ $size }})"
                         class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                         {{ $size }}
                     </a>
                 </li>
             @endforeach
         </ul>
     </div>
 </div>
