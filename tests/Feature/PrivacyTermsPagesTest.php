<?php

describe('privacy policy', function () {
    it('is reachable without authentication and covers real data practices', function () {
        $response = $this->get(route('privacy'))
            ->assertOk()
            ->assertSeeText('Stripe')
            ->assertSeeText('hashed')
            ->assertSeeText('Export your data')
            ->assertSeeText('Slack');

        $response->assertDontSee('{{', false);
        $response->assertSee('mailto:privacy@', false);
    });
});

describe('terms of service', function () {
    it('is reachable without authentication and covers real product behavior', function () {
        $response = $this->get(route('terms'))
            ->assertOk()
            ->assertSeeText('Client Portal')
            ->assertSeeText('Stripe')
            ->assertSee(route('status'), false);

        $response->assertDontSee('{{', false);
        $response->assertSee('mailto:legal@', false);
    });
});

describe('public layout footer', function () {
    it('links status, security, privacy, and terms from every public page', function () {
        foreach (['status', 'security', 'privacy', 'terms'] as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee(route('status'), false)
                ->assertSee(route('security'), false)
                ->assertSee(route('privacy'), false)
                ->assertSee(route('terms'), false);
        }
    });
});

describe('registration page', function () {
    it('links to terms and privacy', function () {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee(route('terms'), false)
            ->assertSee(route('privacy'), false);
    });
});
