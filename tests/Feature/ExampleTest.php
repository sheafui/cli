<?php

namespace Sheaf\Cli\Tests\Feature;

test('confirm environment is set to testing', function () {
    expect(config('app.env'))->toBe('testing');
});

test('sum', function () {
    $result = 3;
    expect($result)->toBe(3);
});
