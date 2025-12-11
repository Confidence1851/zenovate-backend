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
            if (!Schema::hasColumn('payments', 'order_type')) {
                $table->enum('order_type', ['regular', 'order_sheet'])->default('regular')->after('status');
            }
            if (!Schema::hasColumn('payments', 'account_number')) {
                $table->string('account_number')->nullable()->after('order_type');
            }
            if (!Schema::hasColumn('payments', 'location')) {
                $table->string('location')->nullable()->after('account_number');
            }
            if (!Schema::hasColumn('payments', 'shipping_address')) {
                $table->text('shipping_address')->nullable()->after('location');
            }
            if (!Schema::hasColumn('payments', 'additional_information')) {
                $table->text('additional_information')->nullable()->after('shipping_address');
            }
        });

        // Add index on order_type
        Schema::table('payments', function (Blueprint $table) {
            $table->index('order_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex(['order_type']);
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'additional_information')) {
                $table->dropColumn('additional_information');
            }
            if (Schema::hasColumn('payments', 'shipping_address')) {
                $table->dropColumn('shipping_address');
            }
            if (Schema::hasColumn('payments', 'location')) {
                $table->dropColumn('location');
            }
            if (Schema::hasColumn('payments', 'account_number')) {
                $table->dropColumn('account_number');
            }
            if (Schema::hasColumn('payments', 'order_type')) {
                $table->dropColumn('order_type');
            }
        });
    }
};
