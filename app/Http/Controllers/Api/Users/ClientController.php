<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Support\Permissions\AccountPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Change client plan (plan_id).
     */
    public function changePlan(Request $request, Client $client): JsonResponse
    {
        $this->authorizePermission($request, 'client.update');
        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ]);
        $client->plan_id = $data['plan_id'];
        $client->save();
        return response()->json([
            'message' => 'Client plan updated.',
            'plan_id' => $client->plan_id,
        ]);
    }
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client.page');
        $this->authorizePermission($request, 'client.view');

        $clients = Client::query()
            ->with(['user:id,name,username,phone', 'plan', 'shippingContent'])
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $clients->map(fn (Client $client): array => $this->filterVisibleColumns($request, $client))->values()
        );
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        $this->authorizePermission($request, 'client.page');
        $this->authorizePermission($request, 'client.view');

        $client->load(['user:id,name,username,phone', 'plan', 'shippingContent']);

        return response()->json($this->filterVisibleColumns($request, $client));
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function filterVisibleColumns(Request $request, Client $client): array
    {
        $raw = $client->toArray();
        $result = [];

        foreach (AccountPermissionMap::CLIENT_VIEW_COLUMNS as $column => $permission) {
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
