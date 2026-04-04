@props(['name', 'label', 'checked' => false, 'color' => 'indigo'])

@php
    $colorClasses = [
        'indigo' => [
            'active' => 'bg-indigo-600',
            'inactive' => 'bg-gray-200 dark:bg-gray-700',
            'ring' => 'focus:ring-indigo-500',
        ],
        'green' => [
            'active' => 'bg-green-600',
            'inactive' => 'bg-gray-200 dark:bg-gray-700',
            'ring' => 'focus:ring-green-500',
        ],
    ];
    $colors = $colorClasses[$color] ?? $colorClasses['indigo'];
@endphp

<div class="space-y-3" x-data="{ active: {{ $checked ? 'true' : 'false' }} }">
    <label class="flex cursor-pointer items-center gap-3">
        <button type="button" role="switch" :aria-checked="active.toString()"
                @click="active = !active"
                :class="active ? '{{ $colors['active'] }}' : '{{ $colors['inactive'] }}'"
                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 {{ $colors['ring'] }} focus:ring-offset-2">
            <span :class="active ? 'translate-x-6' : 'translate-x-1'"
                  class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"></span>
        </button>
        <input type="hidden" name="{{ $name }}" :value="active ? '1' : '0'">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
    </label>
</div>

