<?php

use Illuminate\Console\BufferedConsoleOutput;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use function PHPUnit\Framework\directoryExists;

it("Should be able to initialize the package", function () {
    $command = $this->artisan("sheaf:init")
        ->expectsQuestion('Install Phosphor Icons package?', 'no')
        ->expectsQuestion('Install and setup livewire?', 'yes')
        ->expectsQuestion('Include dark mode theme support?', 'yes')
        ->expectsQuestion('Target CSS file for package assets integration', 'app.css')
        ->expectsQuestion('Theme CSS file name', 'theme.css');


    if(!File::exists(resource_path('js/app.js'))) {
        $command->expectsQuestion('Enter the path (relative to resources/) to your main JS file:', 'js/app.js');
    }

    $command->assertExitCode(0);
    expect(\Composer\InstalledVersions::isInstalled('livewire/livewire'))->toBeTrue();
    expect(\Composer\InstalledVersions::isInstalled('wireui/heroicons'))->toBeTrue();
    expect(File::exists(resource_path('css/theme.css')))->toBeTrue();
});

it("Should be able to install a component without dependencies", function () {

    $this->artisan("sheaf:install separator")
        ->assertExitCode(0);

    $this->view('components.ui.separator.index');
});

it("Should be able to install a component with dependencies", function () {
    
    if(File::exists(resource_path('views/components/ui/icon'))) {
        File::deleteDirectory(resource_path('views/components/ui/icon'));
    }

    if(File::exists(resource_path('views/components/ui/radio'))) {
        File::deleteDirectory(resource_path('views/components/ui/radio'));
    }

    $this->artisan("sheaf:install radio")
        ->expectsQuestion('Install required dependencies?', 'yes')
        ->assertExitCode(0);

        $this->view("components.ui.radio.group", ['slot' => 'default']);

});
