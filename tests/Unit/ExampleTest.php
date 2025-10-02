<?php

namespace Sheaf\Cli\Tests\Unit;

test('that true is true', function () {
  dump(['class' => get_class($this), 'parent' => get_parent_class($this)]);

    expect(true)->toBeTrue();
});
