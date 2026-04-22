@props([
    'open'       => 'open',
    'closeExpr'  => null,
    'maxWidth'   => 'lg',
])

@php
$closeAction = $closeExpr ?? ($open . ' = false');
@endphp

<div
    x-show="{{ $open }}"
    x-cloak
    x-init="$watch('{{ $open }}', val => document.body.classList.toggle('overflow-hidden', val))"
    @keydown.escape.window="{{ $closeAction }}"
    class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"
         aria-hidden="true"
         @click="{{ $closeAction }}"></div>

    {{-- Panel --}}
    <div class="relative w-full my-auto rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900
                @if($maxWidth === 'sm')  max-w-sm
                @elseif($maxWidth === 'md')  max-w-md
                @elseif($maxWidth === 'xl')  max-w-xl
                @elseif($maxWidth === '2xl') max-w-2xl
                @else                        max-w-lg
                @endif"
         @click.stop>
        {{ $slot }}
    </div>
</div>
