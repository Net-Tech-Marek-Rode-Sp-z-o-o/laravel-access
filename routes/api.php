<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use NetCode\Access\Presentation\Http\Controllers\AssignRoleController;
use NetCode\Access\Presentation\Http\Controllers\CreateRoleController;
use NetCode\Access\Presentation\Http\Controllers\DeleteRoleController;
use NetCode\Access\Presentation\Http\Controllers\ListRolesController;
use NetCode\Access\Presentation\Http\Controllers\RevokeRoleController;
use NetCode\Access\Presentation\Http\Controllers\SetRolePermissionsController;

Route::get('roles', ListRolesController::class);
Route::post('roles', CreateRoleController::class);
Route::delete('roles/{roleId}', DeleteRoleController::class);
Route::put('roles/{roleId}/permissions', SetRolePermissionsController::class);

Route::post('subjects/{subjectId}/roles', AssignRoleController::class);
Route::delete('subjects/{subjectId}/roles/{roleId}', RevokeRoleController::class);
