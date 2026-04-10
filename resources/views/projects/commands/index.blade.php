@extends('layouts.app')

@section('title', 'Commands — ' . $project->name . ' — LaraHostPanel')
@section('heading', 'Command Runner')

@section('content')
<div x-data="commandRunner({{ session('latest_run_id', 'null') }}, '{{ csrf_token() }}')" class="space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-1.5 text-sm">
        <a href="{{ route('projects.index') }}"
           class="text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            Projects
        </a>
        <span class="text-gray-300 dark:text-gray-700">/</span>
        <a href="{{ route('projects.show', $project) }}"
           class="text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            {{ $project->name }}
        </a>
        <span class="text-gray-300 dark:text-gray-700">/</span>
        <span class="font-medium text-gray-900 dark:text-white">Commands</span>
    </div>

    {{-- Flash alerts --}}
    @if (session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if (session('error'))
        <x-alert type="error">{{ session('error') }}</x-alert>
    @endif

    {{-- Command Input Card --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Run a Command</h2>

        {{-- Preset buttons --}}
        <div class="mb-4">
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">
                Artisan &amp; common presets — click to fill
            </p>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($presets as $preset)
                    <button type="button"
                            @click="setCommand('{{ addslashes($preset['command']) }}', '{{ addslashes($preset['label']) }}')"
                            class="rounded-md border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 transition-colors hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-indigo-600 dark:hover:bg-indigo-900/30 dark:hover:text-indigo-400">
                        {{ $preset['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Command form --}}
        <form method="POST" action="{{ route('projects.commands.run', $project) }}">
            @csrf
            <input type="hidden" name="label" :value="label">

            <div class="flex gap-2">
                <input type="text" name="command"
                       x-model="command"
                       required
                       placeholder="e.g. php artisan migrate --force"
                       class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm shadow-sm placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500">
                <button type="submit"
                        class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                    </svg>
                    Run
                </button>
            </div>

            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Commands run in
                <code class="rounded bg-gray-100 px-1 py-0.5 dark:bg-gray-800">
                    @if ($project->source_type === 'git')
                        storage/app/deployments/{{ $project->id }}
                    @else
                        {{ $project->local_path }}
                    @endif
                </code>
                — multiple commands and deployments can run concurrently.
            </p>
        </form>
    </div>

    {{-- Runs --}}
    @forelse ($runs as $run)
        <div id="run-{{ $run->id }}"
             x-init="initRun({{ $run->id }}, '{{ $run->status }}', {{ $run->pid ?? 'null' }}, @js($run->currentOutput()))"
             class="overflow-hidden rounded-xl border transition-colors"
             :class="statusBorderClass(getStatus({{ $run->id }}))">

            {{-- Run header --}}
            <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-800/60">

                {{-- Spinner while running --}}
                <template x-if="getStatus({{ $run->id }}) === 'running'">
                    <svg class="h-4 w-4 shrink-0 animate-spin text-yellow-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </template>
                <template x-if="getStatus({{ $run->id }}) !== 'running'">
                    <span class="inline-flex h-2 w-2 shrink-0 rounded-full"
                          :class="{
                              'bg-green-500': getStatus({{ $run->id }}) === 'success',
                              'bg-red-500':   getStatus({{ $run->id }}) === 'failed',
                              'bg-gray-400':  getStatus({{ $run->id }}) === 'pending',
                          }"></span>
                </template>

                {{-- Command text --}}
                <div class="min-w-0 flex-1">
                    @if ($run->label)
                        <span class="mr-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $run->label }}:</span>
                    @endif
                    <code class="break-all text-sm text-gray-900 dark:text-white">{{ $run->command }}</code>
                </div>

                {{-- Meta + actions --}}
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        {{ $run->started_at?->diffForHumans() }}
                    </span>

                    @if ($run->completed_at && $run->started_at)
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            ({{ number_format($run->started_at->diffInSeconds($run->completed_at), 0) }}s)
                        </span>
                    @endif

                    {{-- Status badge --}}
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                          :class="{
                              'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400':    getStatus({{ $run->id }}) === 'success',
                              'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400':            getStatus({{ $run->id }}) === 'failed',
                              'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': getStatus({{ $run->id }}) === 'running',
                              'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400':           getStatus({{ $run->id }}) === 'pending',
                          }"
                          x-text="getStatus({{ $run->id }})">{{ $run->status }}</span>

                    {{-- Stop button (only for running) --}}
                    <button x-show="getStatus({{ $run->id }}) === 'running'"
                            x-cloak
                            @click="stopRun({{ $run->id }})"
                            class="inline-flex items-center gap-1 rounded border border-red-200 bg-white px-2 py-0.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50 dark:border-red-800 dark:bg-transparent dark:text-red-400 dark:hover:bg-red-900/20">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6 6h12v12H6z"/>
                        </svg>
                        Stop
                    </button>

                    {{-- Toggle output --}}
                    <button @click="toggleOutput({{ $run->id }})"
                            class="inline-flex items-center gap-1 text-xs text-indigo-600 transition-colors hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                        <span x-text="isOpen({{ $run->id }}) ? 'Hide' : 'Output'">Output</span>
                        <svg class="h-3.5 w-3.5 transition-transform" :class="isOpen({{ $run->id }}) && 'rotate-180'"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Terminal output panel --}}
            <div x-show="isOpen({{ $run->id }})"
                 x-transition:enter="transition-opacity duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100">
                <pre class="max-h-[28rem] overflow-y-auto bg-gray-950 p-4 font-mono text-xs leading-relaxed text-green-400 whitespace-pre-wrap break-words"
                     x-ref="term{{ $run->id }}"
                     x-text="getOutput({{ $run->id }}) ?? '(waiting for output…)'"></pre>
                {{-- Auto-scroll helper for running runs --}}
                <span x-effect="if (getStatus({{ $run->id }}) === 'running' && isOpen({{ $run->id }})) { const el = $refs['term{{ $run->id }}']; if (el) el.scrollTop = el.scrollHeight; }"></span>
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-gray-200 py-14 text-center text-sm text-gray-400 dark:border-gray-800 dark:text-gray-500">
            No commands have been run yet. Use the presets above or type a custom command.
        </div>
    @endforelse

    {{-- Pagination --}}
    @if ($runs->hasPages())
        <div>{{ $runs->links() }}</div>
    @endif

</div>

<script>
function commandRunner(preOpenId, csrfToken) {
    return {
        command:   '',
        label:     '',
        openRuns:  preOpenId ? [preOpenId] : [],
        outputs:   {},
        statuses:  {},
        intervals: {},

        setCommand(cmd, lbl) {
            this.command = cmd;
            this.label   = lbl;
        },

        initRun(id, status, pid, initialOutput) {
            this.statuses = { ...this.statuses, [id]: status };

            if (initialOutput) {
                this.outputs = { ...this.outputs, [id]: initialOutput };
            }

            if (status === 'running') {
                if (!this.openRuns.includes(id)) {
                    this.openRuns.push(id);
                }
                this.poll(id);
            }
        },

        poll(id) {
            if (this.intervals[id]) return;
            this.fetchOutput(id);
            this.intervals[id] = setInterval(() => this.fetchOutput(id), 1500);
        },

        fetchOutput(id) {
            fetch(`/projects/{{ $project->id }}/commands/${id}/output`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(r => r.json())
                .then(data => {
                    this.outputs  = { ...this.outputs,  [id]: data.output };
                    this.statuses = { ...this.statuses, [id]: data.status };
                    if (data.done) {
                        clearInterval(this.intervals[id]);
                        delete this.intervals[id];
                    }
                })
                .catch(() => {});
        },

        stopRun(id) {
            fetch(`/projects/{{ $project->id }}/commands/${id}/stop`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':    csrfToken,
                    'Content-Type':    'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }).catch(() => {});
        },

        getOutput(id) {
            return this.outputs[id] !== undefined ? this.outputs[id] : null;
        },

        getStatus(id) {
            return this.statuses[id] || 'pending';
        },

        isOpen(id) {
            return this.openRuns.includes(id);
        },

        toggleOutput(id) {
            if (this.isOpen(id)) {
                this.openRuns = this.openRuns.filter(r => r !== id);
            } else {
                this.openRuns.push(id);
                if (this.getStatus(id) === 'running' && !this.intervals[id]) {
                    this.poll(id);
                }
            }
        },

        statusBorderClass(status) {
            const map = {
                success: 'border-green-200 dark:border-green-800',
                failed:  'border-red-200 dark:border-red-800',
                running: 'border-yellow-300 dark:border-yellow-700',
                pending: 'border-gray-200 dark:border-gray-800',
            };
            return map[status] || map.pending;
        },
    };
}
</script>
@endsection
