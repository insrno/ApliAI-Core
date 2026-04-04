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
        Schema::table('resumes', function (Blueprint $table) {
            if (! Schema::hasColumn('resumes', 'user_id')) {
                $table->unsignedBigInteger('user_id');
            }

            if (! Schema::hasColumn('resumes', 'file_name')) {
                $table->string('file_name');
            }

            if (! Schema::hasColumn('resumes', 'file_path')) {
                $table->string('file_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            if (Schema::hasColumn('resumes', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }

            if (Schema::hasColumn('resumes', 'file_name')) {
                $table->dropColumn('file_name');
            }

            if (Schema::hasColumn('resumes', 'file_path')) {
                $table->dropColumn('file_path');
            }
        });
    }
};
