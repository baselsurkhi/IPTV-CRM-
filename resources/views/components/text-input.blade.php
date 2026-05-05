@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border border-[#e3e3e0] bg-[#FDFDFC] text-[#1b1b18] shadow-sm transition-colors focus:border-[#f53003] focus:outline-none focus:ring-2 focus:ring-[#f53003]/20 dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]/20']) }}>
