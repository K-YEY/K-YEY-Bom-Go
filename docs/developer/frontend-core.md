# Developer Handover: Frontend & SPA Core

This section explains the Vue 3 and TypeScript architecture of the K-YEY dashboard.

## Core Stack

- **Framework**: Vue 3 (Composition API).
- **Tooling**: Vite + TypeScript.
- **Components**: Vuetify.
- **Routing**: `vue-router` with file-based routing.
- **Store**: Pinia (Check `resources/ts/stores/` for global state).
- **Icons**: Iconify.

## Frontend Directory Structure

- `resources/ts/@core/`: The base template components and logic.
- `resources/ts/@layouts/`: Layout components (Sidebar, Navbar).
- `resources/ts/pages/`: Page components indexed by direct URL.
- `resources/ts/plugins/`: Global plugins (i18n, Casl, axios).
- `resources/ts/views/`: Feature-specific views and widgets.

## Casl Authorization

The frontend uses **Casl** to determine which UI elements to show or hide.

- **Logic**: During the login process (`resources/ts/pages/pages/authentication/login-v1.vue`), the app fetches a full list of permissions and roles from the `/acl` endpoint.
- **Update**: `ability.update(userAbilityRules)` sets the rules for the session.
- **Usage**:

  ```vue
  <VBtn v-if="can('order.create', 'all')">Add Order</VBtn>
  ```

  The logic is automatically updated upon refreshing or re-logging (check `login-v1.vue`'s `buildAbilityRulesFromAcl`).

## Layouts & Navigation

- **Sidebar**: Defined in `resources/ts/navigation/vertical/index.ts`.
- **VerticalNavLink.vue**: This component checks for `item.action` and `item.subject` using `can()` before rendering.

## Reusable Patterns

- **Forms**: Most forms are implemented in the `views/` directory.
- **Tables**: We use a recurring pattern for V-Data-Table with generic filters.
- **Styling**: Standardized SCSS is located in `resources/ts/assets/scss/`.

## Debugging

- Use the **Vue DevTools** to check state and events.
- Check the **Network** tab for API response JSON from the Laravel backend.
- Inspect **LocalStorage** to find the `userAbilityRules` and `accessToken`.
