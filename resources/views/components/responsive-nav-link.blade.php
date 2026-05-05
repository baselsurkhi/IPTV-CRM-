@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-[#f53003] dark:border-[#FF4433] text-start text-base font-medium text-[#1b1b18] dark:text-[#EDEDEC] bg-[#fff2f2]/50 dark:bg-[#1D0002]/30 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] hover:bg-gray-50 dark:hover:bg-[#161615] dark:hover:text-[#EDEDEC] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
