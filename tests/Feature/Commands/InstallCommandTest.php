<?php


it("Should be able to initialize the package", function() {
    $result = $this->artisan("sheaf:init --skip-prompts"); // exit with code 1 so the test fails

});

// it("Should be able to install a component", function () {

//     $result = $this->artisan("sheaf:init --skip-prompts")->run();

//     dd($result);


//     $this->artisan("sheaf:install alerts --internal-deps --external-deps --force")
//     ->assertExitCode(0);

//     $this->view('components.ui.alerts.index');

// });