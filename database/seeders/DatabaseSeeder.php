<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\UsedFeature;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Données de démonstration (factices). Les icônes sont des SVG locaux (public/img).
     */
    public function run(): void
    {
        $sum = Feature::create([
            'route_name' => 'feature1.index',
            'image' => '/img/feature-sum.svg',
            'name' => 'Calculate Sum',
            'description' => 'Calculate the sum of two numbers.',
            'required_credits' => 1,
            'is_active' => true,
        ]);

        $difference = Feature::create([
            'route_name' => 'feature2.index',
            'image' => '/img/feature-difference.svg',
            'name' => 'Calculate Difference',
            'description' => 'Calculate the difference between two numbers.',
            'required_credits' => 3,
            'is_active' => true,
        ]);

        Package::create(['name' => 'Basic Package', 'price_cents' => 999, 'credits' => 20]);
        $silver = Package::create(['name' => 'Silver Package', 'price_cents' => 9999, 'credits' => 100]);
        Package::create(['name' => 'Golden Package', 'price_cents' => 19999, 'credits' => 500]);

        // Compte de démonstration : 10 crédits de bienvenue + un achat déjà payé + un historique d'utilisation.
        $demo = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        $demo->transactions()->create([
            'status' => Transaction::STATUS_PAID,
            'price_cents' => $silver->price_cents,
            'credits' => $silver->credits,
            'session_id' => 'cs_test_demo_seed',
            'package_id' => $silver->id,
        ]);
        $demo->addCredits($silver->credits);

        foreach (range(1, 14) as $i) {
            $feature = $i % 2 === 0 ? $difference : $sum;

            if (! $demo->spendCredits($feature->required_credits)) {
                break;
            }

            (new UsedFeature)->forceFill([
                'feature_id' => $feature->id,
                'user_id' => $demo->id,
                'credits' => $feature->required_credits,
                'data' => ['number1' => 10 * $i, 'number2' => $i],
                'created_at' => now()->subHours(15 - $i),
                'updated_at' => now()->subHours(15 - $i),
            ])->save();
        }
    }
}
