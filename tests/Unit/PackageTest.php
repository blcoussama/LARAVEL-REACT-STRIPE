<?php

use App\Models\Package;

test('le prix affiché est calculé depuis les centimes', function (int $cents, string $expected) {
    expect((new Package(['price_cents' => $cents]))->formattedPrice())->toBe($expected);
})->with([
    [999, '9.99'],
    [9999, '99.99'],
    [19999, '199.99'],
    [29, '0.29'],
]);
