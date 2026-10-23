<?php

use App\Models\Feature;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use App\Services\StripeCheckout;
use Inertia\Testing\AssertableInertia as Assert;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiConnectionException;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->package = Package::create(['name' => 'Golden Package', 'price_cents' => 19999, 'credits' => 500]);
});

test('la page d\'achat liste les packs (prix en centimes) et seulement les features actives', function () {
    Feature::create(['route_name' => 'feature1.index', 'image' => '/img/feature-sum.svg', 'name' => 'Calculate Sum', 'description' => 'd', 'required_credits' => 1, 'is_active' => true]);
    Feature::create(['route_name' => 'feature2.index', 'image' => '/img/feature-difference.svg', 'name' => 'Hidden', 'description' => 'd', 'required_credits' => 3, 'is_active' => false]);

    $this->actingAs($this->user)
        ->get(route('credit.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Credit/Index')
            ->has('packages.data', 1)
            ->where('packages.data.0.price_cents', 19999)
            ->where('packages.data.0.price', '199.99')
            ->has('features.data', 1)
            ->where('features.data.0.name', 'Calculate Sum'));
});

test('acheter un pack ouvre une session Checkout et enregistre une transaction en attente', function () {
    $this->mock(StripeCheckout::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturnTrue();
        $mock->shouldReceive('createSession')->once()->andReturn(Session::constructFrom([
            'id' => 'cs_test_abc',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_abc',
        ]));
    });

    $this->actingAs($this->user)
        ->post(route('credits.buy', $this->package))
        ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_abc');

    $transaction = Transaction::sole();
    expect($transaction->status)->toBe(Transaction::STATUS_PENDING)
        ->and($transaction->session_id)->toBe('cs_test_abc')
        ->and($transaction->user_id)->toBe($this->user->id)
        ->and($transaction->price_cents)->toBe(19999)
        ->and($transaction->credits)->toBe(500)
        // Rien n'est crédité avant la confirmation par le webhook.
        ->and($this->user->fresh()->available_credits)->toBe(10);
});

test('sans clé Stripe configurée, l\'achat est refusé proprement', function () {
    config(['services.stripe.secret' => null]);

    $this->actingAs($this->user)
        ->post(route('credits.buy', $this->package))
        ->assertRedirect(route('credit.index'))
        ->assertSessionHas('error');

    expect(Transaction::count())->toBe(0);
});

test('une erreur de l\'API Stripe n\'enregistre aucune transaction', function () {
    $this->mock(StripeCheckout::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturnTrue();
        $mock->shouldReceive('createSession')->andThrow(ApiConnectionException::factory('Network error'));
    });

    $this->actingAs($this->user)
        ->post(route('credits.buy', $this->package))
        ->assertRedirect(route('credit.index'))
        ->assertSessionHas('error');

    expect(Transaction::count())->toBe(0);
});

test('la session Checkout envoie un montant entier en centimes', function () {
    $payload = app(StripeCheckout::class)->sessionPayload($this->package, $this->user);
    $priceData = $payload['line_items'][0]['price_data'];

    expect($priceData['unit_amount'])->toBe(19999)->toBeInt()
        ->and($priceData['currency'])->toBe('usd')
        ->and($payload['client_reference_id'])->toBe((string) $this->user->id)
        ->and($payload['success_url'])->toEndWith('?session_id={CHECKOUT_SESSION_ID}');
});

test('la page de succès ne crédite rien tant que le webhook n\'a pas confirmé', function () {
    $this->user->transactions()->create(['status' => 'pending', 'price_cents' => 19999, 'credits' => 500, 'session_id' => 'cs_test_wait', 'package_id' => $this->package->id]);

    $this->actingAs($this->user)
        ->get(route('credit.success', ['session_id' => 'cs_test_wait']))
        ->assertRedirect(route('credit.index'))
        ->assertSessionHas('success', fn ($message) => str_contains($message, 'being confirmed'));

    expect($this->user->fresh()->available_credits)->toBe(10)
        ->and(Transaction::sole()->status)->toBe('pending');
});

test('la page de succès confirme un paiement déjà traité par le webhook', function () {
    $this->user->transactions()->create(['status' => 'paid', 'price_cents' => 19999, 'credits' => 500, 'session_id' => 'cs_test_done', 'package_id' => $this->package->id]);

    $this->actingAs($this->user)
        ->get(route('credit.success', ['session_id' => 'cs_test_done']))
        ->assertSessionHas('success', fn ($message) => str_contains($message, 'Payment confirmed'));
});

test('la page de succès ne révèle pas la session d\'un autre utilisateur', function () {
    $other = User::factory()->create();
    $other->transactions()->create(['status' => 'paid', 'price_cents' => 19999, 'credits' => 500, 'session_id' => 'cs_test_other', 'package_id' => $this->package->id]);

    $this->actingAs($this->user)
        ->get(route('credit.success', ['session_id' => 'cs_test_other']))
        ->assertSessionHas('error');
});

test('l\'annulation affiche un message sans rien débiter', function () {
    $this->actingAs($this->user)
        ->get(route('credit.cancel'))
        ->assertRedirect(route('credit.index'))
        ->assertSessionHas('error');

    expect($this->user->fresh()->available_credits)->toBe(10);
});

test('un invité ne peut pas acheter de crédits', function () {
    $this->post(route('credits.buy', $this->package))->assertRedirect(route('login'));

    expect(Transaction::count())->toBe(0);
});
