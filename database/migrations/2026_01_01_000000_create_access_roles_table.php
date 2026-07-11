<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Access\Infrastructure\DataAccess\Tables;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(Tables::roles(), function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', RoleName::MAX_LENGTH)->unique();
            $table->string('label');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::roles());
    }
};
