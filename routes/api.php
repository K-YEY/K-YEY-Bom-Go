<?php

use App\Http\Controllers\Api\Area\GovernorateController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AclMatrixController;
use App\Http\Controllers\Api\Expense\ExpenseCategoryController;
use App\Http\Controllers\Api\Expense\ExpenseController;
use App\Http\Controllers\Api\Operations\MaterialController;
use App\Http\Controllers\Api\Operations\MaterialRequestController;
use App\Http\Controllers\Api\Operations\MaterialRequestItemController;
use App\Http\Controllers\Api\Operations\PickupRequestController;
use App\Http\Controllers\Api\Operations\VisitController;
use App\Http\Controllers\Api\Orders\OrderController;
use App\Http\Controllers\Api\Orders\ShipperCollectionController;
use App\Http\Controllers\Api\Orders\ShipperReturnController;
use App\Http\Controllers\Api\Orders\ClientSettlementController;
use App\Http\Controllers\Api\Orders\ClientReturnController;
use App\Http\Controllers\Api\Orders\ActivityLogController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\Plan\PlanController;
use App\Http\Controllers\Api\RefusedReasonController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ShippingContent\ContentController;
use App\Http\Controllers\Api\Users\ClientController;
use App\Http\Controllers\Api\Users\ShipperController;
use App\Http\Controllers\Api\Users\UserController;
use App\Http\Middleware\UpdateLoginSessionLastSeen;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', UpdateLoginSessionLastSeen::class])->group(function (): void {
    Route::get('acl', [AclMatrixController::class, 'matrix']);
    Route::apiResource('expense-categories', ExpenseCategoryController::class);
    Route::apiResource('expenses', ExpenseController::class);
    Route::apiResource('clients', ClientController::class)->only(['index', 'show']);
    Route::apiResource('contents', ContentController::class);
    Route::apiResource('shippers', ShipperController::class)->only(['index', 'show']);
    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class)->only(['index', 'show']);
    Route::apiResource('refused-reasons', RefusedReasonController::class);
    Route::get('settings', [SettingController::class, 'index']);
    Route::put('settings', [SettingController::class, 'update']);
    Route::apiResource('governorates', GovernorateController::class);
    Route::apiResource('plans', PlanController::class);
    Route::apiResource('materials', MaterialController::class);
    Route::apiResource('material-requests', MaterialRequestController::class);
    Route::apiResource('material-request-items', MaterialRequestItemController::class);
    Route::apiResource('pickup-requests', PickupRequestController::class);
    Route::apiResource('visits', VisitController::class);
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('shipper-collections', ShipperCollectionController::class);
    Route::apiResource('shipper-returns', ShipperReturnController::class);
    Route::apiResource('client-settlements', ClientSettlementController::class);
    Route::apiResource('client-returns', ClientReturnController::class);
    Route::apiResource('activity-logs', ActivityLogController::class)->only(['index', 'show']);
});
