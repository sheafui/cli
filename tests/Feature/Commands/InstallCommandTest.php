<?php

use Illuminate\Support\Facades\File;

it("installs a component without dependencies", function () {

    $this->artisan("sheaf:install separator")
        ->assertExitCode(0)
        ->run();

    expect(view()->exists('components.ui.separator.index'))->toBeTrue();
    $this->artisan("sheaf:remove separator")->run();
});

it("installs a component along with its dependencies when confirmed", function () {

    $sheafLock = json_decode(File::get(base_path("sheaf-lock.json")), true);

    if(isset($sheafLock['internalDependencies']['icon'])) {
        $this->artisan("sheaf:remove icon radio")->run();
    }

    $this->artisan("sheaf:install radio")
        ->expectsQuestion('Install required dependencies?', true)
        ->assertExitCode(0)
        ->run();


    expect(view()->exists("components.ui.radio.group"))->toBeTrue();

    $this->artisan("sheaf:remove radio")->expectsQuestion("icon is no longer used as a dependency, would you like to remove it?", true)->run();

});


it("overwrites existing component files when forced", function () {

    $this->artisan("sheaf:install separator")
        ->assertExitCode(0)
        ->run();

    $this->artisan("sheaf:install separator")
        ->expectsQuestion("Component 'Separator' already exists. What would you like to do?", "overwrite")
        ->expectsQuestion("All the component files will be overwritten, you might lose your modifications. are you sure you want to proceed?", "yes")
        ->expectsOutputToContain("All component files will be overwritten.")
        ->assertExitCode(0)
        ->run();

    expect(view()->exists("components.ui.separator.index"))->toBeTrue();

    $this->artisan("sheaf:remove separator")->run();
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

    expect(view()->exists("components.ui.separator.index"))->toBeTrue();

    $this->artisan("sheaf:remove separator")->run();
});


it("simulates component installation with the dry-run option", function () {
    $this->artisan("sheaf:install alerts --dry-run")
        ->expectsOutputToContain("Preview: Installing Alerts (Dry Run)")
        ->expectsOutputToContain("Will create")
        ->assertExitCode(0)
        ->run();

    expect(view()->exists("components.ui.alerts.index"))->toBeFalse();
});
