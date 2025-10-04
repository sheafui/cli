<?php

use Illuminate\Console\BufferedConsoleOutput;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

afterAll(function () {
    if (File::exists(resource_path('css/theme.css'))) {
        File::delete(resource_path('css/theme.css'));
    }

    if (File::exists(resource_path('js/utils.js'))) {
        File::delete(resource_path('js/utils.js'));
    }

    if (File::exists(resource_path('js/globals'))) {
        File::deleteDirectory(resource_path('js/globals'));
    }
});

it("Should be able to initialize the package", function () {

    Process::fake();

    File::partialMock();

    File::shouldReceive('replace')
        ->withArgs(function ($path, $content) {
            return str_contains($path, '/js/app.js');
        })
        ->zeroOrMoreTimes()
        ->andReturn(true);

    File::shouldReceive('replace')
        ->withArgs(function ($path, $content) {
            return str_contains($path, '/css/app.css');
        })
        ->zeroOrMoreTimes()
        ->andReturn(true);

    File::shouldReceive('put')
        ->withArgs(function ($path, $content) {
            return str_contains($path, '/css/theme.css');
        })
        ->once();

    // $output = new BufferedConsoleOutput();
    // Artisan::call("sheaf:init", [], $output);
    // dd($output->fetch());

    $command = $this->artisan("sheaf:init")
        ->expectsQuestion('Install Phosphor Icons package?', 'no')
        ->expectsQuestion('Install and setup livewire?', 'yes')
        ->expectsQuestion('Include dark mode theme support?', 'yes')
        ->expectsQuestion('Target CSS file for package assets integration', 'app.css')
        ->expectsQuestion('Theme CSS file name', 'theme.css');

    if (!File::exists(resource_path('js/app.js'))) {
        $command->expectsQuestion('Enter the path (relative to resources/) to your main JS file:', 'js/app.js');
    }

    $command->assertExitCode(0);

    expect(File::exists(resource_path('css/theme.css')))->toBeTrue();
});
