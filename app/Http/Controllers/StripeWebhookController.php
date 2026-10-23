<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\CreditPurchases;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Point d'entrée des webhooks Stripe (route publique, exclue de la protection CSRF).
 * Seuls les évènements dont la signature est valide sont traités.
 */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, CreditPurchases $purchases): JsonResponse
    {
        $secret = config('services.stripe.webhook_secret');

        if (blank($secret)) {
            Log::error('Stripe webhook received but STRIPE_WEBHOOK_KEY is not configured.');

            return response()->json(['error' => 'Webhook not configured.'], 500);
        }

        $signature = $request->header('Stripe-Signature');

        if (blank($signature)) {
            return response()->json(['error' => 'Missing signature.'], 400);
        }

        try {
            $event = Webhook::constructEvent($request->getContent(), $signature, $secret);
        } catch (\UnexpectedValueException) {
            return response()->json(['error' => 'Invalid payload.'], 400);
        } catch (SignatureVerificationException) {
            return response()->json(['error' => 'Invalid signature.'], 400);
        }

        $session = $event->data->object;

        switch ($event->type) {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                // Moyens de paiement différés : « completed » peut arriver avant l'encaissement.
                if ($session->payment_status === 'paid') {
                    $purchases->fulfill($session->id);
                }
                break;

            case 'checkout.session.async_payment_failed':
                $purchases->close($session->id, Transaction::STATUS_FAILED);
                break;

            case 'checkout.session.expired':
                $purchases->close($session->id, Transaction::STATUS_EXPIRED);
                break;

            default:
                // Évènement non utilisé par l'application : accusé de réception simple.
                break;
        }

        return response()->json(['received' => true]);
    }
}
