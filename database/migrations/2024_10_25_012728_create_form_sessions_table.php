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
        Schema::create('form_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId("user_id")->nullable()->constrained("users");
            $table->json("data")->nullable();
            $table->json("metadata")->nullable();
            $table->string("status");
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_sessions');
    }
};
