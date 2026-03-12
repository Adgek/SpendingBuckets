<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buckets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // 'fixed' or 'excess'
            $table->integer('monthly_target')->nullable();
            $table->integer('priority_order')->nullable();
            $table->integer('cap')->nullable();
            $table->boolean('sweeps_excess')->default(false);
            $table->integer('excess_percentage')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buckets');
    }
};
