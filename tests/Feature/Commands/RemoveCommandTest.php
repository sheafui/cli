<?php

use Illuminate\Console\BufferedConsoleOutput;
use Illuminate\Support\Facades\Artisan;
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


// it("removes an installed component with dependencies", function () {

//     $component = 'select';
//     $dependency = 'icon';
//     $helper = 'popup';
//     $baseDirectory = resource_path("views/components/ui/");

//     //* using force option to ensure the command runs
//     $this->artisan("sheaf:install $component --force  --internal-deps")
//         ->assertExitCode(0)
//         ->run();

//     expect(File::isDirectory("$baseDirectory/$component"))->toBeTrue();
//     expect(File::isDirectory("$baseDirectory/$dependency"))->toBeTrue();
//     expect(File::exists("$baseDirectory/$helper.blade.php"))->toBeTrue();

//     expect(view()->exists("components.ui.$component.index"))->toBeTrue();
//     expect(view()->exists("components.ui.$dependency.index"))->toBeTrue();
//     expect(view()->exists("components.ui.$helper"))->toBeTrue();


//     $output = new BufferedConsoleOutput();
//     Artisan::call("sheaf:remove $component", [], $output);
//     $sheafLock = File::get(base_path("sheaf-lock.json"));
//     dd($output->fetch(), $sheafLock);


//     $this->artisan("sheaf:remove $component")
//         ->assertExitCode(0)
//         ->expectsOutputToContain("Deleted directory: resources/views/components/ui/$component")
//         ->expectsOutputToContain("Deleted directory: resources/views/components/ui/$dependency")
//         ->expectsOutputToContain("Removed internal dependency: $dependency (no longer used.)")
//         ->expectsOutputToContain("Removed helper: $helper (no longer used.)")
//         ->run();
//     // Deleted directory: resources/views/components/ui/icon

//     expect(File::isDirectory("$baseDirectory/$component"))->toBeFalse();
//     expect(File::isDirectory("$baseDirectory/$dependency"))->toBeFalse();

//     $sheafLock = File::get(base_path("sheaf-lock.json"));

//     expect($sheafLock)->not->toContain("$component");
//     expect($sheafLock)->not->toContain("$dependency");
// })->only();
