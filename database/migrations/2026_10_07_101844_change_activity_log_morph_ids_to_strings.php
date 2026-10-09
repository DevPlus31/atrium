<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original bigint morph ids reject UUIDs on PostgreSQL and MySQL.
     * Subjects are keyed by UUIDs (users, products, orders) and integers
     * (roles), so subject_id becomes a string; causers are always users, so
     * causer_id becomes a uuid that joins against users.id. PostgreSQL cannot
     * cast bigint to uuid directly, hence the string step in between.
     */
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->string('subject_id', 36)->nullable()->change();
            $table->string('causer_id', 36)->nullable()->change();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('alter table activity_log alter column causer_id type uuid using causer_id::uuid');

            return;
        }

        Schema::table('activity_log', function (Blueprint $table): void {
            $table->uuid('causer_id')->nullable()->change();
        });
    }
};
