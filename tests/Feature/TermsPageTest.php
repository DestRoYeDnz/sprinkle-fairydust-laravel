<?php

use Inertia\Testing\AssertableInertia as Assert;

test('terms and conditions page can be rendered', function () {
    $response = $this->get(route('terms'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Site/Terms')
    );
});
