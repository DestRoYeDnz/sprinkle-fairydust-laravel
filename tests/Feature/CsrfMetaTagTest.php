<?php

it('includes the csrf meta token in the web app shell', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('name="csrf-token"', false);
});
