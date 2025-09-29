<?php

namespace Sheaf\Cli\Tests;


use Pest\Arch\Concerns\Architectable;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use Architectable;

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            \Sheaf\Cli\ServiceProvider::class,
        ];
    }
}
