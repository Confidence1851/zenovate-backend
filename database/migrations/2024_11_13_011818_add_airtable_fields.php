<?php

use App\Helpers\StatusConstants;
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
            $table->string("status")->default(StatusConstants::ACTIVE);
            $table->string("airtable_id")->unique()->nullable();
        });

        Schema::table('form_sessions', function (Blueprint $table) {
            $table->string("airtable_order_id")->unique()->nullable();
            $table->string("airtable_status")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
