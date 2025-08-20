<?php


namespace Fluxtor\Cli\Contracts;

use Illuminate\Support\Collection;

interface InstallationStrategyInterface
{
    /**
     * @param Collection $componentResources
     * @return int
     */
    public function execute(Collection $componentResources): int;
}
