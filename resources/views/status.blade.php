<x-layouts.public :title="'System status — '.config('app.name', 'Skylight')">
    <div class="max-w-2xl mx-auto">
        <div class="rounded-xl p-5 mb-6 flex items-center gap-3 {{ $healthy ? 'bg-green-50 dark:bg-green-900/20 ring-1 ring-green-200 dark:ring-green-900/40' : 'bg-red-50 dark:bg-red-900/20 ring-1 ring-red-200 dark:ring-red-900/40' }}">
            <span class="h-2.5 w-2.5 rounded-full {{ $healthy ? 'bg-green-500' : 'bg-red-500' }}"></span>
            <h1 class="text-lg font-semibold {{ $healthy ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300' }}">
                {{ $healthy ? 'All systems operational' : 'Some systems are experiencing issues' }}
            </h1>
        </div>

        <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($checks as $check)
                <div class="flex items-center justify-between px-5 py-4">
                    <span class="text-sm font-medium">{{ $check['name'] }}</span>
                    <span class="flex items-center gap-2 text-sm {{ $check['healthy'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $check['healthy'] ? 'bg-green-500' : 'bg-red-500' }}"></span>
                        {{ $check['detail'] }}
                    </span>
                </div>
            @endforeach
        </div>

        <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
            Checked {{ now()->toDayDateTimeString() }}. This page reflects the application's own view of its
            dependencies at the moment it was loaded — for continuous uptime monitoring, point an external
            monitor (e.g. UptimeRobot, Better Uptime, Pingdom) at <code class="text-xs bg-gray-100 dark:bg-gray-800 rounded px-1 py-0.5">/up</code>.
        </p>
    </div>
</x-layouts.public>
