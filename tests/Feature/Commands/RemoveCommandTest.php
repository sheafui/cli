<?php

use Illuminate\Support\Facades\File;

it("removes an installed component", function () {

    $component = 'separator';
    $componentDirectory = resource_path("views/components/ui/$component");

    $this->artisan("sheaf:install $component")
        ->assertExitCode(0)
        ->run();


    expect(File::isDirectory($componentDirectory))->toBeTrue();

    $this->view("components.ui.$component.index");
    
    $this->artisan("sheaf:remove $component")
    ->assertExitCode(0)
    ->expectsOutputToContain("Deleted directory: resources/views/components/ui/$component")
    ->run();
    
    expect(File::isDirectory($componentDirectory))->toBeFalse();
    
    $sheafLock = File::get(base_path("sheaf-lock.json"));

    expect($sheafLock)->not->toContain("$component");

});

it("quits successfully when attempting to remove a non-installed component", function () {

    $component = 'modal';
    $componentDirectory = resource_path("views/components/ui/$component");

    
    $this->artisan("sheaf:remove $component")
    ->assertExitCode(0)
    ->expectsOutputToContain("Component is not installed in this project.")
    ->run();
    
    expect(File::isDirectory($componentDirectory))->toBeFalse();
    
    $sheafLock = File::get(base_path("sheaf-lock.json"));

    expect($sheafLock)->not->toContain("$component");

});
