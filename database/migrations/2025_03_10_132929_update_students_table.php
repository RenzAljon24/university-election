<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // ✅ First, rename 'department' to 'college' if applicable
            if (Schema::hasColumn('students', 'department') && !Schema::hasColumn('students', 'college')) {
                $table->renameColumn('department', 'college');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            // ✅ Now, add missing columns only if they don't already exist
            if (!Schema::hasColumn('students', 'middle_name')) {
                $table->string('middle_name')->nullable();
            }
            if (!Schema::hasColumn('students', 'college')) {
                $table->string('college')->nullable();
            }
            if (!Schema::hasColumn('students', 'course')) {
                $table->string('course')->nullable();
            }
            if (!Schema::hasColumn('students', 'session')) {
                $table->string('session')->nullable();
            }
            if (!Schema::hasColumn('students', 'semester')) {
                $table->string('semester')->nullable();
            }
            if (!Schema::hasColumn('students', 'learning_modality')) {
                $table->string('learning_modality')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // ✅ Drop added columns only if they exist
            if (Schema::hasColumn('students', 'middle_name')) {
                $table->dropColumn('middle_name');
            }
            if (Schema::hasColumn('students', 'course')) {
                $table->dropColumn('course');
            }
            if (Schema::hasColumn('students', 'session')) {
                $table->dropColumn('session');
            }
            if (Schema::hasColumn('students', 'semester')) {
                $table->dropColumn('semester');
            }
            if (Schema::hasColumn('students', 'learning_modality')) {
                $table->dropColumn('learning_modality');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            // ✅ Rename 'college' back to 'department' only if necessary
            if (Schema::hasColumn('students', 'college') && !Schema::hasColumn('students', 'department')) {
                $table->renameColumn('college', 'department');
            }
        });
    }
};
