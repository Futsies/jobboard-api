<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id(); // Primary key

            // Foreign key for the applicant (user)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Foreign key for the job being applied to
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');

            // Store paths to the uploaded files
            $table->string('resume_path'); // Path to the resume file
            $table->string('cover_letter_path')->nullable(); // Path to the cover letter (optional)

            // Optional: Add a status field later (e.g., pending, viewed, rejected)
            // $table->string('status')->default('pending');

            $table->timestamps(); // created_at and updated_at

            // Optional: Prevent duplicate applications from the same user for the same job
            $table->unique(['user_id', 'job_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_applications');
    }
};
