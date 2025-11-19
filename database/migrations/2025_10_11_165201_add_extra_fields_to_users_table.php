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
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'description')) {
                $table->text('description')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'is_admin')) {
                $table->boolean('is_admin')->default(false)->after('description');
            }
            if (!Schema::hasColumn('users', 'is_employer')) {
                $table->boolean('is_employer')->default(false)->after('is_admin');
            }
            if (!Schema::hasColumn('users', 'profile_photo_')) {
                // Store the file path, not the image data
                $table->string('profile_photo')->nullable()->after('is_employer');
            }
        });

        // Create the saved_jobs pivot table
        if (!Schema::hasTable('saved_jobs')) {
            Schema::create('saved_jobs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('job_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                
                $table->unique(['user_id', 'job_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['description', 'is_admin', 'is_employer', 'profile_photo']);
        });
        
        Schema::dropIfExists('saved_jobs');
    }
};