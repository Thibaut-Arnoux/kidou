<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milk_goals', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('baby_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('goal');
            $table->timestamps();

            $table->unique(['baby_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milk_goals');
    }
};
