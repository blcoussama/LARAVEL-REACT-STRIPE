<?php

namespace App\Http\Controllers;

use App\Http\Resources\FeatureResource;
use App\Http\Resources\PackageResource;
use App\Models\Feature;
use App\Models\Package;
use App\Models\Transaction;
use App\Services\StripeCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Response;
use Stripe\Exception\ApiErrorException;

class CreditController extends Controller
{
    public function index(): Response
    {
        return inertia('Credit/Index', [
            'packages' => PackageResource::collection(Package::query()->orderBy('price_cents')->get()),
            'features' => FeatureResource::collection(Feature::query()->where('is_active', true)->get()),
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    /**
     * Ouvre une session Stripe Checkout et enregistre une transaction « pending ».
     * Les crédits ne sont ajoutés que par le webhook signé (voir StripeWebhookController).
     */
    public function buyCredits(Request $request, Package $package, StripeCheckout $checkout): RedirectResponse
    {
        if (! $checkout->isConfigured()) {
            return to_route('credit.index')->with('error', 'Online payment is not configured on this server.');
        }

        try {
            $session = $checkout->createSession($package, $request->user());
        } catch (ApiErrorException $e) {
            Log::error('Stripe Checkout session creation failed.', ['message' => $e->getMessage()]);

            return to_route('credit.index')->with('error', 'The payment could not be started. Please try again later.');
        }

        $request->user()->transactions()->create([
            'status' => Transaction::STATUS_PENDING,
            'price_cents' => $package->price_cents,
            'credits' => $package->credits,
            'session_id' => $session->id,
            'package_id' => $package->id,
        ]);

        return redirect()->away($session->url);
    }

    /**
     * Retour depuis Stripe. Cette page ne crédite rien : elle affiche l'état de la
     * transaction de l'utilisateur connecté, mis à jour par le webhook.
     */
    public function success(Request $request): RedirectResponse
    {
        $transaction = $request->user()
            ->transactions()
            ->where('session_id', (string) $request->query('session_id'))
            ->first();

        if (! $transaction) {
            return to_route('credit.index')->with('error', 'We could not find this payment.');
        }

        if ($transaction->status === Transaction::STATUS_PAID) {
            return to_route('credit.index')->with('success', "Payment confirmed: {$transaction->credits} credits were added to your account.");
        }

        if ($transaction->status === Transaction::STATUS_PENDING) {
            return to_route('credit.index')->with('success', 'Thank you! Your payment is being confirmed by Stripe. Your credits will appear in a few moments.');
        }

        return to_route('credit.index')->with('error', 'This payment was not completed. No credits were added.');
    }

    public function cancel(): RedirectResponse
    {
        return to_route('credit.index')->with('error', 'The payment was canceled. You have not been charged.');
    }
}
