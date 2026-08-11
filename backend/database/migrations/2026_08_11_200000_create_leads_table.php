<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('company')->nullable();
            $table->string('website')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedTinyInteger('score')->nullable();
            $table->json('enrichment')->nullable();
            $table->timestamp('enriched_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
