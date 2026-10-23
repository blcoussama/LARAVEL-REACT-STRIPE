<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price_cents',
        'credits',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'credits' => 'integer',
        ];
    }

    /**
     * Prix lisible (ex. « 9.99 ») calculé depuis les centimes, pour l'affichage uniquement.
     */
    public function formattedPrice(): string
    {
        return number_format($this->price_cents / 100, 2, '.', '');
    }
}
