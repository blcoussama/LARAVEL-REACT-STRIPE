<?php

use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;

/*
 * Webhook Stripe : signature vérifiée par le SDK officiel (Stripe\Webhook),
 * avec un secret factice. Aucun appel réseau vers Stripe.
 */

beforeEach(function () {
    config(['services.stripe.webhook_secret' => TEST_WEBHOOK_SECRET]);

    $this->user = User::factory()->create(); // 10 crédits de bienvenue
    $package = Package::create(['name' => 'Basic Package', 'price_cents' => 999, 'credits' => 20]);

    $this->transaction = $this->user->transactions()->create([
        'status' => Transaction::STATUS_PENDING,
        'price_cents' => 999,
        'credits' => 20,
        'session_id' => 'cs_test_123',
        'package_id' => $package->id,
    ]);
});

function completedPaidEvent(string $sessionId = 'cs_test_123'): string
{
    return stripeCheckoutEvent('checkout.session.completed', [
        'id' => $sessionId,
        'payment_status' => 'paid',
        'amount_total' => 999,
    ]);
}

test('un évènement checkout.session.completed signé et payé crédite l\'acheteur', function () {
    $payload = completedPaidEvent();

    postStripeWebhook($payload, stripeSignatureHeader($payload))
        ->assertOk()
        ->assertJson(['received' => true]);

    expect($this->user->fresh()->available_credits)->toBe(30)
        ->and($this->transaction->fresh()->status)->toBe(Transaction::STATUS_PAID);
});

test('le même évènement livré deux fois ne crédite qu\'une seule fois (idempotence)', function () {
    $payload = completedPaidEvent();

    postStripeWebhook($payload, stripeSignatureHeader($payload))->assertOk();
    postStripeWebhook($payload, stripeSignatureHeader($payload))->assertOk();
    // Nouvel évènement (autre id) pour la même session : toujours ignoré.
    $again = completedPaidEvent();
    postStripeWebhook($again, stripeSignatureHeader($again))->assertOk();

    expect($this->user->fresh()->available_credits)->toBe(30);
});

test('une signature calculée avec un autre secret est refusée', function () {
    $payload = completedPaidEvent();

    postStripeWebhook($payload, stripeSignatureHeader($payload, 'whsec_attacker_secret'))
        ->assertStatus(400);

    expect($this->user->fresh()->available_credits)->toBe(10)
        ->and($this->transaction->fresh()->status)->toBe(Transaction::STATUS_PENDING);
});

test('un corps modifié après signature est refusé', function () {
    $signed = completedPaidEvent();
    $tampered = completedPaidEvent('cs_test_123'); // autre id d'évènement : corps différent

    postStripeWebhook($tampered, stripeSignatureHeader($signed))->assertStatus(400);

    expect($this->user->fresh()->available_credits)->toBe(10);
});

test('un appel sans en-tête Stripe-Signature renvoie 400 (et non 500)', function () {
    postStripeWebhook(completedPaidEvent(), null)->assertStatus(400);

    expect($this->user->fresh()->available_credits)->toBe(10);
});

test('une signature trop ancienne (rejeu) est refusée', function () {
    $payload = completedPaidEvent();

    postStripeWebhook($payload, stripeSignatureHeader($payload, TEST_WEBHOOK_SECRET, time() - 3600))
        ->assertStatus(400);

    expect($this->user->fresh()->available_credits)->toBe(10);
});

test('un corps signé mais non JSON est refusé', function () {
    $payload = 'not-json';

    postStripeWebhook($payload, stripeSignatureHeader($payload))->assertStatus(400);
});

test('sans secret de webhook configuré, rien n\'est traité', function () {
    config(['services.stripe.webhook_secret' => null]);
    $payload = completedPaidEvent();

    postStripeWebhook($payload, stripeSignatureHeader($payload))->assertStatus(500);

    expect($this->user->fresh()->available_credits)->toBe(10);
});

test('paiement différé : crédité seulement à async_payment_succeeded', function () {
    $completed = stripeCheckoutEvent('checkout.session.completed', ['id' => 'cs_test_123', 'payment_status' => 'unpaid']);
    postStripeWebhook($completed, stripeSignatureHeader($completed))->assertOk();

    expect($this->user->fresh()->available_credits)->toBe(10)
        ->and($this->transaction->fresh()->status)->toBe(Transaction::STATUS_PENDING);

    $succeeded = stripeCheckoutEvent('checkout.session.async_payment_succeeded', ['id' => 'cs_test_123', 'payment_status' => 'paid']);
    postStripeWebhook($succeeded, stripeSignatureHeader($succeeded))->assertOk();

    expect($this->user->fresh()->available_credits)->toBe(30)
        ->and($this->transaction->fresh()->status)->toBe(Transaction::STATUS_PAID);
});

test('paiement différé échoué : transaction « failed », aucun crédit', function () {
    $failed = stripeCheckoutEvent('checkout.session.async_payment_failed', ['id' => 'cs_test_123', 'payment_status' => 'unpaid']);
    postStripeWebhook($failed, stripeSignatureHeader($failed))->assertOk();

    expect($this->transaction->fresh()->status)->toBe(Transaction::STATUS_FAILED)
        ->and($this->user->fresh()->available_credits)->toBe(10);
});

test('session expirée : transaction « expired » qui ne peut plus être créditée', function () {
    $expired = stripeCheckoutEvent('checkout.session.expired', ['id' => 'cs_test_123', 'payment_status' => 'unpaid']);
    postStripeWebhook($expired, stripeSignatureHeader($expired))->assertOk();

    $late = completedPaidEvent();
    postStripeWebhook($late, stripeSignatureHeader($late))->assertOk();

    expect($this->transaction->fresh()->status)->toBe(Transaction::STATUS_EXPIRED)
        ->and($this->user->fresh()->available_credits)->toBe(10);
});

test('une session inconnue est acquittée sans effet', function () {
    $payload = completedPaidEvent('cs_test_unknown');

    postStripeWebhook($payload, stripeSignatureHeader($payload))->assertOk();

    expect($this->user->fresh()->available_credits)->toBe(10)
        ->and($this->transaction->fresh()->status)->toBe(Transaction::STATUS_PENDING);
});

test('un type d\'évènement non utilisé est simplement acquitté', function () {
    $payload = json_encode(['id' => 'evt_test_other', 'object' => 'event', 'type' => 'customer.created', 'data' => ['object' => ['object' => 'customer', 'id' => 'cus_test']]]);

    postStripeWebhook($payload, stripeSignatureHeader($payload))->assertOk();

    expect($this->user->fresh()->available_credits)->toBe(10);
});
