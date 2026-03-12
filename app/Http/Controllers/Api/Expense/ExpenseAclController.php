<?php

namespace App\Http\Controllers\Api\Expense;

use App\Http\Controllers\Controller;
use App\Support\Permissions\AreaPlanPermissionMap;
use App\Support\Permissions\AccountPermissionMap;
use App\Support\Permissions\ContentPermissionMap;
use App\Support\Permissions\ExpensePermissionMap;
use App\Support\Permissions\SettingPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseAclController extends Controller
{
    public function matrix(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 401, 'Unauthenticated');

        return response()->json([
            'pages' => [
                'expense.page' => $user->can('expense.page'),
                'expense-category.page' => $user->can('expense-category.page'),
                'user.page' => $user->can('user.page'),
                'client.page' => $user->can('client.page'),
                'shipper.page' => $user->can('shipper.page'),
                'content.page' => $user->can('content.page'),
                'setting.page' => $user->can('setting.page'),
                'governorate.page' => $user->can('governorate.page'),
                'plan.page' => $user->can('plan.page'),
            ],
            'actions' => [
                ...$this->permissionState($user, array_column(ExpensePermissionMap::ACTION_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(AccountPermissionMap::ACTION_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(ContentPermissionMap::ACTION_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(AreaPlanPermissionMap::ACTION_PERMISSIONS, 'name')),
            ],
            'setting_pages' => $this->permissionState($user, array_column(SettingPermissionMap::PAGE_PERMISSIONS, 'name')),
            'expense_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(ExpensePermissionMap::EXPENSE_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(ExpensePermissionMap::EXPENSE_EDIT_COLUMNS)),
            ],
            'expense_category_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(ExpensePermissionMap::EXPENSE_CATEGORY_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(ExpensePermissionMap::EXPENSE_CATEGORY_EDIT_COLUMNS)),
            ],
            'user_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(AccountPermissionMap::USER_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(AccountPermissionMap::USER_EDIT_COLUMNS)),
            ],
            'client_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(AccountPermissionMap::CLIENT_VIEW_COLUMNS))),
            ],
            'shipper_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(AccountPermissionMap::SHIPPER_VIEW_COLUMNS))),
            ],
            'content_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(ContentPermissionMap::CONTENT_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(ContentPermissionMap::CONTENT_EDIT_COLUMNS)),
            ],
            'governorate_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(AreaPlanPermissionMap::GOVERNORATE_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(AreaPlanPermissionMap::GOVERNORATE_EDIT_COLUMNS)),
            ],
            'city_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(AreaPlanPermissionMap::CITY_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(AreaPlanPermissionMap::CITY_EDIT_COLUMNS)),
            ],
            'plan_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(AreaPlanPermissionMap::PLAN_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(AreaPlanPermissionMap::PLAN_EDIT_COLUMNS)),
            ],
            'plan_price_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(AreaPlanPermissionMap::PLAN_PRICE_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(AreaPlanPermissionMap::PLAN_PRICE_EDIT_COLUMNS)),
            ],
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<string, bool>
     */
    private function permissionState(object $user, array $permissions): array
    {
        $result = [];

        foreach ($permissions as $permission) {
            $result[$permission] = $user->can($permission);
        }

        return $result;
    }
}
