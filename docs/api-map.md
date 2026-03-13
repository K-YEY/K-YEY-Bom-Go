# API Map

Base URL: `http://127.0.0.1:8000/api`

## Auth

- Public endpoint: `POST /login`
- All other endpoints require: `Authorization: Bearer <token>`

## Common Response Patterns

- `index` endpoints: return `array<object>` (columns may be filtered by permissions).
- `show` endpoints: return `object` (columns may be filtered by permissions).
- `store` endpoints: return `{ "message": string, "data": object }` with status `201`.
- `update` endpoints: return `{ "message": string, "data": object }`.
- `destroy` endpoints: return `{ "message": string }`.
- Permission failures: `403` with message like `Missing permission: ...`.
- Validation failures: `422` with Laravel validation payload.

## 1) Login

### POST /login

Request body:

```json
{
  "login": "admin",
  "password": "12345678",
  "device_name": "web-chrome"
}
```

Success response:

```json
{
  "message": "Login successful.",
  "token_type": "Bearer",
  "access_token": "1|...",
  "user": {
    "id": 1,
    "name": "System Admin",
    "username": "admin"
  }
}
```

Error responses:

- `422`: `{ "message": "Invalid credentials." }`
- `403`: `{ "message": "This account is blocked." }`

## 2) ACL Matrix

### GET /acl

Returns permissions matrix used by frontend:

```json
{
  "pages": { "expense.page": true, "user.page": true },
  "actions": { "expense.view": true, "user.create": false },
  "expense_columns": { "view": {}, "edit": {} },
  "expense_category_columns": { "view": {}, "edit": {} },
  "user_columns": { "view": {}, "edit": {} },
  "client_columns": { "view": {} },
  "shipper_columns": { "view": {} },
  "content_columns": { "view": {}, "edit": {} },
  "governorate_columns": { "view": {}, "edit": {} },
  "city_columns": { "view": {}, "edit": {} },
  "plan_columns": { "view": {}, "edit": {} },
  "plan_price_columns": { "view": {}, "edit": {} }
}
```

## 3) Users

### Endpoints

- `GET /users`
- `POST /users`
- `GET /users/{id}`
- `PUT|PATCH /users/{id}`
- `DELETE /users/{id}`

### Request highlights

- `account_type`: `0=user`, `1=client`, `2=shipper`
- If shipper: `commission_rate` required
- If client: `address`, `plan_id`, `shipping_content_id` required

### Response shape (filtered by column permissions)

```json
{
  "id": 1,
  "name": "...",
  "username": "...",
  "phone": "...",
  "is_blocked": false,
  "account_type": 2,
  "roles": [],
  "shipper": {},
  "client": null,
  "login_sessions": []
}
```

## 3.3) My Profile

- `GET /profile`
- `PATCH /profile`

Permission model:

- page: `user.profile.page`
- view: `user.profile.view`
- update: `user.profile.update`
- column view: `user.profile.column.*.view`
- column edit: `user.profile.column.*.edit`

`GET /profile` returns current authenticated user filtered by profile column permissions.

Example response:

```json
{
  "id": 1,
  "name": "System Admin",
  "username": "admin",
  "phone": "01000000000",
  "avatar": null,
  "roles": [
    {
      "id": 1,
      "name": "super-admin",
      "label": "Super Admin"
    }
  ],
  "account_type": 0,
  "created_at": "2026-03-13T10:00:00.000000Z",
  "updated_at": "2026-03-13T10:00:00.000000Z"
}
```

`PATCH /profile` accepts editable profile fields (based on column edit permissions):

```json
{
  "name": "Updated Name",
  "phone": "01012345678",
  "avatar": "https://.../avatar.png",
  "password": "new-password-123"
}
```

## 3.4) Notifications

- `GET /notifications`
- `PATCH /notifications/{notificationId}/read`
- `PATCH /notifications/read-all`

Notes:

- Notifications are stored in `notifications` table (database channel).
- `GET /notifications` returns paginated current-user notifications.
- `PATCH /notifications/{notificationId}/read` marks one notification as read.
- `PATCH /notifications/read-all` marks all unread notifications as read.

## 3.1) Roles

- `GET /roles`
- `POST /roles`
- `GET /roles/{id}`
- `PUT|PATCH /roles/{id}`
- `DELETE /roles/{id}`

### Store/Update request

`permissions` accepts permission names or ids.

```json
{
  "name": "sales-manager",
  "label": "Sales Manager",
  "is_active": true,
  "permissions": ["user.page", "user.view", "content.page"]
}
```

### Response shape

```json
{
  "data": {
    "id": 1,
    "name": "sales-manager",
    "label": "Sales Manager",
    "is_active": true,
    "permissions_count": 3,
    "users_count": 0,
    "permissions": [
      {
        "id": 5,
        "name": "user.page",
        "group": "users",
        "label": "Access User page",
        "type": "page"
      }
    ]
  }
}
```

## 3.2) Permissions

- `GET /permissions`
- `GET /permissions/{id}`

Optional filters on index:

- `group`
- `type`

### Index response

```json
{
  "data": [
    {
      "id": 1,
      "name": "user.page",
      "guard_name": "web",
      "group": "users",
      "label": "Access User page",
      "type": "page"
    }
  ],
  "meta": {
    "groups": ["users", "content"],
    "types": ["page", "action", "button", "column"]
  }
}
```

## 4) Clients (read-only)

- `GET /clients`
- `GET /clients/{id}`

Response: client objects (filtered by `client.column.*.view` permissions).

## 5) Shippers (read-only)

- `GET /shippers`
- `GET /shippers/{id}`

Response: shipper objects (filtered by `shipper.column.*.view` permissions).

## 6) Expense Categories

- `GET /expense-categories`
- `POST /expense-categories`
- `GET /expense-categories/{id}`
- `PUT|PATCH /expense-categories/{id}`
- `DELETE /expense-categories/{id}`

Store/Update request fields:

```json
{
  "name": "Office",
  "notes": "optional",
  "is_active": true
}
```

## 7) Expenses

- `GET /expenses`
- `POST /expenses`
- `GET /expenses/{id}`
- `PUT|PATCH /expenses/{id}`
- `DELETE /expenses/{id}`

Typical request fields:

```json
{
  "category_id": 1,
  "amount": 1200.5,
  "expense_date": "2026-03-12",
  "title": "Fuel",
  "notes": "optional",
  "status": "PENDING"
}
```

## 8) Contents

- `GET /contents`
- `POST /contents`
- `GET /contents/{id}`
- `PUT|PATCH /contents/{id}`
- `DELETE /contents/{id}`

Request:

```json
{
  "name": "Fragile"
}
```

## 9) Settings

- `GET /settings`
- `PUT /settings`

### GET /settings response

Grouped key/value map:

```json
{
  "general": {
    "site_name": "...",
    "currency": "..."
  },
  "shipping": {
    "default_shipping_cost": "..."
  }
}
```

### PUT /settings request

```json
{
  "settings": {
    "site_name": "Bom Go",
    "currency": "EGP"
  }
}
```

### PUT /settings response

```json
{
  "message": "Settings updated successfully.",
  "data": {
    "general": {
      "site_name": "Bom Go",
      "currency": "EGP"
    }
  }
}
```

## 10) Governorates + Cities

- `GET /governorates`
- `POST /governorates`
- `GET /governorates/{id}`
- `PUT|PATCH /governorates/{id}`
- `DELETE /governorates/{id}`

Store/Update request:

```json
{
  "name": "Cairo",
  "follow_up_hours": 24,
  "default_shipper_user_id": 5,
  "cities": ["Nasr City", "Heliopolis"]
}
```

Response shape (filtered):

```json
{
  "id": 1,
  "name": "Cairo",
  "follow_up_hours": 24,
  "default_shipper_user_id": 5,
  "defaultShipper": { "id": 5, "name": "..." },
  "cities": [{ "id": 1, "name": "Nasr City", "governorate_id": 1 }],
  "created_at": "...",
  "updated_at": "..."
}
```

## 11) Plans + Plan Prices

- `GET /plans`
- `POST /plans`
- `GET /plans/{id}`
- `PUT|PATCH /plans/{id}`
- `DELETE /plans/{id}`

Store/Update request:

```json
{
  "name": "Basic Plan",
  "order_count": 100,
  "prices": [
    { "governorate_id": 1, "price": 50.0 },
    { "governorate_id": 2, "price": 60.0 }
  ]
}
```

Response shape (filtered):

```json
{
  "id": 1,
  "name": "Basic Plan",
  "order_count": 100,
  "prices": [
    {
      "id": 10,
      "plan_id": 1,
      "governorate_id": 1,
      "price": "50.00",
      "governorate": { "id": 1, "name": "Cairo" }
    }
  ],
  "created_at": "...",
  "updated_at": "..."
}
```

## 23) Activity Logs (Audit Trail - Read Only)

### What is Activity Log?

Activity Log is an **audit trail** that records all important system operations:

- Create, Update, Delete operations
- Who performed the action
- What changed (old vs new values)
- When it happened
- From where (IP address, device)

### Endpoints

- `GET /activity-logs` - List all activity logs (paginated, 50 per page)
- `GET /activity-logs/{id}` - Get specific activity log details

### Index Response

```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "user": {
        "id": 1,
        "name": "Admin User",
        "username": "admin"
      },
      "login_session": {
        "id": 5,
        "ip_address": "192.168.1.1",
        "country": "Egypt",
        "city": "Cairo"
      },
      "entity_type": "Order",
      "entity_id": 145,
      "action": "updated",
      "label": "Updated Order #145",
      "old_values": {
        "status": "OUT_FOR_DELIVERY"
      },
      "new_values": {
        "status": "DELIVERED"
      },
      "ip_address": "192.168.1.1",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2026-03-12T14:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 1250
  }
}
```

### Permissions Required

- `activity-log.page` - Access Activity Log page
- `activity-log.view` - View activity logs
- `activity-log.column.*.view` - View specific columns

### Roles

- `activity-log-viewer` - Can view all activity logs (read-only)
- `super-admin` - Can view all activity logs

### Examples

```bash
# View all recent activities
http GET $BASE_URL/activity-logs Authorization:"Bearer $TOKEN"

# View activity on specific order
http GET "$BASE_URL/activity-logs?entity_type=Order&entity_id=145" \
  Authorization:"Bearer $TOKEN"

# View specific activity details
http GET $BASE_URL/activity-logs/1 Authorization:"Bearer $TOKEN"
```

### What Gets Logged Automatically

✅ **Create** - New record created
✅ **Update** - Record fields changed (only changed fields recorded)
✅ **Delete** - Record deleted
✅ **Restore** - Soft-deleted record restored
✅ **Force Delete** - Record permanently deleted

### Logged Entities

Activity is automatically logged for:

- User, Expense, ExpenseCategory
- Content, Setting
- Governorate, Plan, PlanPrice
- Material, MaterialRequest, MaterialRequestItem
- PickupRequest, Visit
- RefusedReason
- Order, ShipperCollection, ShipperReturn
- ClientSettlement, ClientReturn
- Role

### Activity Log Entry Fields

| Field           | Description                                                          |
| --------------- | -------------------------------------------------------------------- |
| `id`            | Log entry ID                                                         |
| `user_id`       | User who performed the action                                        |
| `user`          | User object (name, username)                                         |
| `login_session` | Session details (IP, country, city)                                  |
| `entity_type`   | Type of record (Order, User, etc.)                                   |
| `entity_id`     | ID of the record                                                     |
| `action`        | Operation type (created, updated, deleted, restored, status_changed) |
| `label`         | Human-readable description                                           |
| `old_values`    | Previous field values (JSON)                                         |
| `new_values`    | Updated field values (JSON)                                          |
| `ip_address`    | IP address of the requester                                          |
| `user_agent`    | Browser/device information                                           |
| `created_at`    | Timestamp of the action                                              |

### Common Use Cases

**1. Find who changed an order status**

```json
{
  "entity_type": "Order",
  "entity_id": 145,
  "action": "updated",
  "old_values": { "status": "PENDING" },
  "new_values": { "status": "SHIPPED" }
}
```

**2. Track user permission changes**

```json
{
  "entity_type": "User",
  "entity_id": 5,
  "action": "updated",
  "old_values": { "roles": [1, 2] },
  "new_values": { "roles": [1, 2, 3] }
}
```

**3. Audit account deletions**

```json
{
  "entity_type": "User",
  "entity_id": 10,
  "action": "deleted",
  "old_values": {
    /*entire user data*/
  },
  "user": { "id": 1, "name": "Admin" },
  "created_at": "2026-03-12T14:30:00Z"
}
```

### Important Notes

- **Read-Only** - Activity logs cannot be created, updated, or deleted via API
- **Auto-Logged** - All CRUD operations are automatically captured
- **Sensitive Fields Ignored** - Passwords and tokens are not logged
- **Pagination** - Index returns 50 records per page
- **User Info Included** - User who performed the action is always recorded
- **Location Tracking** - IP address and geolocation of the request

For complete examples and usage guide, see [ACTIVITY_LOG.md](./ACTIVITY_LOG.md)

## Full Route List (Current)

- `POST /login`
- `GET /acl`
- `GET|POST /expense-categories`
- `GET|PUT|PATCH|DELETE /expense-categories/{expense_category}`
- `GET|POST /expenses`
- `GET|PUT|PATCH|DELETE /expenses/{expense}`
- `GET /clients`
- `GET /clients/{client}`
- `GET|POST /contents`
- `GET|PUT|PATCH|DELETE /contents/{content}`
- `GET /shippers`
- `GET /shippers/{shipper}`
- `GET|POST /users`
- `GET|PUT|PATCH|DELETE /users/{user}`
- `GET|POST /roles`
- `GET|PUT|PATCH|DELETE /roles/{role}`
- `GET /permissions`
- `GET /permissions/{permission}`
- `GET /settings`
- `PUT /settings`
- `GET|POST /governorates`
- `GET|PUT|PATCH|DELETE /governorates/{governorate}`
- `GET|POST /plans`
- `GET|PUT|PATCH|DELETE /plans/{plan}`
- `GET|POST /orders`
- `GET|PUT|PATCH|DELETE /orders/{order}`
- `GET|POST /shipper-collections`
- `GET /shipper-collections/eligible-orders`
- `PATCH /shipper-collections/{shipper_collection}/approve`
- `PATCH /shipper-collections/{shipper_collection}/reject`
- `GET|PUT|PATCH|DELETE /shipper-collections/{shipper_collection}`
- `GET|POST /shipper-returns`
- `GET /shipper-returns/eligible-orders`
- `PATCH /shipper-returns/{shipper_return}/approve`
- `PATCH /shipper-returns/{shipper_return}/reject`
- `GET|PUT|PATCH|DELETE /shipper-returns/{shipper_return}`
- `GET|POST /client-settlements`
- `GET /client-settlements/eligible-orders`
- `PATCH /client-settlements/{client_settlement}/approve`
- `PATCH /client-settlements/{client_settlement}/reject`
- `GET|PUT|PATCH|DELETE /client-settlements/{client_settlement}`
- `GET|POST /client-returns`
- `GET /client-returns/eligible-orders`
- `PATCH /client-returns/{client_return}/approve`
- `PATCH /client-returns/{client_return}/reject`
- `GET|PUT|PATCH|DELETE /client-returns/{client_return}`
- `GET /activity-logs`
- `GET /activity-logs/{activity_log}`
- `GET|POST /materials`
- `GET|PUT|PATCH|DELETE /materials/{material}`
- `GET|POST /material-requests`
- `GET|PUT|PATCH|DELETE /material-requests/{material_request}`
- `GET|POST /material-request-items`
- `GET|PUT|PATCH|DELETE /material-request-items/{material_request_item}`
- `GET|POST /pickup-requests`
- `GET|PUT|PATCH|DELETE /pickup-requests/{pickup_request}`
- `GET|POST /visits`
- `GET|PUT|PATCH|DELETE /visits/{visit}`
- `GET|POST /refused-reasons`
- `GET|PUT|PATCH|DELETE /refused-reasons/{refused_reason}`
