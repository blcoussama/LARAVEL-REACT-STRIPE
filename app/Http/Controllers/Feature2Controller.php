<?php

namespace App\Http\Controllers;

/**
 * Feature 2 : différence de deux nombres.
 */
class Feature2Controller extends FeatureController
{
    protected function routeName(): string
    {
        return 'feature2.index';
    }

    protected function page(): string
    {
        return 'Feature2/Index';
    }

    protected function compute(float $number1, float $number2): float
    {
        return $number1 - $number2;
    }
}
