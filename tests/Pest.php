<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/** Secret de signature factice, utilisé uniquement par les tests. */
const TEST_WEBHOOK_SECRET = 'whsec_test_fake_secret_for_tests';

/**
 * Corps JSON d'un évènement Stripe portant une session Checkout.
 *
 * @param  array<string, mixed>  $session
 */
function stripeCheckoutEvent(string $type, array $session): string
{
    return json_encode([
        'id' => 'evt_test_'.bin2hex(random_bytes(6)),
        'object' => 'event',
        'type' => $type,
        'data' => ['object' => ['object' => 'checkout.session', ...$session]],
    ], JSON_THROW_ON_ERROR);
}

/**
 * En-tête Stripe-Signature, calculé comme le fait Stripe :
 * HMAC-SHA256 de « timestamp.payload » avec le secret du endpoint.
 */
function stripeSignatureHeader(string $payload, string $secret = TEST_WEBHOOK_SECRET, ?int $timestamp = null): string
{
    $timestamp ??= time();

    return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
}

/**
 * Envoie un corps brut au webhook, comme Stripe (pas d'encodage par Laravel).
 */
function postStripeWebhook(string $payload, ?string $signature): \Illuminate\Testing\TestResponse
{
    $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];

    if ($signature !== null) {
        $server['HTTP_STRIPE_SIGNATURE'] = $signature;
    }

    return test()->call('POST', '/buy-credits/webhook', [], [], [], $server, $payload);
}
