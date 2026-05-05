<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-lg border border-black bg-[#1b1b18] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-black focus:outline-none focus:ring-2 focus:ring-[#f53003]/35 focus:ring-offset-2 dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white dark:focus:ring-[#FF4433]/40 dark:focus:ring-offset-[#0a0a0a]']) }}>
    {{ $slot }}
</button>
