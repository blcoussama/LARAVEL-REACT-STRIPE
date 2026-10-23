<?php

use Inertia\Testing\AssertableInertia as Assert;

test('la page d\'accueil s\'affiche sans exposer les versions de Laravel et PHP', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->missing('laravelVersion')
            ->missing('phpVersion'));
});
