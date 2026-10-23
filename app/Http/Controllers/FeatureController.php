<?php

namespace App\Http\Controllers;

use App\Http\Resources\FeatureResource;
use App\Models\Feature;
use App\Models\UsedFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

/**
 * Logique commune aux fonctionnalités payantes : chaque utilisation débite
 * `required_credits` et est enregistrée dans l'historique.
 */
abstract class FeatureController extends Controller
{
    /** Nom de la route de la page (sert aussi d'identifiant de la feature en base). */
    abstract protected function routeName(): string;

    /** Composant Inertia de la page. */
    abstract protected function page(): string;

    abstract protected function compute(float $number1, float $number2): float;

    public function index(): Response
    {
        return inertia($this->page(), [
            'feature' => new FeatureResource($this->feature()),
            'answer' => session('answer'),
        ]);
    }

    public function calculate(Request $request): RedirectResponse
    {
        $feature = $this->feature();

        $data = $request->validate([
            'number1' => ['required', 'numeric', 'between:-1000000000,1000000000'],
            'number2' => ['required', 'numeric', 'between:-1000000000,1000000000'],
        ]);

        $user = $request->user();

        $used = DB::transaction(function () use ($user, $feature, $data) {
            // Débit atomique : pas de « vérifier puis décrémenter » en deux temps.
            if (! $user->spendCredits($feature->required_credits)) {
                return false;
            }

            UsedFeature::create([
                'feature_id' => $feature->id,
                'user_id' => $user->id,
                'credits' => $feature->required_credits,
                'data' => $data,
            ]);

            return true;
        });

        if (! $used) {
            return back()->with('error', 'You do not have enough credits to use this feature.');
        }

        return to_route($this->routeName())
            ->with('answer', $this->compute((float) $data['number1'], (float) $data['number2']));
    }

    protected function feature(): Feature
    {
        return Feature::query()
            ->where('route_name', $this->routeName())
            ->where('is_active', true)
            ->firstOrFail();
    }
}
