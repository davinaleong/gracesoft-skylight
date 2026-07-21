<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('landing page', function () {
    it('is reachable without authentication and covers real, shipped features', function () {
        $this->get(route('landing'))
            ->assertOk()
            ->assertSeeText('Client Portal')
            ->assertSeeText('two-factor')
            ->assertSee(route('register'), false)
            ->assertSee(route('pricing'), false);
    });

    it('shows the plan names and prices from config/plans.php', function () {
        $response = $this->get(route('landing'))->assertOk();

        foreach (config('plans') as $plan) {
            $response->assertSeeText($plan['name']);
            $response->assertSeeText('$'.$plan['price_monthly']);
        }
    });

    it('redirects authenticated users to their dashboard instead of showing the landing page', function () {
        $this->actingAs(User::factory()->create());

        $this->get(route('landing'))->assertRedirect(route('home'));
    });
});

describe('pricing page', function () {
    it('is reachable without authentication and lists every plan', function () {
        $response = $this->get(route('pricing'))->assertOk();

        foreach (config('plans') as $plan) {
            $response->assertSeeText($plan['name']);
            $response->assertSeeText('$'.$plan['price_monthly']);
        }
    });

    it('links guests to registration and authenticated users to billing', function () {
        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee(route('register'), false);

        $this->actingAs(User::factory()->create());

        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee(route('billing'), false);
    });

    it('does not claim feature differences between plans that do not actually exist in the app', function () {
        // Every plan includes every feature today (config/plans.php only varies
        // board_limit/member_limit) -- guard against copy drifting into
        // claiming a feature is plan-gated when PlanLimiter doesn't enforce it.
        $response = $this->get(route('pricing'))->assertOk();
        $response->assertSeeText('Every plan includes every feature');
    });
});
