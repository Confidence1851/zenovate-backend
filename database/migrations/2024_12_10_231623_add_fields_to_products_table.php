<?php

use App\Models\Product;
use App\Services\General\ProductService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $seed = false;

        Schema::table('products', function (Blueprint $table) use (&$seed) {
            if (!Schema::hasColumn("products", "nav_description")) {
                $table->text("nav_description")->nullable();
                $table->text("key_ingredients")->nullable();
                $table->text("benefits")->nullable();
            }

            if (Schema::hasColumn("products", column: "slug")) {
                Product::get()->each(function ($p) {
                    ProductService::generateSlug($p);
                });
                $table->string("slug")->unique()->change();
            } else {
                $seed = true;
                $table->string("slug")->nullable()->unique();
            }
        });

        if (Schema::hasColumn("products", column: "price")) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn("price");
            });

            Schema::table('products', function (Blueprint $table) {
                $table->json("price")->nullable()->after("description");
            });
        }

        if ($seed) {
            Product::get()->each(function ($p) {
                ProductService::generateSlug($p);
            });
        }
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
