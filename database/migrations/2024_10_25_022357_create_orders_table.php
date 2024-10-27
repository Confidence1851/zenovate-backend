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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained("users");
            $table->foreignId("payment_id")->constrained("payments");
            $table->foreignUuid("form_session_id")->constrained("form_sessions");
            $table->double("shipping_fee")->nullable();
            $table->double("sub_total")->nullable();
            $table->double("total")->nullable();
            $table->string("status")->nullable();
            $table->string("pdf_path")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
