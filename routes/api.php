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
Route::delete('roles/{role_id}', DeleteRoleController::class)->whereUuid('role_id');
Route::put('roles/{role_id}/permissions', SetRolePermissionsController::class)->whereUuid('role_id');

Route::post('subjects/{subject_id}/roles', AssignRoleController::class)->whereUuid('subject_id');
Route::delete('subjects/{subject_id}/roles/{role_id}', RevokeRoleController::class)
    ->whereUuid(['subject_id', 'role_id']);
