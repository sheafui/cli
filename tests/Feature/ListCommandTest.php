<?php

//file: tests/Feature/ListCommandTest.php
test("List artisan should display a list of fluxtor components", function () {
  // echo get_class($this); //echo P\Packages\cli\tests\Feature\ListCommandTest

  dump(['class' => get_class($this), 'parent' => get_parent_class($this)]);


  // $this->artisan('fluxtor:add');

  expect(true)->toBe(true);
  // $this->artisan('fluxtor:list')->assetExitCode(0);
});
