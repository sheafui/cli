<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    File::ensureDirectoryExists(resource_path('css'));
    File::ensureDirectoryExists(resource_path('js'));
});

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

    $command = $this->artisan("sheaf:init")
        ->expectsQuestion('Install Phosphor Icons package?', 'no')
        ->expectsQuestion('Install and setup livewire?', 'yes')
        ->expectsQuestion('Include dark mode theme support?', 'yes')
        ->expectsQuestion('Target CSS file for package assets integration', 'app.css')
        ->expectsQuestion('Theme CSS file name', 'theme.css');

    $mainJsFile = resource_path("js/app.js");
    if (!File::exists($mainJsFile)) {
        $command->expectsQuestion('Enter the path (relative to resources/) to your main JS file:', 'js/app.js');
        $command->expectsQuestion("The $mainJsFile is not exists, do you want to create it?", true);
    }

    $command->assertExitCode(0);

    $command->run();

    expect(File::exists(resource_path('css/theme.css')))->toBeTrue();
    expect(File::exists(resource_path('js/utils.js')))->toBeTrue();
    expect(File::exists(resource_path('js/globals/theme.js')))->toBeTrue();

});
