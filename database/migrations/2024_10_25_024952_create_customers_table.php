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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->nullable()->constrained("users");
            $table->foreignId("created_by")->nullable()->constrained("users");
            $table->string("dob")->nullable();
            $table->string("preferred_contact_method")->nullable();
            $table->string("address")->nullable();
            $table->string("postal_code")->nullable();
            $table->string("city")->nullable();
            $table->string("province")->nullable();
            $table->string("country")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
