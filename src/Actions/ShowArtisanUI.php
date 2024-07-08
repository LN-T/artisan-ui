<?php

namespace Lorisleiva\ArtisanUI\Actions;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Lorisleiva\ArtisanUI\ArtisanUI;

class ShowArtisanUI
{
    public function __invoke(ArtisanUI $artisanUI): View
    {
        return view('artisan-ui::home')->with('commands', $this->getFilteredCommands($artisanUI));
    }

    private function getFilteredCommands($artisanUI)
    {
        $commands = $artisanUI->allGroupedByNamespace();
        $includedNamespaces = config('artisan-ui.filters.namespaces.included');
        $excludedNamespaces = config('artisan-ui.filters.namespaces.excluded');
        $includedCommands = config('artisan-ui.filters.commands.included');
        $excludedCommands = config('artisan-ui.filters.commands.excluded');

        return $this->filterCollection($commands, $includedNamespaces, $excludedNamespaces,
            $includedCommands, $excludedCommands
        );
    }

    private function filterCollection(
        Collection $collection, array $includedNamespaces = [], array $excludedNamespaces = [],
        array $includedCommands = [], array $excludedCommands = []
    )
    {
        $collection = $collection->map(fn($commands) => $commands->when(!empty($includedCommands), function ($commands) use ($includedCommands) {
            return $commands->filter(function ($command) use ($includedCommands) {
                return in_array($command->getArtisanCommand()::class, $includedCommands);
            });
        })
            ->when(!empty($excludedCommands), function ($commands) use ($excludedCommands) {
                return $commands->reject(function ($command) use ($excludedCommands) {
                    return in_array($command->getArtisanCommand()::class, $excludedCommands);
                });
            }));

        return $collection->when(!empty($includedNamespaces), fn($collection) => $collection->filter(fn($commands, $key) => in_array($key, $includedNamespaces)))
            ->when(!empty($excludedNamespaces), function ($collection) use ($excludedNamespaces) {
                return $collection->reject(fn($commands, $key) => in_array($key, $excludedNamespaces));
            })
            ->reject(fn($value, $key) => $value instanceof Collection && $value->isEmpty());
    }
}
