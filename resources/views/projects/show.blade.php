@extends('layouts.app')

@section('title', $project->name . ' — LaraHostPanel')
@section('heading', $project->name)

@section('content')
<div class="space-y-6" x-data="deployModal()">

    @if (session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if (session('error'))
        <x-alert type="error">{{ session('error') }}</x-alert>
    @endif

    {{-- Header actions --}}
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('projects.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            All Projects
        </a>
        <span class="text-gray-300 dark:text-gray-700">/</span>
        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $project->name }}</span>
        <div class="ml-auto flex items-center gap-2">
            {{-- Start / Stop --}}
            @if ($project->status === 'running')
                <form method="POST" action="{{ route('projects.stop', $project) }}">
                    @csrf
                    <button type="submit" title="Running — click to stop"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-emerald-500 transition-colors duration-200 ease-in-out hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        <span class="sr-only">Stop {{ $project->name }}</span>
                        <span class="pointer-events-none inline-block h-5 w-5 translate-x-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                    </button>
                </form>
            @elseif (!$project->last_deployed_at)
                <button type="button" @click="openModal()" title="Deploy &amp; start {{ $project->name }}"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-gray-300 dark:bg-gray-600 transition-colors duration-200 ease-in-out hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <span class="sr-only">Deploy {{ $project->name }}</span>
                    <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                </button>
            @elseif ($project->status === 'deploying')
                <span class="relative inline-flex h-6 w-11 shrink-0 cursor-not-allowed rounded-full border-2 border-transparent bg-amber-400 opacity-75" title="Deploying&hellip;">
                    <span class="pointer-events-none inline-block h-5 w-5 translate-x-[10px] transform rounded-full bg-white shadow"></span>
                </span>
            @else
                <x-toggle-switch
                    :status="$project->status"
                    :on-action="route('projects.start', $project)"
                    :off-action="route('projects.stop', $project)"
                    :name="$project->name"
                />
            @endif

            <a href="{{ route('projects.edit', $project) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
            </a>

            @if ($project->source_type === 'local')
                <a href="{{ route('projects.env.edit', $project) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-sm font-medium text-amber-700 shadow-sm hover:bg-amber-50 dark:border-amber-700 dark:bg-gray-800 dark:text-amber-400 dark:hover:bg-gray-700 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    .env
                </a>
            @endif

            {{-- Commands --}}
            <a href="{{ route('projects.commands.index', $project) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Commands
            </a>

            @if ($project->source_type === 'git')
                <form method="POST" action="{{ route('projects.deploy', $project) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-300 bg-white px-3 py-1.5 text-sm font-medium text-indigo-700 shadow-sm hover:bg-indigo-50 dark:border-indigo-700 dark:bg-gray-800 dark:text-indigo-400 dark:hover:bg-gray-700 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Re-deploy
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Details card --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Details</h2>
                </div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr>
                            <td class="w-1/3 px-5 py-3 font-medium text-gray-500 dark:text-gray-400 align-top">Status</td>
                            <td class="px-5 py-3 text-right align-top">
                                @if ($project->status === 'running')
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Running</span>
                                @elseif ($project->status === 'stopped')
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">Stopped</span>
                                @elseif ($project->status === 'deploying')
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Deploying</span>
                                @elseif ($project->status === 'error')
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Error</span>
                                @else
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ ucfirst($project->status) }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Source</td>
                            <td class="px-5 py-3 text-right text-gray-900 dark:text-white">{{ ucfirst($project->source_type) }}</td>
                        </tr>
                        @if ($project->source_type === 'local')
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400 align-top">Path</td>
                                <td class="px-5 py-3 text-right font-mono text-xs text-gray-900 dark:text-white break-all align-top">{{ $project->local_path }}</td>
                            </tr>
                        @else
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400 align-top">Repo</td>
                                <td class="px-5 py-3 text-right font-mono text-xs text-gray-900 dark:text-white break-all align-top">{{ $project->git_url }}</td>
                            </tr>
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Branch</td>
                                <td class="px-5 py-3 text-right font-mono text-gray-900 dark:text-white">{{ $project->branch }}</td>
                            </tr>
                            @if ($project->gitCredential)
                                <tr>
                                    <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Credential</td>
                                    <td class="px-5 py-3 text-right text-gray-900 dark:text-white">{{ $project->gitCredential->name }}</td>
                                </tr>
                            @endif
                        @endif
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Address</td>
                            <td class="px-5 py-3 text-right font-mono text-gray-900 dark:text-white">{{ $project->ip_address }}:{{ $project->port }}</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Auto Deploy</td>
                            <td class="px-5 py-3 text-right text-gray-900 dark:text-white">
                                @if ($project->auto_deploy)
                                    Every {{ $project->auto_deploy_interval }}m
                                @else
                                    <span class="text-gray-400 dark:text-gray-600">Disabled</span>
                                @endif
                            </td>
                        </tr>
                        @if ($project->last_commit_hash)
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Last Commit</td>
                                <td class="px-5 py-3 text-right font-mono text-gray-900 dark:text-white">{{ substr($project->last_commit_hash, 0, 8) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Last Deployed</td>
                            <td class="px-5 py-3 text-right text-gray-900 dark:text-white">
                                {{ $project->last_deployed_at ? $project->last_deployed_at->diffForHumans() : '—' }}
                            </td>
                        </tr>
                        @if ($project->container_id)
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Container</td>
                                <td class="px-5 py-3 text-right font-mono text-xs text-gray-900 dark:text-white">{{ substr($project->container_id, 0, 12) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Deployment logs --}}
        <div class="lg:col-span-2" x-data="{ open: [] }">
            <x-table heading="Deployment Logs" :empty="$project->deploymentLogs->isEmpty()">

                <x-slot:head>
                    <x-table.th class="px-5">Status</x-table.th>
                    <x-table.th class="px-5">Commit</x-table.th>
                    <x-table.th class="px-5">Duration</x-table.th>
                    <x-table.th class="px-5">When</x-table.th>
                    <x-table.th class="px-5 text-right"><span class="sr-only">Output</span></x-table.th>
                </x-slot:head>

                @if ($project->deploymentLogs->isEmpty())
                    <x-table.empty>No deployments yet.</x-table.empty>
                @else
                    @foreach ($project->deploymentLogs as $log)
                        @php $i = $loop->index; @endphp
                        <tr class="align-middle hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">
                            <td class="px-5 py-3">
                                @if ($log->status === 'success')
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Success</span>
                                @elseif ($log->status === 'failed')
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Failed</span>
                                @elseif ($log->status === 'running')
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Running</span>
                                @else
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ ucfirst($log->status) }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                                {{ $log->commit_hash ? substr($log->commit_hash, 0, 8) : '—' }}
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-500 dark:text-gray-400">
                                @if ($log->started_at && $log->completed_at)
                                    {{ $log->started_at->diffInSeconds($log->completed_at) }}s
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $log->created_at->diffForHumans() }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($log->output)
                                    <button @click="open.includes({{ $i }}) ? open.splice(open.indexOf({{ $i }}), 1) : open.push({{ $i }})"
                                            class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                                        <span x-text="open.includes({{ $i }}) ? 'Hide' : 'Logs'">Logs</span>
                                        <svg class="h-3.5 w-3.5 transition-transform" :class="open.includes({{ $i }}) && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @if ($log->output)
                            <tr x-show="open.includes({{ $i }})" x-cloak>
                                <td colspan="5" class="px-5 pb-4 pt-0 bg-gray-950">
                                    <pre class="overflow-x-auto rounded-lg bg-gray-950 p-4 font-mono text-xs leading-relaxed text-gray-100">{{ $log->output }}</pre>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @endif

            </x-table>
        </div>

    </div>

    {{-- ================================================================ --}}
    {{-- Deploy & Setup Modal                                              --}}
    {{-- ================================================================ --}}
    <x-modal open="open" close-expr="phase === 'setup' && (open = false)">

            {{-- Header --}}
            <div class="flex items-center gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-900/50">
                    <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h2 class="truncate text-base font-semibold text-gray-900 dark:text-white">Deploy {{ $project->name }}</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Choose setup steps to run after deployment</p>
                </div>
            </div>

            {{-- Body: setup phase --}}
            <div x-show="phase === 'setup'" class="max-h-[60vh] overflow-y-auto px-6 py-5 space-y-5">
                <template x-for="(group, name) in groups" :key="name">
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500" x-text="name"></p>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="preset in group" :key="preset.label">
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border px-3 py-2 transition-colors"
                                       :class="preset.checked
                                           ? 'border-indigo-400 bg-indigo-50 dark:border-indigo-600 dark:bg-indigo-900/30'
                                           : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                    <input type="checkbox" x-model="preset.checked"
                                           class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300" x-text="preset.label"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Body: running phase --}}
            <div x-show="phase === 'running'" class="px-6 py-5 space-y-4">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 shrink-0 animate-spin text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="currentStep"></span>
                </div>
                <div class="space-y-2">
                    <template x-for="step in steps" :key="step.label">
                        <div class="flex items-center gap-2.5 text-sm">
                            <template x-if="step.status === 'done'">
                                <svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                            <template x-if="step.status === 'running'">
                                <svg class="h-4 w-4 shrink-0 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <template x-if="step.status === 'pending'">
                                <svg class="h-4 w-4 shrink-0 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <circle cx="12" cy="12" r="9" />
                                </svg>
                            </template>
                            <template x-if="step.status === 'failed'">
                                <svg class="h-4 w-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </template>
                            <span x-text="step.label"
                                  :class="step.status === 'pending' ? 'text-gray-400 dark:text-gray-600' : 'text-gray-700 dark:text-gray-300'"></span>
                        </div>
                    </template>
                </div>
                <div x-show="logOutput" class="max-h-44 overflow-y-auto rounded-lg bg-gray-950 p-3">
                    <pre class="whitespace-pre-wrap font-mono text-xs leading-relaxed text-gray-100" x-text="logOutput"></pre>
                </div>
            </div>

            {{-- Body: done phase --}}
            <div x-show="phase === 'done'" class="px-6 py-10 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                    <svg class="h-7 w-7 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Deployment complete!</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $project->name }} is now running.</p>
            </div>

            {{-- Body: error phase --}}
            <div x-show="phase === 'error'" class="px-6 py-10 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40">
                    <svg class="h-7 w-7 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Deployment failed</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="errorMsg"></p>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                <template x-if="phase === 'setup'">
                    <div class="flex w-full items-center justify-end gap-3">
                        <button type="button" @click="open = false"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            Skip for now
                        </button>
                        <button type="button" @click="go()"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-indigo-700">
                            Deploy Now
                        </button>
                    </div>
                </template>
                <template x-if="phase === 'done' || phase === 'error'">
                    <button type="button" @click="open = false; window.location.reload()"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-indigo-700">
                        Done
                    </button>
                </template>
            </div>
    </x-modal>

</div>

<script>
function deployModal() {
    return {
        open: {{ session('first_deploy') ? 'true' : 'false' }},
        phase: 'setup',
        currentStep: '',
        steps: [],
        logOutput: '',
        errorMsg: '',
        presets: [
            { group: 'Setup', label: 'Composer Install', command: 'composer install --no-interaction --prefer-dist --optimize-autoloader', checked: false },
            { group: 'Setup', label: 'Migrate',          command: 'php artisan migrate --force',   checked: false },
            { group: 'Setup', label: 'DB Seed',          command: 'php artisan db:seed --force',   checked: false },
            { group: 'Setup', label: 'Storage Link',     command: 'php artisan storage:link',      checked: false },
            { group: 'Cache', label: 'Config Cache',     command: 'php artisan config:cache',      checked: false },
            { group: 'Cache', label: 'Route Cache',      command: 'php artisan route:cache',       checked: false },
            { group: 'Cache', label: 'View Clear',       command: 'php artisan view:clear',        checked: false },
            { group: 'Cache', label: 'Optimize',         command: 'php artisan optimize',          checked: false },
            { group: 'Build', label: 'NPM Install',      command: 'npm ci',                        checked: false },
            { group: 'Build', label: 'NPM Build',        command: 'npm run build',                 checked: false },
        ],
        get groups() {
            const g = {};
            this.presets.forEach(p => { if (!g[p.group]) g[p.group] = []; g[p.group].push(p); });
            return g;
        },
        openModal() {
            this.phase   = 'setup';
            this.steps   = [];
            this.logOutput = '';
            this.errorMsg  = '';
            this.open    = true;
        },
        async go() {
            this.phase = 'running';
            const csrf     = document.querySelector('meta[name="csrf-token"]').content;
            const selected = this.presets.filter(p => p.checked);

            this.steps = [
                { label: 'Starting project', status: 'running' },
                ...selected.map(p => ({ label: p.label, status: 'pending' })),
            ];

            // 1. Start / deploy the project
            this.currentStep = 'Starting project\u2026';
            try {
                const res = await fetch('{{ route("projects.start", $project) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    this.steps[0].status = 'failed';
                    this.phase    = 'error';
                    this.errorMsg = data.message || 'Failed to start the project. Check deployment logs.';
                    return;
                }
            } catch {
                this.steps[0].status = 'failed';
                this.phase    = 'error';
                this.errorMsg = 'Network error while starting the project.';
                return;
            }
            this.steps[0].status = 'done';

            // 2. Run selected commands sequentially
            for (let i = 0; i < selected.length; i++) {
                const preset  = selected[i];
                const stepIdx = i + 1;
                this.steps[stepIdx].status = 'running';
                this.currentStep = preset.label + '\u2026';
                this.logOutput   = '';

                let run;
                try {
                    const runRes = await fetch('{{ route("projects.commands.run", $project) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ command: preset.command, label: preset.label }),
                    });
                    if (!runRes.ok) { this.steps[stepIdx].status = 'failed'; continue; }
                    run = await runRes.json();
                } catch {
                    this.steps[stepIdx].status = 'failed';
                    continue;
                }

                // Poll until the command finishes
                let polling = true;
                while (polling) {
                    await new Promise(r => setTimeout(r, 1500));
                    try {
                        const outRes = await fetch(`/projects/{{ $project->id }}/commands/${run.id}/output`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        const data = await outRes.json();
                        this.logOutput = data.output;
                        if (data.done) {
                            this.steps[stepIdx].status = data.status === 'success' ? 'done' : 'failed';
                            polling = false;
                        }
                    } catch {
                        this.steps[stepIdx].status = 'failed';
                        polling = false;
                    }
                }
            }

            this.phase       = 'done';
            this.currentStep = 'All done!';
        },
    };
}
</script>
@endsection
