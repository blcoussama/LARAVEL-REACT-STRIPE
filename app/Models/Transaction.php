<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Achat d'un pack de crédits via Stripe Checkout.
 * Créée « pending » à l'ouverture de la session, passée à « paid » uniquement
 * par le webhook signé de Stripe.
 */
class Transaction extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'status',
        'price_cents',
        'credits',
        'session_id',
        'user_id',
        'package_id',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'credits' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
