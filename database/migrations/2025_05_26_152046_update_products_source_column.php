<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, let's backup the source values
        $products = DB::table('products')->get();
        $sourceBackup = [];
        foreach ($products as $product) {
            $sourceBackup[$product->id] = $product->source;
        }

        Schema::table('products', function (Blueprint $table) {
            // Drop the existing source column if it exists
            $table->dropColumn('source');
        });

        Schema::table('products', function (Blueprint $table) {
            // Recreate the source column with proper default
            $table->string('source')->default('Database');
        });

        // Restore the original source values
        foreach ($sourceBackup as $id => $source) {
            DB::table('products')
                ->where('id', $id)
                ->update(['source' => $source]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('source')->default('Database');
        });
    }
};
