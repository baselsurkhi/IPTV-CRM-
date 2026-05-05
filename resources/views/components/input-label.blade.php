@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]']) }}>
    {{ $value ?? $slot }}
</label>
