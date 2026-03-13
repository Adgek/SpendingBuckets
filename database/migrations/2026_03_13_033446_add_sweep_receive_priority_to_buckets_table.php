<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buckets', function (Blueprint $table) {
            $table->boolean('receives_sweeps')->default(false)->after('sweeps_excess');
        });
    }

    public function down(): void
    {
        Schema::table('buckets', function (Blueprint $table) {
            $table->dropColumn('receives_sweeps');
        });
    }
};
