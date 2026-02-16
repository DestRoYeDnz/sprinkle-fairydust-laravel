<?php

it('uses pacific auckland as the default app timezone', function () {
    expect(config('app.timezone'))->toBe('Pacific/Auckland');
});
