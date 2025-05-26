<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('ndc_code')->unique();
                $table->string('brand_name')->nullable();
                $table->string('generic_name')->nullable();
                $table->string('labeler_name')->nullable();
                $table->string('product_type')->nullable();
                $table->string('source')->default('OpenFDA');
                $table->timestamps();
            });
        } else if (!Schema::hasColumn('products', 'source')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('source')->default('Database')->after('product_type');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}; 