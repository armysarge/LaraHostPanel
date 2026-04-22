@props([
    'open'         => 'open',
    'title'        => 'Are you sure?',
    'description'  => 'This action cannot be undone.',
    'action'       => '',
    'alpineAction' => null,
    'method'       => 'DELETE',
    'confirmText'  => 'Delete',
    'cancelText'   => 'Cancel',
])

<x-modal :open="$open" max-width="sm">
    <div class="p-6">
        {{-- Icon + text --}}
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40">
                <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1 pt-0.5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="mt-5 flex items-center justify-end gap-3">
            <button type="button"
                    @click="{{ $open }} = false"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                {{ $cancelText }}
            </button>
            <form method="POST" @if($alpineAction) :action="{{ $alpineAction }}" @else action="{{ $action }}" @endif>
                @csrf
                @if (strtoupper($method) !== 'POST')
                    @method($method)
                @endif
                <button type="submit"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    {{ $confirmText }}
                </button>
            </form>
        </div>
    </div>
</x-modal>
