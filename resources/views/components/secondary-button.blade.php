<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm font-semibold text-[#1b1b18] shadow-sm transition-colors hover:bg-[#fafaf8] focus:outline-none focus:ring-2 focus:ring-[#f53003]/25 focus:ring-offset-2 disabled:opacity-25 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1c1c1a] dark:focus:ring-offset-[#0a0a0a]']) }}>
    {{ $slot }}
</button>
