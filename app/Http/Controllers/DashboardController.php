<?php

namespace App\Http\Controllers;

use App\Http\Resources\UsedFeatureResource;
use Illuminate\Http\Request;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Historique paginé des fonctionnalités utilisées par l'utilisateur connecté.
     */
    public function index(Request $request): Response
    {
        $usedFeatures = $request->user()
            ->usedFeatures()
            ->with('feature')
            ->latest()
            ->latest('id')
            ->paginate(10);

        return inertia('Dashboard', [
            'usedFeatures' => UsedFeatureResource::collection($usedFeatures),
        ]);
    }
}
