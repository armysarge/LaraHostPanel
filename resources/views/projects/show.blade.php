@extends('layouts.app')

@section('title', $project->name . ' — LaraHostPanel')
@section('heading', $project->name)

@section('content')
<div class="space-y-6">

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
            <x-toggle-switch
                :status="$project->status"
                :on-action="route('projects.start', $project)"
                :off-action="route('projects.stop', $project)"
                :name="$project->name"
            />

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
</div>
@endsection
