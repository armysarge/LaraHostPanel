@extends('layouts.app')

@section('title', 'Dashboard — LaraHostPanel')
@section('heading', 'Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Stats row --}}
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $stats = [
                ['label' => 'Total Projects', 'value' => $stats['total'], 'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z', 'color' => 'indigo'],
                ['label' => 'Running', 'value' => $stats['running'], 'icon' => 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z', 'color' => 'emerald'],
                ['label' => 'Stopped', 'value' => $stats['stopped'], 'icon' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0z M10 15V9', 'color' => 'amber'],
                ['label' => 'Errors', 'value' => $stats['errors'], 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'color' => 'red'],
            ];
        @endphp

        @foreach ($stats as $stat)
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stat['value'] }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-{{ $stat['color'] }}-50 dark:bg-{{ $stat['color'] }}-900/30">
                        <svg class="h-5 w-5 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" />
                        </svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Recent activity / Projects table placeholder --}}
    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Recent Projects</h2>
            <a href="{{ route('projects.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                View all
            </a>
        </div>
        @if ($recentProjects->isEmpty())
        <div class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
            <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">No projects yet</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding your first Laravel project.</p>
            <a href="{{ route('projects.create') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Add Project
            </a>
        </div>
        @else
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Address</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Last Deployed</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($recentProjects as $project)
                    @php
                        $statusClasses = [
                            'running'   => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                            'stopped'   => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                            'deploying' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            'error'     => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                        ];
                        $cls = $statusClasses[$project->status] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                        <td class="px-6 py-3">
                            <a href="{{ route('projects.show', $project) }}" class="font-medium text-gray-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400">
                                {{ $project->name }}
                            </a>
                        </td>
                        <td class="px-6 py-3 font-mono text-sm text-gray-500 dark:text-gray-400">
                            {{ $project->ip_address }}:{{ $project->port }}
                        </td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $cls }}">
                                {{ ucfirst($project->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400">
                            {{ $project->last_deployed_at ? $project->last_deployed_at->diffForHumans() : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
