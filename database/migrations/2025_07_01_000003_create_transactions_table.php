<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bucket_id')->constrained('buckets')->restrictOnDelete();
            $table->foreignId('deposit_id')->nullable()->constrained('deposits')->restrictOnDelete();
            $table->integer('amount');
            $table->string('type'); // 'allocation', 'expense', 'sweep', 'transfer'
            $table->uuid('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
