<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Area\GovernorateController;
use App\Http\Controllers\Api\Plan\PlanController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ShippingContent\ContentController;
use App\Http\Controllers\Api\Expense\ExpenseAclController;
use App\Http\Controllers\Api\Expense\ExpenseCategoryController;
use App\Http\Controllers\Api\Expense\ExpenseController;
use App\Http\Controllers\Api\Users\ClientController;
use App\Http\Controllers\Api\Users\ShipperController;
use App\Http\Controllers\Api\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('acl/expenses', [ExpenseAclController::class, 'matrix']);
    Route::apiResource('expense-categories', ExpenseCategoryController::class);
    Route::apiResource('expenses', ExpenseController::class);
    Route::apiResource('clients', ClientController::class)->only(['index', 'show']);
    Route::apiResource('contents', ContentController::class);
    Route::apiResource('shippers', ShipperController::class)->only(['index', 'show']);
    Route::apiResource('users', UserController::class);
    Route::get('settings', [SettingController::class, 'index']);
    Route::put('settings', [SettingController::class, 'update']);
    Route::apiResource('governorates', GovernorateController::class);
    Route::apiResource('plans', PlanController::class);
});
