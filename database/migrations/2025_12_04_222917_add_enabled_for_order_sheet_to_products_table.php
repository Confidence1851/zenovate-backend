<?php

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('enabled_for_order_sheet')->default(false)->after('tax_rate');
        });

        // Enable all peptides for order sheet by default
        $peptidesCategory = ProductCategory::where('slug', 'peptides')->first();
        if ($peptidesCategory) {
            Product::where('category_id', $peptidesCategory->id)
                ->update(['enabled_for_order_sheet' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('enabled_for_order_sheet');
        });
    }
};
