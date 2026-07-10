<x-layouts.app title="{{ $board->name }} — {{ config('app.name', 'Skylight') }}">
    <livewire:boards.show :board="$board" />
</x-layouts.app>
