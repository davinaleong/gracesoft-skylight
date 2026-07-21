<?php

it('is reachable without authentication and covers real shipped features', function () {
    $this->get(route('changelog'))
        ->assertOk()
        ->assertSeeText('Client Portals')
        ->assertSeeText('Stripe billing')
        ->assertSeeText('REST API')
        ->assertSeeText('Roadmap');
});

it('links to real internal pages rather than dead placeholders', function () {
    $this->get(route('changelog'))
        ->assertOk()
        ->assertSee(route('status'), false)
        ->assertSee(route('security'), false);
});

it('is linked from the public layout footer', function () {
    $this->get(route('landing'))
        ->assertOk()
        ->assertSee(route('changelog'), false);
});
