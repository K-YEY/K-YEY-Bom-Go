# User Guide: Role Permissions

This section defines the behavior and access of different project user roles.

## 🛡 Super Admin

The **Super Admin** role bypasses all standard permission checks (via `Gate::before`). They can see all orders, modify all settings, and view all audit logs.

## 💼 Admin

Has operational access but might have specific permissions missing (e.g. `order.delete` or `user.update-role`).

## 🏢 Client

- **View own orders**: Can only see orders where `client_user_id` matches their own ID.
- **Create orders**: Can create shipments but cannot modify them after a `Final Status` (SHIPPED/DELIVERED).
- **Settlements**: Can view their own settlement documentation.

## 🛵 Shipper (Representative)

- **Assigned List**: Can only see orders where `shipper_user_id` matches their own ID.
- **Mobile Access**: Uses the Flutter app for scanning and status updates.
- **Collections**: Can see a summary of orders that are `DELIVERED` but not yet reconciliated.

## 👁 Follower

A **Read-only** role with access to `order.view` but no buttons or actions (no `create`, `update`, `delete`).

## Permission Troubleshooting

If you cannot see a button or feature that should be available to your role, contact your system administrator to verify your character/role assignment in the database.
