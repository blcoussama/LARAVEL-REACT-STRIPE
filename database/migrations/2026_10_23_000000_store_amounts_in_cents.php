<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les montants passent d'un décimal (prix en dollars, source d'erreurs
 * d'arrondi : 199.99 * 100 = 19998.999...) à un entier en centimes, format
 * attendu par Stripe (unit_amount). session_id devient unique : une session
 * Stripe correspond à une seule transaction, ce qui rend le webhook idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['packages', 'transactions'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedInteger('price_cents')->default(0)->after('price');
            });

            DB::table($table)->orderBy('id')->each(function (object $row) use ($table) {
                DB::table($table)->where('id', $row->id)->update([
                    'price_cents' => (int) round(((float) $row->price) * 100),
                ]);
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('price');
            });
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->unique('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['session_id']);
        });

        foreach (['packages', 'transactions'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->decimal('price', 10, 2)->default(0);
            });

            DB::table($table)->orderBy('id')->each(function (object $row) use ($table) {
                DB::table($table)->where('id', $row->id)->update([
                    'price' => $row->price_cents / 100,
                ]);
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('price_cents');
            });
        }
    }
};
