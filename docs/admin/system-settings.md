# Admin Operations: System Settings

Manage the basic configuration of the K-YEY platform.

## Geographic Hierarchy

- **Governorates**: Primary regions (e.g. Cairo, Alexandria).
- **Cities**: Specific districts or areas within a Governorate.
- **Fees**: Shipping fees are often tied to the specific Governorate.

## Shipping Content Types

- **Module**: `dashbaords/content`.
- **Purpose**: Define what goes inside an order (e.g. Clothing, Electronics, Food).
- **Effect**: Used for tracking and for specific shipping plan calculations.

## Refused Reasons

- **Module**: `dashboards/refused-reason`.
- **Purpose**: Define why an order was `UNDELIVERED` or `HOLD`.
- **Effect**: Shippers MUST choose a reason from this list when updating an order status in the mobile app.

## General Settings

- **App Title**: Change the name of the system in the UI.
- **Contact Info**: Support phone numbers and email addresses.
- **Site Logos**: Branding for the main dashboard and the mobile app.

## Maintenance Notes

- All changes to **System Settings** take effect immediately across all user interfaces.
- Backup your database before performing major geographic or plan reconfigurations.
