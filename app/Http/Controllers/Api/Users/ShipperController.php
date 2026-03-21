<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Shipper;
use App\Support\Permissions\AccountPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipperController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shipper.page');
        $this->authorizePermission($request, 'shipper.view');

        $query = Shipper::query()->with(['user:id,name,username,phone']);

        if ($request->filled('q')) {
            $search = (string) $request->get('q');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', $search.'%')
                  ->orWhere('username', 'like', $search.'%')
                  ->orWhere('phone', 'like', $search.'%');
            });
        }

        $shippers = $query->orderByDesc('id')
            ->paginate($request->get('per_page', 20));

        $data = $shippers->getCollection()->map(fn (Shipper $shipper): array => $this->filterVisibleColumns($request, $shipper))->values();

        return response()->json([
            'data' => $data,
            'total' => $shippers->total(),
        ]);
    }

    public function show(Request $request, Shipper $shipper): JsonResponse
    {
        $this->authorizePermission($request, 'shipper.page');
        $this->authorizePermission($request, 'shipper.view');

        $shipper->load(['user:id,name,username,phone']);

        return response()->json($this->filterVisibleColumns($request, $shipper));
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function filterVisibleColumns(Request $request, Shipper $shipper): array
    {
        $raw = $shipper->toArray();
        $result = [];

        foreach (AccountPermissionMap::SHIPPER_VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $raw)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $raw[$column];
            }
        }

        return $result;
    }
}
