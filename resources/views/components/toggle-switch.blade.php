@props(['status', 'onAction', 'offAction', 'name', 'ajax' => false])

@if ($ajax)
{{--
    AJAX toggle: uses Alpine to POST start/stop without a page reload.
    Shows an amber pulsing state while the request is in flight.
--}}
<div x-data="{
        status: @js($status),
        busy: false,
        async toggle() {
            if (this.busy || this.status === 'deploying') return;
            const url   = this.status === 'running' ? @js($offAction) : @js($onAction);
            const csrf  = document.querySelector('meta[name=\'csrf-token\']').content;
            this.busy   = true;
            try {
                const res  = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    this.status = this.status === 'running' ? 'stopped' : 'running';
                } else {
                    this.status = 'error';
                }
            } catch {
                this.status = 'error';
            } finally {
                this.busy = false;
            }
        }
    }">
    <button type="button"
            @click="toggle()"
            :disabled="busy || status === 'deploying'"
            :title="busy ? 'Please wait…' : (status === 'running' ? 'Running — click to stop' : (status === 'error' ? 'Error — click to retry' : 'Stopped — click to start'))"
            :class="{
                'cursor-wait opacity-75':                          busy,
                'cursor-not-allowed opacity-75':                   status === 'deploying',
                'cursor-pointer':                                  !busy && status !== 'deploying',
                'bg-emerald-500 hover:bg-emerald-600':             !busy && status === 'running',
                'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500': !busy && status === 'stopped',
                'bg-red-500 hover:bg-red-600':                     !busy && status === 'error',
                'bg-amber-400 animate-pulse':                      busy || status === 'deploying',
            }"
            class="relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
        <span class="sr-only" x-text="busy ? 'Please wait…' : (status === 'running' ? 'Stop ' + @js($name) : 'Start ' + @js($name))"></span>
        {{-- Thumb --}}
        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
              :class="{
                  'translate-x-5':      status === 'running',
                  'translate-x-0':      status === 'stopped' || status === 'error',
                  'translate-x-[10px]': busy || status === 'deploying',
              }">
            {{-- Spinner overlay while busy --}}
            <span x-show="busy" class="absolute inset-0 flex items-center justify-center">
                <svg class="h-3 w-3 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </span>
        </span>
    </button>
</div>

@else
{{-- Standard form-POST toggle (used on project show page) --}}
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
@endif
