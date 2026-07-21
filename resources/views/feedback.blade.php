<x-layouts.public :title="'Feedback — '.config('app.name', 'Skylight')">
    <div class="max-w-lg mx-auto">
        <h1 class="text-2xl font-semibold">Feedback</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Found a bug, have a feature request, or just want to tell us something? This goes straight to the team.
        </p>

        <div class="mt-6 rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
            <iframe
                src="https://capture.gracesoft.dev/form/frm_9003b576689d16fe08b8b2affe2cc301?surface=none"
                title="Feedback form"
                width="100%"
                height="720"
                style="display:block;width:100%;max-width:100%;border:0;background:transparent;"
                loading="lazy"
            ></iframe>
        </div>
    </div>
</x-layouts.public>
