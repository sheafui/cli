<?php


it("Should be able to install a component", function () {
    $this->artisan("sheaf:install")
    ->expectsQuestion("What are the component(s) you would like to install?", "alerts")
    ->assertExitCode(0);

    $this->view('components.ui.alerts.index');

    // expect(resource_path('views/components/ui/button'))->toBeDirectory();
    // expect(resource_path('views/components/ui/button/index.blade.php'))->toBeFile();
});