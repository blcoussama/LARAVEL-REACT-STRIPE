<?php

namespace App\Services;

use App\Models\Package;
use App\Models\User;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

/**
 * Création des sessions Stripe Checkout (isolée ici pour pouvoir être
 * remplacée par un double dans les tests).
 */
class StripeCheckout
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function isConfigured(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createSession(Package $package, User $user): Session
    {
        return $this->stripe->checkout->sessions->create($this->sessionPayload($package, $user));
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionPayload(Package $package, User $user): array
    {
        return [
            'mode' => 'payment',
            'client_reference_id' => (string) $user->getKey(),
            'customer_email' => $user->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => config('services.stripe.currency', 'usd'),
                    'product_data' => [
                        'name' => "{$package->name} - {$package->credits} credits",
                    ],
                    // Stripe attend un entier en centimes.
                    'unit_amount' => $package->price_cents,
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'user_id' => (string) $user->getKey(),
                'package_id' => (string) $package->getKey(),
            ],
            // {CHECKOUT_SESSION_ID} est remplacé par Stripe : il ne doit pas être encodé.
            'success_url' => route('credit.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('credit.cancel'),
        ];
    }
}
