<?php

use App\Models\User;

test('une inscription reçoit 10 crédits, même si le formulaire en demande davantage', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'available_credits' => 1000000,
    ])->assertRedirect(route('dashboard', absolute: false));

    expect(User::sole()->available_credits)->toBe(User::SIGNUP_CREDITS);
});

test('available_credits n\'est pas assignable en masse', function () {
    $user = User::create(['name' => 'X', 'email' => 'x@example.com', 'password' => 'password', 'available_credits' => 500]);

    expect($user->fresh()->available_credits)->toBe(10);

    $user->update(['available_credits' => 999]);
    expect($user->fresh()->available_credits)->toBe(10);
});

test('deux dépenses concurrentes ne peuvent pas utiliser deux fois le même solde', function () {
    $user = User::factory()->create(['available_credits' => 3]);

    // Deux « requêtes » chargent le même utilisateur au même moment : chacune voit 3 crédits.
    $first = User::find($user->id);
    $second = User::find($user->id);

    expect($first->spendCredits(3))->toBeTrue()
        ->and($second->available_credits)->toBe(3)
        ->and($second->spendCredits(3))->toBeFalse()
        ->and($user->fresh()->available_credits)->toBe(0);
});

test('le solde ne devient jamais négatif', function () {
    $user = User::factory()->create(['available_credits' => 5]);

    $results = collect(range(1, 4))->map(fn () => $user->spendCredits(2));

    expect($results->filter()->count())->toBe(2)
        ->and($user->fresh()->available_credits)->toBe(1)
        ->and($user->available_credits)->toBe(1);
});

test('ajouter des crédits incrémente le solde', function () {
    $user = User::factory()->create();

    $user->addCredits(20);

    expect($user->fresh()->available_credits)->toBe(30)
        ->and($user->available_credits)->toBe(30);
});

test('un montant de crédits nul ou négatif est rejeté', function () {
    $user = User::factory()->create();

    expect(fn () => $user->spendCredits(0))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $user->addCredits(-5))->toThrow(InvalidArgumentException::class);
});
