<?php

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
            $table->enum('checkout_type', ['form', 'direct'])->default('form')->after('status');
            $table->boolean('requires_patient_clinic_selection')->default(false)->after('checkout_type');
            $table->decimal('shipping_fee', 10, 2)->nullable()->after('requires_patient_clinic_selection');
            $table->decimal('tax_rate', 5, 2)->nullable()->after('shipping_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['checkout_type', 'requires_patient_clinic_selection', 'shipping_fee', 'tax_rate']);
        });
    }
};
