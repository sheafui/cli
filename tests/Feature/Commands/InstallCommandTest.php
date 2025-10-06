<?php

use Illuminate\Console\BufferedConsoleOutput;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

afterAll(function () {
    if(File::exists(resource_path('views/components/ui/separator'))) {
        File::deleteDirectory(resource_path('views/components/ui/separator'));
    }

    if (File::exists(resource_path('views/components/ui/icon'))) {
        File::deleteDirectory(resource_path('views/components/ui/icon'));
    }

    if (File::exists(resource_path('views/components/ui/radio'))) {
        File::deleteDirectory(resource_path('views/components/ui/radio'));
    }
});

it("installs a component without dependencies", function () {
    
    $this->artisan("sheaf:install separator")
        ->assertExitCode(0)
        ->run();

    $this->view('components.ui.separator.index');
});

it("installs a component along with its dependencies when confirmed", function () {

    $this->artisan("sheaf:install radio")
        ->expectsQuestion('Install required dependencies?', 'yes')
        ->assertExitCode(0)
        ->run();

    $this->view("components.ui.radio.group", ['slot' => 'default']);
});


it("overwrites existing component files when forced", function () {

    $this->artisan("sheaf:install separator")
    ->assertExitCode(0)
    ->run();

    $this->artisan("sheaf:install separator")
    ->expectsQuestion("Component 'Separator' already exists. What would you like to do?", "overwrite")
    ->expectsQuestion("All the component files will be overwritten, you might lose your modifications. are you sure you want to processed?", "yes")
    ->expectsOutputToContain("All component files will be overwritten.")
    ->assertExitCode(0)
    ->run();

    $this->view("components.ui.separator.index");

});

it("installs only dependencies when the component already exists and that option is chosen", function () {

    $command = 'sheaf:install separator';

    $this->artisan($command)
    ->assertExitCode(0)
    ->run();

    $this->artisan($command)
    ->expectsQuestion("Component 'Separator' already exists. What would you like to do?", "dependencies")
    ->expectsOutputToContain("Skipping component files, checking dependencies...")
    ->assertExitCode(0)
    ->run();

    $this->view("components.ui.separator.index");

});
