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
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();

            // Foreign key relationships
            $table->foreignId('job_application_id')->constrained('job_applications')->onDelete('cascade');
            $table->foreignId('employer_id')->comment('User ID of the employer/admin scheduling it')->constrained('users')->onDelete('cascade');

            // Interview details
            $table->string('title'); // User input (e.g., "First Round Technical Interview")
            $table->dateTime('scheduled_at'); // User input (Date and Time)

            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};