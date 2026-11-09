<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
 * M00Core Routes — mounted at /api/v1 (prefix applied in bootstrap/app.php)
 *
 * All routes require authentication via Sanctum.
 * Permission checks are applied at the controller or policy level.
 */

Route::prefix('core')->middleware(['auth:sanctum', 'module:M00Core'])->group(function () {

    // ─── Branches ─────────────────────────────────────────────────────────
    Route::get('branches', [\Modules\M00Core\Http\Controllers\BranchController::class, 'index'])->middleware('permission:core.branch.view');
    Route::post('branches', [\Modules\M00Core\Http\Controllers\BranchController::class, 'store'])->middleware('permission:core.branch.create');
    Route::get('branches/{branch}', [\Modules\M00Core\Http\Controllers\BranchController::class, 'show'])->middleware('permission:core.branch.view');
    Route::put('branches/{branch}', [\Modules\M00Core\Http\Controllers\BranchController::class, 'update'])->middleware('permission:core.branch.update');
    Route::delete('branches/{branch}', [\Modules\M00Core\Http\Controllers\BranchController::class, 'destroy'])->middleware('permission:core.branch.delete');

    // ─── Users ────────────────────────────────────────────────────────────
    Route::get('users', [\Modules\M00Core\Http\Controllers\UserController::class, 'index'])->middleware('permission:core.user.view');
    Route::post('users', [\Modules\M00Core\Http\Controllers\UserController::class, 'store'])->middleware('permission:core.user.create');
    Route::get('users/{user}', [\Modules\M00Core\Http\Controllers\UserController::class, 'show'])->middleware('permission:core.user.view');
    Route::put('users/{user}', [\Modules\M00Core\Http\Controllers\UserController::class, 'update'])->middleware('permission:core.user.update');
    Route::delete('users/{user}', [\Modules\M00Core\Http\Controllers\UserController::class, 'destroy'])->middleware('permission:core.user.delete');

    // ─── Roles & Permissions ──────────────────────────────────────────────
    Route::get('roles', [\Modules\M00Core\Http\Controllers\RoleController::class, 'index'])->middleware('permission:core.role.view');
    Route::post('roles', [\Modules\M00Core\Http\Controllers\RoleController::class, 'store'])->middleware('permission:core.role.create');
    Route::get('roles/{role}', [\Modules\M00Core\Http\Controllers\RoleController::class, 'show'])->middleware('permission:core.role.view');
    Route::put('roles/{role}', [\Modules\M00Core\Http\Controllers\RoleController::class, 'update'])->middleware('permission:core.role.update');
    Route::delete('roles/{role}', [\Modules\M00Core\Http\Controllers\RoleController::class, 'destroy'])->middleware('permission:core.role.delete');
    Route::get('permissions', [\Modules\M00Core\Http\Controllers\PermissionController::class, 'index'])->middleware('permission:core.role.view');

    // ─── Audit Log ────────────────────────────────────────────────────────
    Route::get('audit-log', [\Modules\M00Core\Http\Controllers\AuditLogController::class, 'index'])->middleware('permission:core.audit.view');

    // ─── Settings ─────────────────────────────────────────────────────────
    Route::get('settings', [\Modules\M00Core\Http\Controllers\SettingController::class, 'index'])->middleware('permission:core.settings.view');
    Route::put('settings', [\Modules\M00Core\Http\Controllers\SettingController::class, 'update'])->middleware('permission:core.settings.update');

    // ─── License & Modules ────────────────────────────────────────────────
    Route::prefix('license')->group(function () {
        Route::get('/', [\Modules\M00Core\Http\Controllers\LicenseController::class, 'show'])->middleware('permission:core.license.view');
        Route::post('/activate', [\Modules\M00Core\Http\Controllers\LicenseController::class, 'activate'])->middleware('permission:core.license.manage');
        Route::post('/upload', [\Modules\M00Core\Http\Controllers\LicenseController::class, 'upload'])->middleware('permission:core.license.manage');
    });

    Route::get('modules', [\Modules\M00Core\Http\Controllers\ModuleStatusController::class, 'index']);
});

// ─── Auth routes (public) ─────────────────────────────────────────────────
Route::prefix('auth')->middleware('module:M00Core')->group(function () {
    Route::post('/login', [\Modules\M00Core\Http\Controllers\Auth\LoginController::class, 'login']);
    Route::post('/forgot-password', [\Modules\M00Core\Http\Controllers\Auth\PasswordResetController::class, 'sendLink']);
    Route::post('/reset-password', [\Modules\M00Core\Http\Controllers\Auth\PasswordResetController::class, 'reset']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [\Modules\M00Core\Http\Controllers\Auth\LoginController::class, 'logout']);
        Route::get('/me', [\Modules\M00Core\Http\Controllers\Auth\LoginController::class, 'me']);
        Route::post('/2fa/enable', [\Modules\M00Core\Http\Controllers\Auth\TwoFactorController::class, 'enable']);
        Route::post('/2fa/confirm', [\Modules\M00Core\Http\Controllers\Auth\TwoFactorController::class, 'confirm']);
        Route::post('/2fa/disable', [\Modules\M00Core\Http\Controllers\Auth\TwoFactorController::class, 'disable']);
        Route::post('/2fa/verify', [\Modules\M00Core\Http\Controllers\Auth\TwoFactorController::class, 'verify']);
    });
});

// ─── Public Agency Showcase (no auth needed) ──────────────────────────────
Route::prefix('public')->middleware('module:M00Core')->group(function () {
    Route::get('/branches/{code}', [\Modules\M00Core\Http\Controllers\BranchController::class, 'publicShow']);
});

