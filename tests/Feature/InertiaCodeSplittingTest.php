<?php

it('uses lazy-loaded client pages and eager-loaded ssr pages for Inertia', function () {
    $clientEntry = file_get_contents(resource_path('js/app.ts'));
    $ssrEntry = file_get_contents(resource_path('js/ssr.ts'));

    expect($clientEntry)
        ->toBeString()
        ->toContain("const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue');")
        ->toContain('resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, pages),')
        ->not->toContain('{ eager: true }');

    expect($ssrEntry)
        ->toBeString()
        ->toContain("const pages = import.meta.glob('./pages/**/*.vue', { eager: true });")
        ->toContain('resolve: (name) => pages[`./pages/${name}.vue`],');
});
