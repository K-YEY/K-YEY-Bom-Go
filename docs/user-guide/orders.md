# Order Management Guide

This section explains the core of the K-YEY system: the Order lifecycle, from creation to final reconciliation.

## Purpose

The **Orders Module** helps you manage the entire lifecycle of a shipment, providing data to Clients, Shippers, and the Company.

## Main Screens

1. **Order List**: Overview of all shipments under your control.
2. **Order Details**: Status tracking and specific order fields.
3. **Scan View**: Quick status update via barcode/QR code (representative-only).
4. **Specialized Filters**:
    - **HOLD & Out For Delivery**: Active shipments.
    - **Uncollected Shipper**: Deliveries not yet reconciliated.
    - **Rejected / Deleted**: Audit and trash views.

## User Actions

- **Create**: Add a new order. Required fields include `receiver_name`, `phone`, `address`, and `total_amount`.
- **Edit**: Update basic order info (if the order is NOT in a final status).
- **Change Status**: Move shipment to `DELIVERED`, `UNDELIVERED`, `HOLD`, etc.
- **Assign Shipper**: Link a representative (Shipper) to the order.

## Field Meanings

- **Code**: System-generated tracking ID.
- **External Code**: Client's reference ID (from their own system).
- **Receiver Name/Phone**: Destination contact details.
- **Governorate/City**: Used for pricing calc and routing.
- **Total Amount**: Collected from the receiver.
- **Shipping Fee**: The company's service fee.
- **COD Amount**: Final amount to be collected (calculated as `Total Amount - Shipping Fee`).
- **Status**: Current lifecycle stage (SHIPPED, DELIVERED, etc.).

## Validations & Rules

- **Non-Unique External Code**: The system warns but allows non-unique codes (check `OrderController@store` logic).
- **Permission to Update**: You cannot change an order once it is in a `ShipperCollection` or `ClientSettlement` (unless you have the `order.update-after-final-status` permission).
- **Manual status update**: Choosing `HOLD` or `UNDELIVERED` requires a **Refused Reason** or a Note.

## Common Mistakes

1. **Wrong Governorate**: This affects shipping fees and carrier assignment.
2. **Updating Settled Orders**: If you modify an order after it has been settled with a client, the financial reports will be inconsistent.

## Edge Cases

- **Duplicate external codes**: If two orders have the same external code, scanning that code might return multiple results.
- **Returned Orders**: When an order is `UNDELIVERED`, a `ClientReturn` or `ShipperReturn` flow must follow to return the physical package.
