# Developer Handover: API Flow & Design

This section defines the RESTful principles and request flow of the K-YEY API.

## Core API Flow

1. **Request**: Frontend (Vue or Flutter) sends a request to `APP_URL/api/v1/`.
2. **Auth**: `Sanctum` verifies the Bearer Token.
3. **Middleware**:
    - `ApiTokenFromQuery`: Allows token passing via the `token` parameter.
    - `UpdateLoginSessionLastSeen`: Logs IP and time of the last request.
4. **Route**: `routes/api.php` maps the request to a Controller.
5. **Logic**: Controller method processes the request.
6. **Response**: Always returned in JSON format.

## Authentication Flow

- **Login**: `POST /api/login` returning user info and an `access_token`.
- **Logout**: `POST /api/logout` revoking the current Sanctum token.
- **Session**: Tokens are stored in the browser's `Cookies` and the mobile app's `SharedPreferences`.

## Authorization (RBAC)

- **Controller-level**:

  ```php
  $this->authorizePermission($request, 'order.page');
  ```

- **Permission Mapping**: Permission names are standardized (e.g., `order.view`, `user.delete`).
- **Super Admin Bypass**: Centralized in `AppServiceProvider`.

## Error Handling

- **401**: Unauthenticated (Missing or invalid token).
- **403**: Forbidden (Unauthorized to perform this action/permission missing).
- **422**: Unprocessable Entity (Validation error).
- **404**: Not Found (Resource doesn't exist).
- **500**: Internal Server Error (Database or logic failure).

## API Guidelines

1. Always use **CamelCase** for JSON responses.
2. Follow the **REST Verbs**:
    - `GET`: Read data.
    - `POST`: Create data.
    - `PATCH`: Update data.
    - `DELETE`: Remove data.
3. Include **Pagination**: All `index` methods must support `per_page` and `page` parameters.
