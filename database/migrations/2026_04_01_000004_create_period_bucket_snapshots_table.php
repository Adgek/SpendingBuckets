<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period_bucket_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('periods')->cascadeOnDelete();
            $table->foreignId('bucket_id')->constrained('buckets')->restrictOnDelete();
            $table->integer('monthly_target')->default(0);
            $table->integer('funded')->default(0);
            $table->integer('paid')->default(0);
            $table->integer('swept')->default(0);
            $table->integer('closing_balance')->default(0);
            $table->timestamps();

            $table->unique(['period_id', 'bucket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_bucket_snapshots');
    }
};
