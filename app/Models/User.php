<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[ObservedBy(UserObserver::class)]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /** Crédits offerts à l'inscription. */
    public const SIGNUP_CREDITS = 10;

    /**
     * available_credits n'est volontairement pas assignable en masse :
     * le solde ne change que via spendCredits() et addCredits().
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'available_credits' => 'integer',
        ];
    }

    /**
     * Débite des crédits de façon atomique : la vérification du solde et le
     * décrément sont une seule requête UPDATE ... WHERE available_credits >= ?.
     * Deux requêtes simultanées ne peuvent donc pas rendre le solde négatif.
     *
     * @return bool false si le solde (en base, pas en mémoire) est insuffisant
     */
    public function spendCredits(int $credits): bool
    {
        if ($credits < 1) {
            throw new \InvalidArgumentException('The number of credits must be positive.');
        }

        $updated = static::query()
            ->whereKey($this->getKey())
            ->where('available_credits', '>=', $credits)
            ->decrement('available_credits', $credits);

        $this->syncCreditsFromDatabase();

        return $updated === 1;
    }

    /**
     * Ajoute des crédits (incrément atomique en base).
     */
    public function addCredits(int $credits): void
    {
        if ($credits < 1) {
            throw new \InvalidArgumentException('The number of credits must be positive.');
        }

        static::query()->whereKey($this->getKey())->increment('available_credits', $credits);

        $this->syncCreditsFromDatabase();
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<UsedFeature, $this>
     */
    public function usedFeatures(): HasMany
    {
        return $this->hasMany(UsedFeature::class);
    }

    private function syncCreditsFromDatabase(): void
    {
        $this->available_credits = (int) static::query()->whereKey($this->getKey())->value('available_credits');
        $this->syncOriginalAttribute('available_credits');
    }
}
