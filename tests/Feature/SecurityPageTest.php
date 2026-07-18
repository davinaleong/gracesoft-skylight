<?php

it('is reachable without authentication and covers the real security practices', function () {
    $response = $this->get(route('security'))
        ->assertOk()
        ->assertSeeText('two-factor authentication')
        ->assertSeeText('bcrypt')
        ->assertSeeText('SHA-256')
        ->assertSeeText('AES-256')
        ->assertSeeText('Role-based permissions')
        ->assertSeeText('Nightly backups');

    // Regression guard: `security@{{ ... }}` is a Blade escape sequence
    // (`@{{`), not string concatenation -- it silently rendered as literal
    // template text instead of a real mailto link the first time this page
    // was written. Assert a real address rendered, not raw template syntax.
    $response->assertDontSee('{{', false);
    $response->assertSee('mailto:security@', false);
});
