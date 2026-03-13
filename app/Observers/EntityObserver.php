<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Support\Services\ActivityLogService;
use App\Support\Services\WorkflowNotificationService;
use Illuminate\Database\Eloquent\Model;

class EntityObserver
{
    public const IGNORED_COLUMNS = [
        'created_at',
        'updated_at',
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function created(Model $model): void
    {
        if ($model instanceof ActivityLog) {
            return;
        }

        ActivityLogService::logCreated($model);
        app(WorkflowNotificationService::class)->handleModelCreated($model);
    }

    public function updated(Model $model): void
    {
        if ($model instanceof ActivityLog) {
            return;
        }

        $changes = $model->getChanges();

        if (empty($changes)) {
            return;
        }

        $oldValues = [];
        $newValues = [];

        foreach ($changes as $key => $newValue) {
            if (in_array($key, self::IGNORED_COLUMNS)) {
                continue;
            }

            $oldValue = $model->getOriginal($key);
            $oldValues[$key] = $oldValue;
            $newValues[$key] = $newValue;
        }

        if (empty($oldValues)) {
            return;
        }

        ActivityLogService::logUpdated($model, $oldValues, $newValues);
        app(WorkflowNotificationService::class)->handleModelUpdated($model, $changes);
    }

    public function deleted(Model $model): void
    {
        if ($model instanceof ActivityLog) {
            return;
        }

        ActivityLogService::logDeleted($model);
    }

    public function restored(Model $model): void
    {
        if ($model instanceof ActivityLog) {
            return;
        }

        ActivityLogService::logAction($model, 'restored', 'Restored '.class_basename($model));
    }

    public function forceDeleted(Model $model): void
    {
        if ($model instanceof ActivityLog) {
            return;
        }

        ActivityLogService::logAction($model, 'force_deleted', 'Permanently deleted '.class_basename($model));
    }
}
