<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'setting.page');

        $settings = Setting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get(['group', 'key', 'value']);

        $grouped = $settings
            ->groupBy('group')
            ->map(fn ($items): array => $items->pluck('value', 'key')->all())
            ->all();

        return response()->json($grouped);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'setting.page');

        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
        ]);

        foreach ($data['settings'] as $key => $value) {
            Setting::query()
                ->where('key', $key)
                ->update(['value' => $value]);
        }

        $settings = Setting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get(['group', 'key', 'value']);

        $grouped = $settings
            ->groupBy('group')
            ->map(fn ($items): array => $items->pluck('value', 'key')->all())
            ->all();

        return response()->json([
            'message' => 'Settings updated successfully.',
            'data' => $grouped,
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }
}
