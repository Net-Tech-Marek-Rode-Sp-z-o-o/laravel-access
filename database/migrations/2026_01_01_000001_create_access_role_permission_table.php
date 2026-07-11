<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NetCode\Access\Domain\ValueObjects\PermissionId;
use NetCode\Access\Infrastructure\DataAccess\Tables;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(Tables::rolePermission(), function (Blueprint $table): void {
            $table->uuid('role_id');
            $table->string('permission', PermissionId::MAX_LENGTH);

            $table->primary(['role_id', 'permission']);

            $table->foreign('role_id')
                ->references('id')
                ->on(Tables::roles())
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::rolePermission());
    }
};
