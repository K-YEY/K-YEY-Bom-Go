# User Guide: Financial Operations

K-YEY Logistics features a professional financial reconciliation system to manage payments and collections.

## Shipper Collections

This process ensures the company receives all Cash on Delivery (COD) money collected by representatives (shippers).

1. **Selection**: Shipper picks delivered orders from their list.
2. **Submission**: Collects the `total_amount` for each order.
3. **Completion**: The order's `is_shipper_collected` status is set to `true`.

## Client Settlements

This process is when the company pays its clients for their delivered orders after deducting shipping fees.

1. **Selection**: Admin picks a specific Client and their un-settled orders.
2. **Calculation**: `Total Amount - Shipping Fee = Settlement Amount`.
3. **Completion**: The order's `is_client_settled` status is set to `true`.

## Commission Management

As a representative (Shipper), you may have a fixed or percentage-based commission rate (check `Shipper` model logic). This is automatically calculated on the `Order` model with each status update.

## Important Notes

- Once an order is fully settled and collected, it enters a `Final Reconciliation` state and can no longer be edited by anyone below a `Super Admin`.
- All financial transitions are logged in the `activity_logs` for transparency.
