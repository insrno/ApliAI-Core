<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add foreign key constraints
        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreign('resume_id')
                ->references('id')->on('resumes')
                ->onDelete('cascade');

            $table->foreign('job_description_id')
                ->references('id')->on('job_descriptions')
                ->onDelete('cascade');
        });

        // 2. Change job_descriptions.description from text to longText
        Schema::table('job_descriptions', function (Blueprint $table) {
            $table->longText('description')->change();
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropForeign(['resume_id']);
            $table->dropForeign(['job_description_id']);
        });

        Schema::table('job_descriptions', function (Blueprint $table) {
            $table->text('description')->change();
        });
    }
};
