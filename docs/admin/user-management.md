# Admin Operations: User Management

Manage all user accounts, roles, and permissions on the K-YEY platform.

## Management Section

- **Location**: `dashboards/users-crm`.
- **Primary Modules**: Clients, Shippers, Staff/Internal Users.

## Roles & Permissions

### Creating Users

- Always specify the correct **Account Type** (Admin, Client, or Shipper).
- **Required**: `username`, `phone`, `password`, and **Role**.

### Assigning Roles

- Use the **Roles & Permissions** page in the System Settings.
- **Roles**: super-admin, admin, shipper, client, follower.
- Each role is a collection of specific **Permissions** (e.g. `order.create`).

### Blocking Users

- Use the `is_blocked` flag to immediately disable a user's access without deleting their data.
- **Effect**: Token-based Sanctum requests will fail for that user (check `UpdateLoginSessionLastSeen` logic).

## Audit & Activity Logs

- Check the **Activity Logs** page to see who performed what action on which user account.
- **Trace**: All CRUD operations on the `User` model are automatically logged via the `EntityObserver`.
