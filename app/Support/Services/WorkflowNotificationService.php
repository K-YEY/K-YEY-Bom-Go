<?php

namespace App\Support\Services;

use App\Models\Client;
use App\Models\ClientReturn;
use App\Models\ClientSettlement;
use App\Models\Order;
use App\Models\Plan;
use App\Models\ShipperCollection;
use App\Models\ShipperReturn;
use App\Models\User;
use App\Notifications\WorkflowEventNotification;
use Illuminate\Database\Eloquent\Model;

class WorkflowNotificationService
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public function handleModelUpdated(Model $model, array $changes): void
    {
        if ($model instanceof Order && array_key_exists('status', $changes)) {
            $this->notifyOrderStatusChanged($model, (string) $model->getOriginal('status'), (string) $model->status);

            return;
        }

        if ($model instanceof Client && array_key_exists('plan_id', $changes)) {
            $this->notifyClientPlanChanged($model, $model->getOriginal('plan_id'), $model->plan_id);

            return;
        }

        if ($model instanceof ShipperCollection && array_key_exists('status', $changes) && $model->status === 'COMPLETED') {
            $this->notifyCollectionCompleted($model);

            return;
        }

        if ($model instanceof ClientSettlement && array_key_exists('status', $changes) && $model->status === 'COMPLETED') {
            $this->notifySettlementCompleted($model);

            return;
        }

        if ($model instanceof ShipperReturn && array_key_exists('status', $changes) && $model->status === 'COMPLETED') {
            $this->notifyShipperReturnCompleted($model);

            return;
        }

        if ($model instanceof ClientReturn && array_key_exists('status', $changes) && $model->status === 'COMPLETED') {
            $this->notifyClientReturnCompleted($model);
        }
    }

    public function handleModelCreated(Model $model): void
    {
        if (! $model instanceof Order) {
            return;
        }

        $this->autoUpgradeClientPlanIfThresholdExceeded($model);
    }

    private function notifyOrderStatusChanged(Order $order, string $oldStatus, string $newStatus): void
    {
        if ($oldStatus === $newStatus || $newStatus === '') {
            return;
        }

        $payload = [
            'type' => 'order.status.changed',
            'title' => 'تم تحديث حالة الطلب',
            'message' => "تم تغيير حالة الطلب {$order->code} من {$oldStatus} إلى {$newStatus}.",
            'order_id' => $order->id,
            'order_code' => $order->code,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ];

        $this->notifyUsersByIds([
            $order->client_user_id,
            $order->shipper_user_id,
        ], $payload);
    }

    private function notifyCollectionCompleted(ShipperCollection $collection): void
    {
        $payload = [
            'type' => 'shipper.collection.completed',
            'title' => 'تم إكمال التحصيل',
            'message' => "تم إكمال تحصيل الشحنات برقم {$collection->id}.",
            'shipper_collection_id' => $collection->id,
            'status' => $collection->status,
            'collection_date' => optional($collection->collection_date)->toDateString(),
        ];

        $this->notifyUsersByIds([$collection->shipper_user_id], $payload);
    }

    private function notifySettlementCompleted(ClientSettlement $settlement): void
    {
        $payload = [
            'type' => 'client.settlement.completed',
            'title' => 'تم إكمال تسوية العميل',
            'message' => "تمت تسوية العميل برقم {$settlement->id} بنجاح.",
            'client_settlement_id' => $settlement->id,
            'status' => $settlement->status,
            'settlement_date' => optional($settlement->settlement_date)->toDateString(),
        ];

        $this->notifyUsersByIds([$settlement->client_user_id], $payload);
    }

    private function notifyShipperReturnCompleted(ShipperReturn $return): void
    {
        $payload = [
            'type' => 'shipper.return.completed',
            'title' => 'تمت مرتجعات الشيبّر',
            'message' => "تم إكمال مرتجع الشيبّر برقم {$return->id}.",
            'shipper_return_id' => $return->id,
            'status' => $return->status,
            'return_date' => optional($return->return_date)->toDateString(),
        ];

        $this->notifyUsersByIds([$return->shipper_user_id], $payload);
    }

    private function notifyClientReturnCompleted(ClientReturn $return): void
    {
        $payload = [
            'type' => 'client.return.completed',
            'title' => 'تمت مرتجعات العميل',
            'message' => "تم إكمال مرتجع العميل برقم {$return->id}.",
            'client_return_id' => $return->id,
            'status' => $return->status,
            'return_date' => optional($return->return_date)->toDateString(),
        ];

        $this->notifyUsersByIds([$return->client_user_id], $payload);
    }

    private function notifyClientPlanChanged(Client $client, mixed $oldPlanId, mixed $newPlanId): void
    {
        if (! $newPlanId || $oldPlanId === $newPlanId) {
            return;
        }

        $oldPlanName = Plan::query()->whereKey($oldPlanId)->value('name');
        $newPlan = Plan::query()->find($newPlanId);

        if (! $newPlan) {
            return;
        }

        $payload = [
            'type' => 'client.plan.changed',
            'title' => 'تم نقل خطتك تلقائيا',
            'message' => 'تم نقل حسابك إلى خطة '.$newPlan->name.($oldPlanName ? " بدلا من {$oldPlanName}" : '').'.',
            'old_plan_id' => $oldPlanId,
            'old_plan_name' => $oldPlanName,
            'new_plan_id' => $newPlan->id,
            'new_plan_name' => $newPlan->name,
            'new_plan_order_count' => $newPlan->order_count,
        ];

        $this->notifyUsersByIds([$client->user_id], $payload);
    }

    private function autoUpgradeClientPlanIfThresholdExceeded(Order $order): void
    {
        if (! $order->client_user_id) {
            return;
        }

        $client = Client::query()
            ->with('plan:id,name,order_count')
            ->where('user_id', $order->client_user_id)
            ->first();

        if (! $client || ! $client->plan) {
            return;
        }

        $currentPlan = $client->plan;
        $currentCount = Order::query()->where('client_user_id', $order->client_user_id)->count();

        if ($currentCount <= $currentPlan->order_count) {
            return;
        }

        $nextPlan = Plan::query()
            ->where('order_count', '>', $currentPlan->order_count)
            ->where('order_count', '>=', $currentCount)
            ->orderBy('order_count')
            ->first();

        if (! $nextPlan) {
            $nextPlan = Plan::query()
                ->where('order_count', '>', $currentPlan->order_count)
                ->orderBy('order_count')
                ->first();
        }

        if (! $nextPlan || $nextPlan->id === $client->plan_id) {
            return;
        }

        $client->update([
            'plan_id' => $nextPlan->id,
        ]);
    }

    /**
     * @param  array<int, int|string|null>  $ids
     * @param  array<string, mixed>  $payload
     */
    private function notifyUsersByIds(array $ids, array $payload): void
    {
        $userIds = collect($ids)
            ->filter(fn ($id): bool => ! is_null($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        User::query()
            ->whereIn('id', $userIds->all())
            ->get()
            ->each(function (User $user) use ($payload): void {
                $user->notify(new WorkflowEventNotification($payload));
            });
    }
}
