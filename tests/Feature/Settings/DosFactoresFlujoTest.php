<?php

use App\Livewire\Settings\Security;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
});

test('un usuario puede activar y confirmar 2FA con un código válido', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test(Security::class)->call('enable');

    // Tras enable, el usuario tiene un secreto de 2FA.
    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull();

    // Generamos un código TOTP válido desde el secreto.
    $secret = decrypt($user->two_factor_secret);
    $codigo = app(Google2FA::class)->getCurrentOtp($secret);

    $component->set('code', $codigo)->call('confirmTwoFactor');

    $component->assertHasNoErrors();

    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();
});
