<?php


it("Should be able to install a component", function () {
    $this->artisan("sheaf:install")
    ->expectsQuestion("What are the component(s) you would like to install?", "button")
    ->assertExitCode(0);

    expect(resource_path('views/components/ui/button'))->toBeDirectory();
    expect(resource_path('views/components/ui/button/index.blade.php'))->toBeFile();
});