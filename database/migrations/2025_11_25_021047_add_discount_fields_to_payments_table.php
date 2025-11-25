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
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'discount_code')) {
                $table->string('discount_code')->nullable()->after('status');
            }
            if (!Schema::hasColumn('payments', 'discount_amount')) {
                $table->double('discount_amount')->nullable()->after('discount_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
            if (Schema::hasColumn('payments', 'discount_code')) {
                $table->dropColumn('discount_code');
            }
        });
    }
};
