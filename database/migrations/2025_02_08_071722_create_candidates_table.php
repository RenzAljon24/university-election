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
        if (!Schema::hasTable('candidates')) {
            Schema::create('candidates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('election_id')->constrained()->onDelete('cascade');
                $table->foreignId('partylist_id')->nullable()->constrained()->onDelete('set null');
                $table->string('name');
                $table->string('photo')->nullable();
                $table->string('position');
                $table->timestamps();
            });

        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
