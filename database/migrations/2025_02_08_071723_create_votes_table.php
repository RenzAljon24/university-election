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
        if (!Schema::hasTable('votes')) {
            Schema::create('votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('election_id')->constrained()->onDelete('cascade'); // Add this line
                $table->foreignId('candidate_id')->nullable()->constrained()->onDelete('cascade'); // Made nullable                
                $table->string('position'); // Ensure this line is properly placed

                $table->timestamp('voted_at')->useCurrent();
                $table->timestamps();
                $table->unique(['student_id', 'election_id', 'position']); // Update unique constraint
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
