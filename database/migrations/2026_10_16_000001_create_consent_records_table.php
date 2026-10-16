<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('visitor_id')->index();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('version', 32);
            $table->json('granted');
            $table->boolean('gpc')->default(false);
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_records');
    }
};
