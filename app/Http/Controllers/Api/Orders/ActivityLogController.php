<?php

namespace App\Http\Controllers\Api\Orders;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\Permissions\ActivityLogPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'activity-log.page');
        $this->authorizePermission($request, 'activity-log.view');

        $logs = ActivityLog::query()
            ->with([
                'user:id,name,username',
                'loginSession:id,ip_address,country,city',
            ])
            ->orderByDesc('id')
            ->paginate(50);

        return response()->json(
            $logs->map(fn (ActivityLog $log): array => $this->filterVisibleColumns($request, $log))
        );
    }

    public function show(Request $request, ActivityLog $activityLog): JsonResponse
    {
        $this->authorizePermission($request, 'activity-log.page');
        $this->authorizePermission($request, 'activity-log.view');

        $activityLog->load([
            'user:id,name,username,phone',
            'loginSession:id,ip_address,country,city,device_name',
        ]);

        return response()->json($this->filterVisibleColumns($request, $activityLog));
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function filterVisibleColumns(Request $request, ActivityLog $log): array
    {
        $payload = $log->toArray();
        $result = [];

        foreach (ActivityLogPermissionMap::VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $log->id;
        }

        return $result;
    }
}
