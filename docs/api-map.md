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
- `GET /settings`
- `PUT /settings`
- `GET|POST /governorates`
- `GET|PUT|PATCH|DELETE /governorates/{governorate}`
- `GET|POST /plans`
- `GET|PUT|PATCH|DELETE /plans/{plan}`
