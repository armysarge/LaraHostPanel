@extends('layouts.app')

@section('title', 'New Credential — LaraHostPanel')
@section('heading', 'New Git Credential')

@section('content')
<div class="mx-auto max-w-2xl">
    <form method="POST" action="{{ route('credentials.store') }}" x-data="{ credType: '{{ old('type', 'token') }}' }">
        @csrf

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
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                       placeholder="e.g. GitHub Deploy Token">
            </div>

            {{-- Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                <div class="mt-2 flex gap-4">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="radio" name="type" value="token"
                               @change="credType = 'token'"
                               {{ old('type', 'token') === 'token' ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Access Token</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="radio" name="type" value="ssh_key"
                               @change="credType = 'ssh_key'"
                               {{ old('type') === 'ssh_key' ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600">
                        <span class="text-sm text-gray-700 dark:text-gray-300">SSH Key</span>
                    </label>
                </div>
            </div>

            {{-- Credential value --}}
            <div>
                <label for="credential" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span x-show="credType === 'token'">Access Token</span>
                    <span x-show="credType === 'ssh_key'" x-cloak>SSH Private Key</span>
                </label>
                <textarea id="credential" name="credential" rows="6" required
                          class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                          :placeholder="credType === 'token' ? 'ghp_xxxxxxxxxxxxxxxxxxxx' : '-----BEGIN OPENSSH PRIVATE KEY-----\n...'"
                >{{ old('credential') }}</textarea>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    <span x-show="credType === 'token'">Paste your personal access token or deploy token.</span>
                    <span x-show="credType === 'ssh_key'" x-cloak>Paste the full private key including BEGIN/END lines.</span>
                    This value is stored encrypted at rest.
                </p>
            </div>

        </div>

        {{-- Actions --}}
        <div class="mt-6 flex items-center justify-end gap-3">
            <a href="{{ route('credentials.index') }}"
               class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
                Create Credential
            </button>
        </div>
    </form>
</div>
@endsection
