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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid("form_session_id")->constrained("form_sessions");
            $table->foreignId("user_id")->nullable()->constrained("users");
            $table->string("reference", 50)->unique();
            $table->string("payment_reference", 200)->nullable()->unique();
            $table->string("receipt_url")->nullable();
            $table->string("gateway");
            $table->string("currency");
            $table->double("sub_total");
            $table->double("shipping_fee")->nullable();
            $table->double("total");
            $table->string("address")->nullable();
            $table->string("postal_code")->nullable();
            $table->string("city")->nullable();
            $table->string("province")->nullable();
            $table->string("country")->nullable();
            $table->string("phone");
            $table->string("status");
            $table->text("metadata")->nullable();
            $table->timestamp("paid_at")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
