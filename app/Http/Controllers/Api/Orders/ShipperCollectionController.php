<?php

namespace App\Http\Controllers\Api\Orders;

use App\Http\Controllers\Controller;
use App\Models\ShipperCollection;
use App\Support\Permissions\CollectionsReturnsSettlementsPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShipperCollectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-collection.page');
        $this->authorizePermission($request, 'shipper-collection.view');

        $collections = ShipperCollection::query()
            ->with(['shipper:id,name'])
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $collections->map(fn (ShipperCollection $collection): array => $this->filterVisibleColumns($request, $collection))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-collection.create');

        $data = $request->validate([
            'shipper_user_id' => ['required', 'exists:users,id'],
            'collection_date' => ['required', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'number_of_orders' => ['required', 'integer', 'min:0'],
            'shipper_fees' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        $data['created_by'] = $request->user()->id;
        $collection = ShipperCollection::query()->create($data);

        return response()->json([
            'message' => 'Shipper collection created successfully.',
            'data' => $this->filterVisibleColumns($request, $collection),
        ], 201);
    }

    public function show(Request $request, ShipperCollection $shipperCollection): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-collection.page');
        $this->authorizePermission($request, 'shipper-collection.view');

        $shipperCollection->load(['shipper:id,name', 'orders']);

        return response()->json($this->filterVisibleColumns($request, $shipperCollection));
    }

    public function update(Request $request, ShipperCollection $shipperCollection): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-collection.update');

        $data = $request->validate([
            'shipper_user_id' => ['sometimes', 'required', 'exists:users,id'],
            'collection_date' => ['sometimes', 'required', 'date'],
            'shipper_fees' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        $shipperCollection->update($data);

        return response()->json([
            'message' => 'Shipper collection updated successfully.',
            'data' => $this->filterVisibleColumns($request, $shipperCollection),
        ]);
    }

    public function destroy(Request $request, ShipperCollection $shipperCollection): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-collection.delete');

        $shipperCollection->delete();

        return response()->json([
            'message' => 'Shipper collection deleted successfully.',
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function authorizeEditableColumns(Request $request, array $columns): void
    {
        foreach ($columns as $column) {
            $permission = CollectionsReturnsSettlementsPermissionMap::SHIPPER_COLLECTION_EDIT_COLUMNS[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission: {$permission}");
            }
        }
    }

    private function filterVisibleColumns(Request $request, ShipperCollection $collection): array
    {
        $payload = $collection->toArray();
        $result = [];

        foreach (CollectionsReturnsSettlementsPermissionMap::SHIPPER_COLLECTION_VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $collection->id;
        }

        return $result;
    }
}
