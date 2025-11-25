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
            // Make form_session_id nullable for direct checkout
            $table->foreignUuid('form_session_id')->nullable()->change();
        });
        
        // Note: metadata column already exists as text and is cast as 'array' in Payment model
        // Laravel's array cast handles JSON encoding/decoding automatically
        // No need to change the column type
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Revert form_session_id to not nullable (if needed)
            // Note: This might fail if there are existing null values
            // $table->foreignUuid('form_session_id')->nullable(false)->change();
            
            // Revert metadata to text if needed
            // $table->text('metadata')->nullable()->change();
        });
    }
};
