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

</div>
@endsection
