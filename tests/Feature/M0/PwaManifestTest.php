<?php

use App\Features\M0Foundations;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

it('serves a valid, installable web app manifest', function () {
    $response = $this->get(route('pwa.manifest'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json');

    $manifest = $response->json();

    expect($manifest)->toHaveKeys(['name', 'short_name', 'start_url', 'scope', 'display', 'icons', 'theme_color', 'background_color']);
    expect($manifest['name'])->toBe(config('app.name'));
    expect($manifest['display'])->toBe('standalone');
    expect($manifest['start_url'])->toBe('/home');

    $sizes = collect($manifest['icons'])->where('purpose', 'any')->pluck('sizes')->all();
    expect($sizes)->toContain('192x192', '512x512');
    expect(collect($manifest['icons'])->where('purpose', 'maskable'))->toHaveCount(1);
});

it('points every manifest icon at a real PNG of the declared size', function () {
    $manifest = $this->get(route('pwa.manifest'))->json();

    foreach ($manifest['icons'] as $icon) {
        $path = public_path(ltrim(parse_url($icon['src'], PHP_URL_PATH), '/'));
        [$width, $height, $type] = getimagesize($path);

        expect($type)->toBe(IMAGETYPE_PNG);
        expect("{$width}x{$height}")->toBe($icon['sizes']);
    }

    expect(getimagesize(public_path('icons/apple-touch-icon.png'))[0])->toBe(180);
});

it('links the manifest in the app layout only when the flag is on', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('home'))->assertDontSee('rel="manifest"', false);

    Feature::for($user)->activate(M0Foundations::class);

    $this->actingAs($user)->get(route('home'))
        ->assertSee('<link rel="manifest" href="'.route('pwa.manifest').'">', false)
        ->assertSee('apple-touch-icon', false);
});
