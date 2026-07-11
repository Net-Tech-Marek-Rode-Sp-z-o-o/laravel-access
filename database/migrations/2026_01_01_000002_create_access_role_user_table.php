<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use NetCode\Access\Infrastructure\DataAccess\Tables;

return new class extends Migration
{
    public function up(): void
    {
        $table = Tables::roleUser();

        Schema::create($table, function (Blueprint $blueprint): void {
            $blueprint->uuid('role_id');
            $blueprint->uuid('user_id');
            $blueprint->uuid('scope_id')->nullable();
            $blueprint->timestamp('granted_at');

            $blueprint->index(['user_id', 'scope_id']);
            $blueprint->index('role_id');

            $blueprint->foreign('role_id')
                ->references('id')
                ->on(Tables::roles())
                ->cascadeOnDelete();
        });

        // Postgres treats NULLs as distinct in a unique index, so the global (scope_id IS NULL)
        // grants need their own partial index to stay unique.
        DB::statement(sprintf(
            'CREATE UNIQUE INDEX %s_scoped_unique ON %s (role_id, user_id, scope_id) WHERE scope_id IS NOT NULL',
            $table,
            $table,
        ));

        DB::statement(sprintf(
            'CREATE UNIQUE INDEX %s_global_unique ON %s (role_id, user_id) WHERE scope_id IS NULL',
            $table,
            $table,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::roleUser());
    }
};
