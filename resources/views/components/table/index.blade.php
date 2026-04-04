@props(['heading' => null, 'empty' => false])

<div class="w-full rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 overflow-hidden">

    @if ($heading)
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $heading }}</h2>
        </div>
    @endif

    @if ($empty)
        {{ $slot }}
    @else
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-800">
                @isset($head)
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>{{ $head }}</tr>
                    </thead>
                @endisset
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    {{ $slot }}
                </tbody>
            </table>
        </div>
    @endif

    @isset($footer)
        <div class="border-t border-gray-200 dark:border-gray-800">
            {{ $footer }}
        </div>
    @endisset

</div>
