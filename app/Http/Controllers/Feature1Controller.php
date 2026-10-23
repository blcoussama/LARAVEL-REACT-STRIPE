<?php

namespace App\Http\Controllers;

/**
 * Feature 1 : somme de deux nombres.
 */
class Feature1Controller extends FeatureController
{
    protected function routeName(): string
    {
        return 'feature1.index';
    }

    protected function page(): string
    {
        return 'Feature1/Index';
    }

    protected function compute(float $number1, float $number2): float
    {
        return $number1 + $number2;
    }
}
