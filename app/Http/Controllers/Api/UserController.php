<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\Permissions\AccountPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $request = request();
        $this->authorizePermission($request, 'user.page');
        $this->authorizePermission($request, 'user.view');

        $users = User::query()
            ->with(['roles:id,name,label', 'shipper', 'client'])
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $users->map(fn (User $user): array => $this->formatUserPayload($request, $user))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'user.create');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'phone' => ['nullable', 'string', 'max:50', 'unique:users,phone'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'is_blocked' => ['nullable', 'boolean'],
            'account_type' => ['required', Rule::in([0, 1, 2, '0', '1', '2'])],
        ]);

        $normalizedAccountType = $this->normalizeAccountType($data['account_type']);

        $typeSpecificData = $request->validate([
            'commission_rate' => [
                Rule::requiredIf($normalizedAccountType === 'shipper'),
                Rule::prohibitedIf($normalizedAccountType !== 'shipper'),
                'numeric',
                'min:0',
                'max:999.99',
            ],
            'address' => [
                Rule::requiredIf($normalizedAccountType === 'client'),
                Rule::prohibitedIf($normalizedAccountType !== 'client'),
                'string',
                'max:255',
            ],
            'plan_id' => [
                Rule::requiredIf($normalizedAccountType === 'client'),
                Rule::prohibitedIf($normalizedAccountType !== 'client'),
                'integer',
                'exists:plans,id',
            ],
            'shipping_content_id' => [
                Rule::requiredIf($normalizedAccountType === 'client'),
                Rule::prohibitedIf($normalizedAccountType !== 'client'),
                'integer',
                'exists:content,id',
            ],
        ]);

        $data = array_merge($data, $typeSpecificData);

        $this->authorizeEditableColumns($request, array_keys($data));
        $this->authorizeAccountTypeButton($request, (int) $data['account_type']);

        $user = DB::transaction(function () use ($data): User {
            $userData = collect($data)->only([
                'name',
                'username',
                'phone',
                'avatar',
                'password',
                'is_blocked',
            ])->toArray();

            $user = User::query()->create($userData);
            $this->applyAccountType($user, $this->normalizeAccountType($data['account_type']), $data);

            return $user;
        });

        $user->load(['roles:id,name,label', 'shipper', 'client']);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $this->formatUserPayload($request, $user),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $request = request();
        $this->authorizePermission($request, 'user.page');
        $this->authorizePermission($request, 'user.view');

        $user->load(['roles:id,name,label', 'shipper', 'client']);

        return response()->json($this->formatUserPayload($request, $user));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorizePermission($request, 'user.update');

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('users', 'phone')->ignore($user->id)],
            'avatar' => ['nullable', 'string', 'max:255'],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'is_blocked' => ['nullable', 'boolean'],
            'account_type' => ['sometimes', 'required', Rule::in([0, 1, 2, '0', '1', '2'])],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'address' => ['nullable', 'string', 'max:255'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'shipping_content_id' => ['nullable', 'integer', 'exists:content,id'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        if (array_key_exists('account_type', $data)) {
            $this->authorizeAccountTypeButton($request, (int) $data['account_type']);
        }

        DB::transaction(function () use ($data, $user): void {
            $userData = collect($data)->only([
                'name',
                'username',
                'phone',
                'avatar',
                'password',
                'is_blocked',
            ])->toArray();

            if (! empty($userData)) {
                $user->update($userData);
            }

            if (array_key_exists('account_type', $data)) {
                $this->applyAccountType($user, $this->normalizeAccountType($data['account_type']), $data);
            } elseif ($user->shipper && array_key_exists('commission_rate', $data)) {
                $user->shipper()->update([
                    'commission_rate' => $data['commission_rate'],
                ]);
            } elseif ($user->client) {
                $clientData = collect($data)->only(['address', 'plan_id', 'shipping_content_id'])->toArray();

                if (! empty($clientData)) {
                    $user->client()->update($clientData);
                }
            }
        });

        $user->load(['roles:id,name,label', 'shipper', 'client']);

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $this->formatUserPayload($request, $user),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorizePermission(request(), 'user.delete');

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    private function applyAccountType(User $user, string $accountType, array $data): void
    {
        if ($accountType === 'shipper') {
            $user->client()?->delete();

            $user->shipper()->updateOrCreate(
                ['user_id' => $user->id],
                ['commission_rate' => $data['commission_rate'] ?? 0]
            );

            $this->syncRoleIfExists($user, 'shipper');

            return;
        }

        if ($accountType === 'client') {
            $user->shipper()?->delete();

            $user->client()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'address' => $data['address'] ?? null,
                    'plan_id' => $data['plan_id'] ?? null,
                    'shipping_content_id' => $data['shipping_content_id'] ?? null,
                ]
            );

            $this->syncRoleIfExists($user, 'client');

            return;
        }

        $user->shipper()?->delete();
        $user->client()?->delete();
        $this->syncRoleIfExists($user, 'user');
    }

    private function syncRoleIfExists(User $user, string $roleName): void
    {
        $role = Role::query()->where('name', $roleName)->first();

        if ($role) {
            $user->syncRoles([$roleName]);
        }
    }

    private function formatUserPayload(Request $request, User $user): array
    {
        $payload = $user->toArray();
        $accountType = $user->shipper ? 'shipper' : ($user->client ? 'client' : 'user');
        $payload['account_type'] = $this->accountTypeToCode($accountType);

        $result = [];

        foreach (AccountPermissionMap::USER_VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $payload['id'];
        }
        if (! array_key_exists('account_type', $result)) {
            $result['account_type'] = $payload['account_type'];
        }

        return $result;
    }

    private function normalizeAccountType(string|int $accountType): string
    {
        return match ((string) $accountType) {
            '0' => 'user',
            '1' => 'client',
            '2' => 'shipper',
            default => (string) $accountType,
        };
    }

    private function accountTypeToCode(string $accountType): int
    {
        return match ($accountType) {
            'client' => 1,
            'shipper' => 2,
            default => 0,
        };
    }

    /**
     * @param array<int, string> $columns
     */
    private function authorizeEditableColumns(Request $request, array $columns): void
    {
        foreach ($columns as $column) {
            $permission = AccountPermissionMap::USER_EDIT_COLUMNS[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission for column [{$column}]: {$permission}");
            }
        }
    }

    private function authorizeAccountTypeButton(Request $request, int $accountTypeCode): void
    {
        $permission = AccountPermissionMap::ACCOUNT_TYPE_BUTTON_PERMISSIONS[$accountTypeCode] ?? null;

        if ($permission && ! $request->user()?->can($permission)) {
            abort(403, "Missing button permission for account type [{$accountTypeCode}]: {$permission}");
        }
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }
}
