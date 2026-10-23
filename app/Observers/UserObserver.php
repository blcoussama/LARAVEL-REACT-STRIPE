<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Crédits de bienvenue à la création du compte. Le champ n'étant pas
     * assignable en masse, un formulaire d'inscription ne peut pas le fixer.
     */
    public function creating(User $user): void
    {
        $user->available_credits ??= User::SIGNUP_CREDITS;
    }
}
