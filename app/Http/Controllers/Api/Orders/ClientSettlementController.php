<?php

namespace App\Http\Controllers\Api\Orders;

use App\Http\Controllers\Controller;
use App\Models\ClientSettlement;
use App\Support\Permissions\CollectionsReturnsSettlementsPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientSettlementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client-settlement.page');
        $this->authorizePermission($request, 'client-settlement.view');

        $settlements = ClientSettlement::query()
            ->with(['client:id,name'])
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $settlements->map(fn (ClientSettlement $settlement): array => $this->filterVisibleColumns($request, $settlement))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client-settlement.create');

        $data = $request->validate([
            'client_user_id' => ['required', 'exists:users,id'],
            'settlement_date' => ['required', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'number_of_orders' => ['required', 'integer', 'min:0'],
            'fees' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        $data['created_by'] = $request->user()->id;
        $settlement = ClientSettlement::query()->create($data);

        return response()->json([
            'message' => 'Client settlement created successfully.',
            'data' => $this->filterVisibleColumns($request, $settlement),
        ], 201);
    }

    public function show(Request $request, ClientSettlement $clientSettlement): JsonResponse
    {
        $this->authorizePermission($request, 'client-settlement.page');
        $this->authorizePermission($request, 'client-settlement.view');

        $clientSettlement->load(['client:id,name', 'orders']);

        return response()->json($this->filterVisibleColumns($request, $clientSettlement));
    }

    public function update(Request $request, ClientSettlement $clientSettlement): JsonResponse
    {
        $this->authorizePermission($request, 'client-settlement.update');

        $data = $request->validate([
            'client_user_id' => ['sometimes', 'required', 'exists:users,id'],
            'settlement_date' => ['sometimes', 'required', 'date'],
            'fees' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        $clientSettlement->update($data);

        return response()->json([
            'message' => 'Client settlement updated successfully.',
            'data' => $this->filterVisibleColumns($request, $clientSettlement),
        ]);
    }

    public function destroy(Request $request, ClientSettlement $clientSettlement): JsonResponse
    {
        $this->authorizePermission($request, 'client-settlement.delete');

        $clientSettlement->delete();

        return response()->json([
            'message' => 'Client settlement deleted successfully.',
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function authorizeEditableColumns(Request $request, array $columns): void
    {
        foreach ($columns as $column) {
            $permission = CollectionsReturnsSettlementsPermissionMap::CLIENT_SETTLEMENT_EDIT_COLUMNS[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission: {$permission}");
            }
        }
    }

    private function filterVisibleColumns(Request $request, ClientSettlement $settlement): array
    {
        $payload = $settlement->toArray();
        $result = [];

        foreach (CollectionsReturnsSettlementsPermissionMap::CLIENT_SETTLEMENT_VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $settlement->id;
        }

        return $result;
    }
}
