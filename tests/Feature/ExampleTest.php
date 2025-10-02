<?php

namespace Sheaf\Cli\Tests\Feature;

use Sheaf\Cli\Tests\TestCase;

uses(TestCase::class);

test('confirm environment is set to testing', function () {
    expect(config('app.env'))->toBe('testing');
});

test('sum', function () {
    $result = 3;
    expect($result)->toBe(3);
});
