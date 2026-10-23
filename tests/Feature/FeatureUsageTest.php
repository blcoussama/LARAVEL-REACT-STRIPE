<?php

use App\Models\Feature;
use App\Models\UsedFeature;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->sum = Feature::create(['route_name' => 'feature1.index', 'image' => '/img/feature-sum.svg', 'name' => 'Calculate Sum', 'description' => 'Sum', 'required_credits' => 1, 'is_active' => true]);
    $this->difference = Feature::create(['route_name' => 'feature2.index', 'image' => '/img/feature-difference.svg', 'name' => 'Calculate Difference', 'description' => 'Difference', 'required_credits' => 3, 'is_active' => true]);
});

test('la feature 1 calcule une somme et débite 1 crédit', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('feature1.calculate'), ['number1' => 2, 'number2' => 3])
        ->assertRedirect(route('feature1.index'))
        ->assertSessionHas('answer', 5.0);

    expect($user->fresh()->available_credits)->toBe(9);

    $used = UsedFeature::sole();
    expect($used->feature_id)->toBe($this->sum->id)
        ->and($used->user_id)->toBe($user->id)
        ->and($used->credits)->toBe(1)
        ->and($used->data)->toBe(['number1' => 2, 'number2' => 3]);
});

test('la feature 2 calcule une différence et débite 3 crédits', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('feature2.calculate'), ['number1' => 10, 'number2' => 4])
        ->assertSessionHas('answer', 6.0);

    expect($user->fresh()->available_credits)->toBe(7);
});

test('crédits insuffisants : refus, aucun débit, aucun historique', function () {
    $user = User::factory()->create(['available_credits' => 2]);

    $this->actingAs($user)
        ->from(route('feature2.index'))
        ->post(route('feature2.calculate'), ['number1' => 10, 'number2' => 4])
        ->assertRedirect(route('feature2.index'))
        ->assertSessionHas('error')
        ->assertSessionMissing('answer');

    expect($user->fresh()->available_credits)->toBe(2)
        ->and(UsedFeature::count())->toBe(0);
});

test('requêtes concurrentes : un solde lu avant une autre dépense ne permet pas de repasser sous zéro', function () {
    $user = User::factory()->create(['available_credits' => 3]);

    // Simule une autre requête qui a dépensé les crédits après le chargement de l'utilisateur :
    // l'instance en mémoire croit encore disposer de 3 crédits.
    User::query()->whereKey($user->id)->update(['available_credits' => 0]);
    expect($user->available_credits)->toBe(3);

    $this->actingAs($user)
        ->post(route('feature2.calculate'), ['number1' => 1, 'number2' => 1])
        ->assertSessionHas('error');

    expect($user->fresh()->available_credits)->toBe(0)
        ->and(UsedFeature::count())->toBe(0);
});

test('une saisie invalide ne consomme aucun crédit', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('feature1.calculate'), ['number1' => 'abc', 'number2' => ''])
        ->assertSessionHasErrors(['number1', 'number2']);

    expect($user->fresh()->available_credits)->toBe(10)
        ->and(UsedFeature::count())->toBe(0);
});

test('une feature désactivée renvoie 404', function () {
    $this->sum->update(['is_active' => false]);
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('feature1.index'))->assertNotFound();
    $this->actingAs($user)->post(route('feature1.calculate'), ['number1' => 1, 'number2' => 1])->assertNotFound();

    expect($user->fresh()->available_credits)->toBe(10);
});

test('un invité est renvoyé vers la connexion', function () {
    $this->get(route('feature1.index'))->assertRedirect(route('login'));
    $this->post(route('feature1.calculate'), ['number1' => 1, 'number2' => 1])->assertRedirect(route('login'));
});

test('le tableau de bord affiche l\'historique paginé de l\'utilisateur uniquement', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    foreach (range(1, 12) as $i) {
        UsedFeature::create(['feature_id' => $this->sum->id, 'user_id' => $user->id, 'credits' => 1, 'data' => ['number1' => $i, 'number2' => 1]]);
    }
    UsedFeature::create(['feature_id' => $this->sum->id, 'user_id' => $other->id, 'credits' => 1, 'data' => null]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('usedFeatures.data', 10)
            ->where('usedFeatures.meta.total', 12)
            ->where('usedFeatures.meta.last_page', 2));

    $this->actingAs($user)
        ->get(route('dashboard', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->has('usedFeatures.data', 2));
});
