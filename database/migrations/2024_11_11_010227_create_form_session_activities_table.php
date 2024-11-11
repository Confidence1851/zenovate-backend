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
        Schema::create('form_session_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid("form_session_id")->constrained("form_sessions");
            $table->foreignId("user_id")->nullable()->constrained("users");
            $table->string("activity");
            $table->text("message");
            $table->json("metadata")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_session_activities');
    }
};
