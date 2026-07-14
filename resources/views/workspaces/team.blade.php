<x-layouts.app title="Team — {{ config('app.name', 'Skylight') }}">
    <livewire:workspaces.team :workspace="$workspace" :key="'team-'.$workspace->id" />
</x-layouts.app>
