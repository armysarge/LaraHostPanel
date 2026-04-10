@extends('layouts.app')

@section('title', 'Deployments — LaraHostPanel')
@section('heading', 'Deployments')

@section('content')
<div class="space-y-6">

    @if (session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if (session('error'))
        <x-alert type="error">{{ session('error') }}</x-alert>
    @endif

    {{-- Filters --}}
    <form method="GET" action="{{ route('deployments.index') }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label for="filter-project" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Project</label>
            <select id="filter-project" name="project"
                    class="mt-1 block w-48 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">All Projects</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" {{ request('project') == $project->id ? 'selected' : '' }}>
                        {{ $project->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select id="filter-status" name="status"
                    class="mt-1 block w-40 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">All</option>
                @foreach (['pending', 'running', 'success', 'failed'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit"
                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            Filter
        </button>
        @if (request('project') || request('status'))
            <a href="{{ route('deployments.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                Clear filters
            </a>
        @endif
    </form>

    {{-- Header row --}}
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $deployments->total() }} deployment{{ $deployments->total() !== 1 ? 's' : '' }}
        </p>
    </div>

    {{-- Deployments table --}}
    <div x-data="{ open: [] }">
        <x-table :empty="$deployments->isEmpty()">

            <x-slot:head>
                <x-table.th class="px-6">Project</x-table.th>
                <x-table.th class="px-6">Status</x-table.th>
                <x-table.th class="px-6">Commit</x-table.th>
                <x-table.th class="px-6">Duration</x-table.th>
                <x-table.th class="px-6">When</x-table.th>
                <x-table.th class="px-6 text-right"><span class="sr-only">Output</span></x-table.th>
            </x-slot:head>

            @if ($deployments->isEmpty())
                <x-table.empty>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">No deployments yet</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Deployments will appear here when you start or deploy a project.</p>
                </x-table.empty>
            @else
                @foreach ($deployments as $deployment)
                    @php $i = $loop->index; @endphp
                    <tr class="align-middle hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">
                        {{-- Project --}}
                        <td class="px-6 py-3">
                            @if ($deployment->project)
                                <a href="{{ route('projects.show', $deployment->project) }}"
                                   class="font-medium text-gray-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400">
                                    {{ $deployment->project->name }}
                                </a>
                            @else
                                <span class="text-gray-400 dark:text-gray-600 italic">Deleted</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="px-6 py-3">
                            @if ($deployment->status === 'success')
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Success</span>
                            @elseif ($deployment->status === 'failed')
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Failed</span>
                            @elseif ($deployment->status === 'running')
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Running</span>
                            @else
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ ucfirst($deployment->status) }}</span>
                            @endif
                        </td>

                        {{-- Commit --}}
                        <td class="px-6 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                            {{ $deployment->commit_hash ? substr($deployment->commit_hash, 0, 8) : '—' }}
                        </td>

                        {{-- Duration --}}
                        <td class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400">
                            @if ($deployment->started_at && $deployment->completed_at)
                                {{ $deployment->started_at->diffInSeconds($deployment->completed_at) }}s
                            @else
                                —
                            @endif
                        </td>

                        {{-- When --}}
                        <td class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ $deployment->created_at->diffForHumans() }}
                        </td>

                        {{-- Output toggle --}}
                        <td class="px-6 py-3 text-right">
                            <div class="inline-flex items-center gap-3">
                                @if ($deployment->project && $deployment->project->source_type === 'git')
                                    <form method="POST" action="{{ route('projects.deploy', $deployment->project) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors"
                                                title="Re-deploy this project">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Re-deploy
                                        </button>
                                    </form>
                                @endif
                                @if ($deployment->output)
                                    <button @click="open.includes({{ $i }}) ? open.splice(open.indexOf({{ $i }}), 1) : open.push({{ $i }})"
                                            class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                                        <span x-text="open.includes({{ $i }}) ? 'Hide' : 'Logs'">Logs</span>
                                        <svg class="h-3.5 w-3.5 transition-transform" :class="open.includes({{ $i }}) && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @if ($deployment->output)
                        <tr x-show="open.includes({{ $i }})" x-cloak>
                            <td colspan="6" class="px-6 pb-4 pt-0 bg-gray-950">
                                <pre class="overflow-x-auto rounded-lg bg-gray-950 p-4 font-mono text-xs leading-relaxed text-gray-100">{{ $deployment->output }}</pre>
                            </td>
                        </tr>
                    @endif
                @endforeach
            @endif

            @if ($deployments->hasPages())
                <x-slot:footer>
                    <div class="px-6 py-4">{{ $deployments->links() }}</div>
                </x-slot:footer>
            @endif

        </x-table>
    </div>

</div>
@endsection
