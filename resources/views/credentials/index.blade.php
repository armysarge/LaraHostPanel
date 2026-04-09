@extends('layouts.app')

@section('title', 'Credentials — LaraHostPanel')
@section('heading', 'Git Credentials')

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
            {{ $credentials->total() }} credential{{ $credentials->total() !== 1 ? 's' : '' }}
        </p>
        <a href="{{ route('credentials.create') }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            New Credential
        </a>
    </div>

    {{-- Credentials table --}}
    <x-table :empty="$credentials->isEmpty()">

        <x-slot:head>
            <x-table.th class="px-6">Name</x-table.th>
            <x-table.th class="px-6">Type</x-table.th>
            <x-table.th class="px-6">Used By</x-table.th>
            <x-table.th class="px-6">Created</x-table.th>
            <x-table.th class="px-6 text-right"><span class="sr-only">Actions</span></x-table.th>
        </x-slot:head>

        @if ($credentials->isEmpty())
            <x-table.empty>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">No credentials yet</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add SSH keys or tokens for private Git repositories.</p>
                <a href="{{ route('credentials.create') }}"
                   class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Credential
                </a>
            </x-table.empty>
        @else
            @foreach ($credentials as $credential)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">

                    {{-- Name --}}
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $credential->name }}</span>
                        </div>
                    </td>

                    {{-- Type --}}
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        @if ($credential->type === 'ssh_key')
                            <span class="inline-flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                SSH Key
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                Token
                            </span>
                        @endif
                    </td>

                    {{-- Used By --}}
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ $credential->projects_count }} project{{ $credential->projects_count !== 1 ? 's' : '' }}
                    </td>

                    {{-- Created --}}
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ $credential->created_at->diffForHumans() }}
                    </td>

                    {{-- Actions --}}
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-3">
                            {{-- Edit --}}
                            <a href="{{ route('credentials.edit', $credential) }}"
                               class="rounded p-1 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                               title="Edit">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>

                            {{-- Delete --}}
                            <form method="POST" action="{{ route('credentials.destroy', $credential) }}"
                                  onsubmit="return confirm('Delete credential &quot;{{ $credential->name }}&quot;?{{ $credential->projects_count > 0 ? ' It is used by ' . $credential->projects_count . ' project(s).' : '' }}')">
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

        @if ($credentials->hasPages())
            <x-slot:footer>
                <div class="px-6 py-4">{{ $credentials->links() }}</div>
            </x-slot:footer>
        @endif

    </x-table>

</div>
@endsection
