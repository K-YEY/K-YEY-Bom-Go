<?php

namespace App\Http\Controllers\Api\Orders;

use App\Http\Controllers\Controller;
use App\Models\ShipperReturn;
use App\Support\Permissions\CollectionsReturnsSettlementsPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShipperReturnController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-return.page');
        $this->authorizePermission($request, 'shipper-return.view');

        $returns = ShipperReturn::query()
            ->with(['shipper:id,name'])
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $returns->map(fn (ShipperReturn $return): array => $this->filterVisibleColumns($request, $return))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-return.create');

        $data = $request->validate([
            'shipper_user_id' => ['required', 'exists:users,id'],
            'return_date' => ['required', 'date'],
            'number_of_orders' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        $data['created_by'] = $request->user()->id;
        $return = ShipperReturn::query()->create($data);

        return response()->json([
            'message' => 'Shipper return created successfully.',
            'data' => $this->filterVisibleColumns($request, $return),
        ], 201);
    }

    public function show(Request $request, ShipperReturn $shipperReturn): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-return.page');
        $this->authorizePermission($request, 'shipper-return.view');

        $shipperReturn->load(['shipper:id,name', 'orders']);

        return response()->json($this->filterVisibleColumns($request, $shipperReturn));
    }

    public function update(Request $request, ShipperReturn $shipperReturn): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-return.update');

        $data = $request->validate([
            'shipper_user_id' => ['sometimes', 'required', 'exists:users,id'],
            'return_date' => ['sometimes', 'required', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        $shipperReturn->update($data);

        return response()->json([
            'message' => 'Shipper return updated successfully.',
            'data' => $this->filterVisibleColumns($request, $shipperReturn),
        ]);
    }

    public function destroy(Request $request, ShipperReturn $shipperReturn): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-return.delete');

        $shipperReturn->delete();

        return response()->json([
            'message' => 'Shipper return deleted successfully.',
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function authorizeEditableColumns(Request $request, array $columns): void
    {
        foreach ($columns as $column) {
            $permission = CollectionsReturnsSettlementsPermissionMap::SHIPPER_RETURN_EDIT_COLUMNS[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission: {$permission}");
            }
        }
    }

    private function filterVisibleColumns(Request $request, ShipperReturn $return): array
    {
        $payload = $return->toArray();
        $result = [];

        foreach (CollectionsReturnsSettlementsPermissionMap::SHIPPER_RETURN_VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $return->id;
        }

        return $result;
    }
}
