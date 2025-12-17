<?php

use App\Helpers\OrderTypeConstants;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum column to include 'cart'
        // MySQL requires dropping and recreating the enum or using ALTER TABLE MODIFY
        if (Schema::hasColumn('payments', 'order_type')) {
            $enumValues = implode("','", OrderTypeConstants::ORDER_TYPES);
            DB::statement("ALTER TABLE `payments` MODIFY COLUMN `order_type` ENUM('{$enumValues}') DEFAULT '" . OrderTypeConstants::REGULAR . "'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values (without 'cart')
        if (Schema::hasColumn('payments', 'order_type')) {
            // Update any 'cart' values to 'regular' before modifying the enum
            DB::statement("UPDATE `payments` SET `order_type` = '" . OrderTypeConstants::REGULAR . "' WHERE `order_type` = '" . OrderTypeConstants::CART . "'");
            DB::statement("ALTER TABLE `payments` MODIFY COLUMN `order_type` ENUM('" . OrderTypeConstants::REGULAR . "', '" . OrderTypeConstants::ORDER_SHEET . "') DEFAULT '" . OrderTypeConstants::REGULAR . "'");
        }
    }
};
