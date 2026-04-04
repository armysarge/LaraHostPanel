@extends('layouts.app')

@section('title', 'Projects — LaraHostPanel')
@section('heading', 'Projects')

@section('content')
<div class="space-y-6">

    @if (session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if (session('error'))
        <x-alert type="error">{{ session('error') }}</x-alert>
    @endif

    {{-- Header row --}}
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $projects->total() }} project{{ $projects->total() !== 1 ? 's' : '' }}
        </p>
        <a href="{{ route('projects.create') }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            New Project
        </a>
    </div>

    {{-- Projects table --}}
    <x-table :empty="$projects->isEmpty()">

        <x-slot:head>
            <x-table.th class="px-6">Name</x-table.th>
            <x-table.th class="px-6">Source</x-table.th>
            <x-table.th class="px-6">Address</x-table.th>
            <x-table.th class="px-6">Status</x-table.th>
            <x-table.th class="px-6">Last Deployed</x-table.th>
            <x-table.th class="px-6 text-right"><span class="sr-only">Actions</span></x-table.th>
        </x-slot:head>

        @if ($projects->isEmpty())
            <x-table.empty>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">No projects yet</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding your first project.</p>
                <a href="{{ route('projects.create') }}"
                   class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Project
                </a>
            </x-table.empty>
        @else
            @foreach ($projects as $project)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">

                    {{-- Name --}}
                    <td class="px-6 py-4">
                        <a href="{{ route('projects.show', $project) }}"
                           class="font-medium text-gray-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400">
                            {{ $project->name }}
                        </a>
                    </td>

                    {{-- Source --}}
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        @if ($project->source_type === 'git')
                            <span class="inline-flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                                Git / {{ $project->branch }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                                Local
                            </span>
                        @endif
                    </td>

                    {{-- Address --}}
                    <td class="px-6 py-4 font-mono text-sm text-gray-600 dark:text-gray-300">
                        {{ $project->ip_address }}:{{ $project->port }}
                    </td>

                    {{-- Status badge --}}
                    <td class="px-6 py-4">
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

                    {{-- Last deployed --}}
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ $project->last_deployed_at ? $project->last_deployed_at->diffForHumans() : '—' }}
                    </td>

                    {{-- Actions --}}
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-3">

                            {{-- Start / Stop toggle switch --}}
                            <x-toggle-switch
                                :status="$project->status"
                                :on-action="route('projects.start', $project)"
                                :off-action="route('projects.stop', $project)"
                                :name="$project->name"
                            />

                            {{-- View --}}
                            <a href="{{ route('projects.show', $project) }}"
                               class="rounded p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                               title="View">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>

                            {{-- Edit --}}
                            <a href="{{ route('projects.edit', $project) }}"
                               class="rounded p-1 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                               title="Edit">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>

                            {{-- Delete --}}
                            <form method="POST" action="{{ route('projects.destroy', $project) }}"
                                  onsubmit="return confirm('Delete {{ addslashes($project->name) }}? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="rounded p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                        title="Delete">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>

                        </div>
                    </td>

                </tr>
            @endforeach
        @endif

        @if ($projects->hasPages())
            <x-slot:footer>
                <div class="px-6 py-4">{{ $projects->links() }}</div>
            </x-slot:footer>
        @endif

    </x-table>

</div>
@endsection
