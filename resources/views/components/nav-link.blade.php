@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-3 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm font-medium leading-normal text-[#1b1b18] dark:text-[#EDEDEC] bg-white dark:bg-[#161615] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.06)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]'
            : 'inline-flex items-center px-3 py-1.5 border border-transparent rounded-sm text-sm font-medium leading-normal text-[#706f6c] dark:text-[#A1A09A] hover:border-[#19140035] hover:text-[#1b1b18] dark:hover:border-[#3E3E3A] dark:hover:text-[#EDEDEC] transition-colors duration-150 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
