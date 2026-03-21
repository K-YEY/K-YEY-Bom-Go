<?php

namespace App\Imports;

use App\Models\Order;
use App\Models\Governorate;
use App\Models\City;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Client;
use App\Models\PlanPrice;
use App\Models\Shipper;

class OrdersImport implements ToCollection, WithHeadingRow
{
    protected $userId;
    protected $successCount = 0;
    protected $errors = [];

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function collection(Collection $rows)
    {
        $governorates = Governorate::all()->pluck('id', 'name')->toArray();
        $cities = City::all()->groupBy('governorate_id')->map(function($group) {
            return $group->pluck('id', 'name')->toArray();
        })->toArray();

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2; // Heading is row 1

            try {
                // 1. Extract Client ID from "id_client" column (Format: Name (ID))
                $clientInfo = $row['id_client'] ?? null;
                $clientUserId = null;
                if ($clientInfo && preg_match('/\(([^)]+)\)/', $clientInfo, $matches)) {
                    $clientUserId = (int) $matches[1];
                }

                if (!$clientUserId) {
                    $this->errors[] = "Row {$lineNumber}: Client missing or invalid format (Name (ID))";
                    continue;
                }

                // 2. Validate Governorate & City
                $govName = trim($row['governorate'] ?? '');
                $govId = $governorates[$govName] ?? null;

                if (!$govId) {
                    $this->errors[] = "Row {$lineNumber}: Governorate '{$govName}' not found.";
                    continue;
                }

                // Handle normalized Arabic heading "المنطقة"
                $cityName = trim($row['almntqh'] ?? $row['almn_t_qa'] ?? $row['المنطقة'] ?? '');
                $cityId = isset($cities[$govId][$cityName]) ? $cities[$govId][$cityName] : null;

                // Handle status from excel or default to OUT_FOR_DELIVERY
                $statusInput = strtoupper(trim($row['alhalh'] ?? $row['الحالة'] ?? ''));
                $status = 'OUT_FOR_DELIVERY';
                if ($statusInput === 'DELIVERED' || $statusInput === 'مستلم' || $statusInput === 'تم التسليم') {
                    $status = 'DELIVERED';
                }

                // 3. Build data
                $orderData = [
                    'external_code' => $row['kwd_alshrkh'] ?? $row['كود_الشركة'] ?? null,
                    'client_user_id' => $clientUserId,
                    'receiver_name' => $row['name'] ?? 'N/A',
                    'phone' => $row['alrqm'] ?? $row['الرقم'] ?? '',
                    'phone_2' => $row['alrqm_altany'] ?? $row['الرقم_التاني'] ?? null,
                    'governorate_id' => $govId,
                    'city_id' => $cityId,
                    'address' => $row['address'] ?? '',
                    'total_amount' => (float) ($row['als_ar'] ?? $row['السعر'] ?? 0),
                    'order_note' => $row['almlhwzh'] ?? $row['الملحوظة'] ?? null,
                    'status' => $status, 
                    'created_by' => $this->userId,
                    'approval_status' => 'APPROVED', // Imports are usually approved
                ];

                // 4. Resolve Default Shipper & Apply Financials
                $orderData = $this->resolveDefaultShipper($orderData);
                $orderData = $this->applyAutomaticFinancials($orderData);

                // Auto-generate Code
                $orderData['code'] = $this->generateOrderCode();

                if (!empty($orderData['shipper_user_id'])) {
                    $orderData['shipper_date'] = now()->toDateString();
                }

                Order::create($orderData);
                $this->successCount++;

            } catch (\Exception $e) {
                Log::error("Import Error Row {$lineNumber}: " . $e->getMessage());
                $this->errors[] = "Row {$lineNumber}: " . $e->getMessage();
            }
        }
    }

    public function getResults()
    {
        return [
            'success_count' => $this->successCount,
            'errors' => $this->errors,
        ];
    }

    private function applyAutomaticFinancials(array $data): array
    {
        $clientUserId = $data['client_user_id'];
        $governorateId = $data['governorate_id'];
        $shipperUserId = $data['shipper_user_id'] ?? null;
        $totalAmount = $data['total_amount'];

        $shippingFee = $this->resolveShippingFee((int) $clientUserId, (int) $governorateId);
        $commissionAmount = $this->resolveCommissionAmount($shipperUserId ? (int) $shipperUserId : null);
        $total = round((float) $totalAmount, 2);

        $data['total_amount'] = $total;
        $data['shipping_fee'] = round((float) $shippingFee, 2);
        $data['commission_amount'] = round((float) $commissionAmount, 2);
        $data['company_amount'] = round($data['shipping_fee'] - $data['commission_amount'], 2);
        $data['cod_amount'] = round($total - $data['shipping_fee'], 2);

        return $data;
    }

    private function resolveShippingFee(int $clientUserId, int $governorateId): float
    {
        $client = Client::where('user_id', $clientUserId)->first();
        if (!$client) return 0;

        $planId = $client->plan_id;
        if (!$planId) return (float) ($client->shipping_fee ?? 0);

        $price = PlanPrice::where('plan_id', $planId)
            ->where('governorate_id', $governorateId)
            ->value('price');

        return (float) ($price ?? ($client->shipping_fee ?? 0));
    }

    private function resolveCommissionAmount(?int $shipperUserId): float
    {
        if ($shipperUserId === null) return 0;
        $shipper = Shipper::where('user_id', $shipperUserId)->first();
        return (float) ($shipper->commission_rate ?? 0);
    }

    private function resolveDefaultShipper(array $data): array
    {
        $governorateId = $data['governorate_id'];
        $defaultShipperUserId = Governorate::whereKey($governorateId)->value('default_shipper_user_id');
        if ($defaultShipperUserId) {
            $data['shipper_user_id'] = (int) $defaultShipperUserId;
        }
        return $data;
    }

    private function generateOrderCode(): string
    {
        $prefix = Setting::where('key', 'order_prefix')->value('value') ?? 'ORD';
        $digits = (int) (Setting::where('key', 'order_digits')->value('value') ?? 5);

        $lastOrder = Order::orderByDesc('id')->first();
        $nextNumber = $lastOrder ? ($lastOrder->id + 1) : 1;

        return $prefix . str_pad((string) $nextNumber, $digits, '0', STR_PAD_LEFT);
    }
}
