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
        Schema::table('jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('jobs', 'title')) {
                $table->string('title')->after('id');
            }
            if (! Schema::hasColumn('jobs', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (! Schema::hasColumn('jobs', 'location')) {
                $table->string('location')->nullable()->after('description');
            }
            if (! Schema::hasColumn('jobs', 'job_type')) {
                $table->string('job_type')->default('full-time')->after('location');
            }
            if (! Schema::hasColumn('jobs', 'salary')) {
                $table->decimal('salary', 10, 2)->nullable()->after('job_type');
            }
            if (! Schema::hasColumn('jobs', 'complete')) {
                $table->boolean('complete')->default(false)->after('salary');
            }
            if (! Schema::hasColumn('jobs', 'company_name')) {
                $table->string('company_name')->nullable()->after('complete');
            }
            if (! Schema::hasColumn('jobs', 'company_logo')) {
                $table->string('company_logo')->nullable()->after('company_name');
            }
            if (! Schema::hasColumn('jobs', 'category')) {
                $table->string('category')->nullable()->after('company_logo');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs');
    }
};
