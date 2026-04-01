# Order Statuses & Reasons Hierarchy

This guide explains how the **Shipya** platform handles order status transitions and the logic behind "Refused Reasons."

## Core Status Flow

The lifecycle of an order is strictly governed by its `status` field. For representatives (Shippers) using the mobile app, the primary transitions are:

- **OUT_FOR_DELIVERY**: The starting state for the representative.
- **DELIVERED**: Successfully completed shipment. Selectable reasons (like **Partial Delivery**) can further refine this state and its financial impact.
- **HOLD**: Temporary delay (e.g., client not answering, requested later date).
- **UNDELIVERED**: Final failure to deliver (e.g., wrong address, client refused).

## Status Reasons (Refused Reasons)

To maintain high data quality, the system allows (and sometimes requires) linking a "Reason" to a status update. This is available for **DELIVERED**, **HOLD**, and **UNDELIVERED**.

### Force Selection & Logic

### 1. Mandatory for Failure

When a Shipper updates an order to `HOLD` or `UNDELIVERED`, a `reason_id` or `note` is **Required**.

### 2. Selection for Delivered

Even when choosing **DELIVERED**, the representative can select a status reason. This is primarily used for **Partial Delivery (تسلم جزئي)**, where the representative took a different amount than planned.

## Advanced Reason Behavior (Flags)

The `RefusedReason` model carries two critical flags that change how the order is processed financially:

### 1. Partial Delivery (`is_edit_amount`)

If a chosen reason has `is_edit_amount = true` (e.g., **"تسلم جزئي"**):

- The frontend/mobile app should allow the user to input a new `total_amount`.
- The backend will accept the new `total_amount` and automatically re-calculate the `cod_amount`, `shipping_fee`, and `commissions` using the `applyAutomaticFinancials` logic.

### 2. Order Clearing (`is_clear`)

If a chosen reason has `is_clear = true` (e.g., **"تم التصفير"** or **"هدية"**):

- The system automatically sets **all financial fields** (`total_amount`, `shipping_fee`, `commission_amount`, etc.) to **0**.
- This is used for promotional orders or specific business cases where the shipping is free of charge.

### Logic Implementation (`ShipperAppController.php`)

```php
if (in_array($status, ['HOLD', 'UNDELIVERED']) && !$reasonId && !$note) {
    throw ValidationException::withMessages([
        'reason_id' => ['A reason or note is required for HOLD or UNDELIVERED status.'],
    ]);
}
```

## Database Integration

1. **RefusedReason Model**: Stores the predefined list of reasons (e.g., "Customer Refused", "No Answer").
2. **Order Relationship**: Orders have a `BelongsToMany` relationship with `RefusedReason`.
3. **Synchronization**: When a status is updated with a reason, the system uses `syncWithoutDetaching()` to record the history of reasons for that specific order.

## Financial Lock (is_shipper_collected)

**Crucial Rule**: A status **cannot** be changed if the order has already been part of a financial settlement (`is_shipper_collected = true`). This prevents data inconsistencies in accounting.

```php
if ($order->is_shipper_collected) {
    abort(422, 'Cannot change status. Order already settled.');
}
```

## How to add a new Reason

1. Go to the **Admin Dashboard (Filament)**.
2. Navigate to **Management > Refused Reasons**.
3. Add a new record with a name and optional description.
4. The new reason will automatically appear in the mobile app's dropdown next time the representative initializes the app (`/api/shipper-app/init`).
