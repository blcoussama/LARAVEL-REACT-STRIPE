<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Traitement des achats de crédits confirmés par les webhooks Stripe.
 */
class CreditPurchases
{
    /**
     * Crédite l'acheteur une seule fois par session Stripe.
     *
     * Stripe peut livrer plusieurs fois le même évènement (nouvelles tentatives,
     * livraisons simultanées) : seul l'UPDATE qui fait passer la transaction de
     * « pending » à « paid » ajoute les crédits, dans la même transaction SQL.
     *
     * @return bool true si les crédits ont été ajoutés par cet appel
     */
    public function fulfill(string $sessionId): bool
    {
        return DB::transaction(function () use ($sessionId) {
            $transaction = Transaction::query()
                ->where('session_id', $sessionId)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                Log::warning('Stripe webhook: no transaction for this checkout session.', ['session_id' => $sessionId]);

                return false;
            }

            $switched = Transaction::query()
                ->whereKey($transaction->getKey())
                ->where('status', Transaction::STATUS_PENDING)
                ->update(['status' => Transaction::STATUS_PAID, 'updated_at' => now()]);

            if ($switched === 0) {
                // Déjà traitée (ou expirée / échouée) : on ignore l'évènement.
                return false;
            }

            User::query()
                ->whereKey($transaction->user_id)
                ->increment('available_credits', $transaction->credits);

            return true;
        });
    }

    /**
     * Clôture une transaction encore en attente (paiement échoué, session expirée).
     */
    public function close(string $sessionId, string $status): void
    {
        Transaction::query()
            ->where('session_id', $sessionId)
            ->where('status', Transaction::STATUS_PENDING)
            ->update(['status' => $status, 'updated_at' => now()]);
    }
}
