<?php

use Inertia\Testing\AssertableInertia as Assert;

test('face painting page can be rendered', function () {
    $response = $this->get(route('face-painting'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Site/FacePainting'));
});

test('glitter tattoos page can be rendered', function () {
    $response = $this->get(route('glitter-tattoos'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Site/GlitterTattoos'));
});

test('festival face painting page can be rendered', function () {
    $response = $this->get(route('festival-face-painting'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Site/FestivalFacePainting'));
});
