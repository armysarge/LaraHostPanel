@extends('layouts.app')

@section('title', $project->name . ' — .env — LaraHostPanel')
@section('heading', $project->name)

@section('content')
<div class="space-y-6">

    @if (session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if (session('error'))
        <x-alert type="error">{{ session('error') }}</x-alert>
    @endif

    {{-- Breadcrumb / actions --}}
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('projects.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            All Projects
        </a>
        <span class="text-gray-300 dark:text-gray-700">/</span>
        <a href="{{ route('projects.show', $project) }}"
           class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
            {{ $project->name }}
        </a>
        <span class="text-gray-300 dark:text-gray-700">/</span>
        <span class="text-sm font-medium text-gray-900 dark:text-white">.env</span>
    </div>

    {{-- Editor card --}}
    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">.env</h2>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $envPath }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    Contains secrets
                </span>
            </div>
        </div>

        <form method="POST" action="{{ route('projects.env.update', $project) }}">
            @csrf
            @method('PUT')

            <div class="relative">
                {{-- Line numbers are rendered as a visual aid via CSS counter --}}
                <textarea
                    name="contents"
                    id="env-editor"
                    rows="30"
                    spellcheck="false"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    class="w-full resize-y bg-gray-950 px-5 py-4 font-mono text-sm leading-6 text-gray-100 focus:outline-none"
                >{{ $contents }}</textarea>
            </div>

            <div class="flex items-center justify-between border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                <a href="{{ route('projects.show', $project) }}"
                   class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Save .env
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
