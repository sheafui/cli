<?php

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

it("Should be able to install a component without dependencies", function () {
    
    $this->artisan("sheaf:install separator")
        ->assertExitCode(0);

    $this->view('components.ui.separator.index');
});

it("Should be able to install a component with dependencies", function () {

    $this->artisan("sheaf:install radio")
        ->expectsQuestion('Install required dependencies?', 'yes')
        ->assertExitCode(0);

    $this->view("components.ui.radio.group", ['slot' => 'default']);
});
