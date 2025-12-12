<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('regular_price', 10, 2)->nullable()->change();
            if (!Schema::hasColumn('products', 'sale_price')) {
                $table->decimal('sale_price', 10, 2)->nullable()->after('regular_price');
            } else {
                $table->decimal('sale_price', 10, 2)->nullable()->change();
            }
        });
    }
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('regular_price')->nullable()->change();

            if (Schema::hasColumn('products', 'sale_price')) {
                $table->decimal('sale_price')->nullable()->change();
            }
        });
    }
};

