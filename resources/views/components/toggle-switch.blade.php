@props(['status', 'onAction', 'offAction', 'name'])

@if ($status === 'running')
    <form method="POST" action="{{ $offAction }}">
        @csrf
        <button type="submit"
                title="Running — click to stop"
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-emerald-500 transition-colors duration-200 ease-in-out hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
            <span class="sr-only">Stop {{ $name }}</span>
            <span class="pointer-events-none inline-block h-5 w-5 translate-x-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
        </button>
    </form>
@elseif ($status === 'stopped')
    <form method="POST" action="{{ $onAction }}">
        @csrf
        <button type="submit"
                title="Stopped — click to start"
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-gray-300 dark:bg-gray-600 transition-colors duration-200 ease-in-out hover:bg-gray-400 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
            <span class="sr-only">Start {{ $name }}</span>
            <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
        </button>
    </form>
@elseif ($status === 'error')
    <form method="POST" action="{{ $onAction }}">
        @csrf
        <button type="submit"
                title="Error — click to retry"
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-red-500 transition-colors duration-200 ease-in-out hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
            <span class="sr-only">Retry {{ $name }}</span>
            <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
        </button>
    </form>
@else
    {{-- Deploying — non-interactive --}}
    <span class="relative inline-flex h-6 w-11 shrink-0 cursor-not-allowed rounded-full border-2 border-transparent bg-amber-400 opacity-75"
          title="Deploying…">
        <span class="pointer-events-none inline-block h-5 w-5 translate-x-[10px] transform rounded-full bg-white shadow"></span>
    </span>
@endif
