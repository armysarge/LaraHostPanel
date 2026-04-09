@extends('layouts.app')

@section('title', 'Deployment #' . $deployment->id . ' — LaraHostPanel')
@section('heading', 'Deployment #' . $deployment->id)

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('deployments.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            All Deployments
        </a>
        <span class="text-gray-300 dark:text-gray-700">/</span>
        <span class="text-sm font-medium text-gray-900 dark:text-white">#{{ $deployment->id }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Details card --}}
        <div class="lg:col-span-1">
            <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Details</h2>
                </div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr>
                            <td class="w-1/3 px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Project</td>
                            <td class="px-5 py-3 text-right text-gray-900 dark:text-white">
                                @if ($deployment->project)
                                    <a href="{{ route('projects.show', $deployment->project) }}"
                                       class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        {{ $deployment->project->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400 italic">Deleted</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Status</td>
                            <td class="px-5 py-3 text-right">
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
                        </tr>
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Commit</td>
                            <td class="px-5 py-3 text-right font-mono text-xs text-gray-900 dark:text-white">
                                {{ $deployment->commit_hash ? substr($deployment->commit_hash, 0, 8) : '—' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Started</td>
                            <td class="px-5 py-3 text-right text-gray-900 dark:text-white">
                                {{ $deployment->started_at ? $deployment->started_at->format('M j, Y H:i:s') : '—' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Completed</td>
                            <td class="px-5 py-3 text-right text-gray-900 dark:text-white">
                                {{ $deployment->completed_at ? $deployment->completed_at->format('M j, Y H:i:s') : '—' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Duration</td>
                            <td class="px-5 py-3 text-right text-gray-900 dark:text-white">
                                @if ($deployment->started_at && $deployment->completed_at)
                                    {{ $deployment->started_at->diffInSeconds($deployment->completed_at) }}s
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Created</td>
                            <td class="px-5 py-3 text-right text-gray-900 dark:text-white">
                                {{ $deployment->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Output --}}
        <div class="lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Output</h2>
                </div>
                @if ($deployment->output)
                    <div class="bg-gray-950 p-5">
                        <pre class="overflow-x-auto font-mono text-xs leading-relaxed text-gray-100">{{ $deployment->output }}</pre>
                    </div>
                @else
                    <div class="px-6 py-12 text-center">
                        <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No output recorded for this deployment.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
