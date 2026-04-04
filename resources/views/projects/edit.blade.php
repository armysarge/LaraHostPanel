@extends('layouts.app')

@section('title', 'Edit ' . $project->name . ' — LaraHostPanel')
@section('heading', 'Edit Project')

@section('content')
<div class="mx-auto max-w-2xl">
    <form id="update-form" method="POST" action="{{ route('projects.update', $project) }}"
          x-data="{ sourceType: '{{ old('source_type', $project->source_type) }}' }">
        @csrf
        @method('PUT')

        <div class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">

            {{-- Validation errors --}}
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-800 dark:bg-red-900/30">
                    <ul class="list-inside list-disc space-y-1 text-sm text-red-700 dark:text-red-300">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Project Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $project->name) }}" required
                       class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            </div>

            {{-- Source type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Source Type</label>
                <div class="mt-2 flex gap-4">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="radio" name="source_type" value="local"
                               @change="sourceType = 'local'"
                               {{ old('source_type', $project->source_type) === 'local' ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Local path</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="radio" name="source_type" value="git"
                               @change="sourceType = 'git'"
                               {{ old('source_type', $project->source_type) === 'git' ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Git repository</span>
                    </label>
                </div>
            </div>

            {{-- Local path --}}
            <div x-show="sourceType === 'local'" x-transition>
                <label for="local_path" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Local Path</label>
                <input type="text" id="local_path" name="local_path" value="{{ old('local_path', $project->local_path) }}"
                       class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                       placeholder="/var/www/my-app">
            </div>

            {{-- Git fields --}}
            <div x-show="sourceType === 'git'" x-transition class="space-y-4">
                <div>
                    <label for="git_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Repository URL</label>
                    <input type="text" id="git_url" name="git_url" value="{{ old('git_url', $project->git_url) }}"
                           class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                           placeholder="https://github.com/user/repo.git">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="branch" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Branch</label>
                        <input type="text" id="branch" name="branch" value="{{ old('branch', $project->branch) }}"
                               class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label for="git_credential_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Git Credential</label>
                        <select id="git_credential_id" name="git_credential_id"
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">None (public repo)</option>
                            @foreach ($credentials as $cred)
                                <option value="{{ $cred->id }}" {{ old('git_credential_id', $project->git_credential_id) == $cred->id ? 'selected' : '' }}>
                                    {{ $cred->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Network --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="ip_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bind IP</label>
                    <input type="text" id="ip_address" name="ip_address" value="{{ old('ip_address', $project->ip_address) }}" required
                           class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label for="port" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Port</label>
                    <input type="number" id="port" name="port" value="{{ old('port', $project->port) }}" required min="1" max="65535"
                           class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
            </div>

            {{-- Auto deploy --}}
            <div class="space-y-3" x-data="{ autoDeploy: {{ old('auto_deploy', $project->auto_deploy) ? 'true' : 'false' }} }">
                <label class="flex cursor-pointer items-center gap-3">
                    <button type="button" role="switch" :aria-checked="autoDeploy.toString()"
                            @click="autoDeploy = !autoDeploy"
                            :class="autoDeploy ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <span :class="autoDeploy ? 'translate-x-6' : 'translate-x-1'"
                              class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"></span>
                    </button>
                    <input type="hidden" name="auto_deploy" :value="autoDeploy ? '1' : '0'">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Auto Deploy</span>
                </label>
                <div x-show="autoDeploy" x-transition>
                    <label for="auto_deploy_interval" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Check interval (minutes)</label>
                    <input type="number" id="auto_deploy_interval" name="auto_deploy_interval"
                           value="{{ old('auto_deploy_interval', $project->auto_deploy_interval) }}" min="1" max="1440"
                           class="mt-1 block w-32 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
            </div>

            {{-- Auto start --}}
            <div class="space-y-3" x-data="{ autoStart: {{ old('auto_start', $project->auto_start) ? 'true' : 'false' }} }">
                <label class="flex cursor-pointer items-center gap-3">
                    <button type="button" role="switch" :aria-checked="autoStart.toString()"
                            @click="autoStart = !autoStart"
                            :class="autoStart ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <span :class="autoStart ? 'translate-x-6' : 'translate-x-1'"
                              class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"></span>
                    </button>
                    <input type="hidden" name="auto_start" :value="autoStart ? '1' : '0'">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Auto Start on LaraHostPanel Boot</span>
                </label>
            </div>

        </div>

    </form>

    {{-- Actions --}}
    <div class="mt-6 flex items-center justify-between">
        <form method="POST" action="{{ route('projects.destroy', $project) }}"
              onsubmit="return confirm('Permanently delete {{ addslashes($project->name) }}?')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-600 shadow-sm hover:bg-red-50 dark:border-red-800 dark:bg-transparent dark:text-red-400 dark:hover:bg-red-900/20 transition-colors">
                Delete Project
            </button>
        </form>
        <div class="flex items-center gap-3">
            <a href="{{ route('projects.show', $project) }}"
               class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                Cancel
            </a>
            <button type="submit" form="update-form"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
                Save Changes
            </button>
        </div>
    </div>
</div>
@endsection
