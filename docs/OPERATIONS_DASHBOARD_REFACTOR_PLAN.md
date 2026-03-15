# Operations Dashboard Refactor Plan (Vue + Laravel)

## Scope and Goal

Transform the existing dashboard into a production operational cockpit for shipping workflows, without a blind redesign.

Workflow coverage target:

- Orders lifecycle
- Shipper collection
- Client settlement
- Shipper return
- Client return
- Pickup
- Material
- Approval/pending/completed/cancelled flows
- Role-based actions and sensitive operations (approve/reject/unlock)

## Implementation Status (March 2026)

Completed in codebase:

- Operations dashboard is now the primary authenticated landing route.
- Dashboard surface is API-first with dedicated aggregated backend endpoints:
  - GET /api/dashboard/operations/summary
  - GET /api/dashboard/operations/pending-approvals
  - GET /api/dashboard/operations/workflow-queues
  - GET /api/dashboard/operations/operations-requests
  - GET /api/dashboard/operations/exceptions
  - GET /api/dashboard/operations/recent-activity
- Dashboard modules are split into focused sections:
  - OperationsPendingApprovals
  - OperationsWorkflowQueues
  - OperationsOpsRequestsPanel
  - OperationsExceptionsPanel
  - OperationsRecentActivity
- Calendar/chat demo pages and references were removed.
- Profile is now a real page backed by backend /api/profile (view + update).
- User menu, shortcuts, search suggestions, and route redirects were migrated away from demo profile/account routes.
- Navigation composition was reduced to operations/admin essentials only.
- Legacy template page directories (old pages/\* demos, front-pages, wizard-examples, invoice/logistics app demos) were removed from the active codebase.

Notes:

- This document originally captured target design; several "recommended" items below are now implemented and can be treated as historical planning context.

## 1) Current-State Audit

### 1.1 Frontend dashboard composition

- Existing template dashboards are still active and mostly demo:
  - resources/ts/pages/dashboards/analytics.vue
  - resources/ts/pages/dashboards/ecommerce.vue
  - resources/ts/pages/dashboards/crm.vue
- The only real operations-aware view is:
  - resources/ts/views/dashboards/operations/OperationsWorkbench.vue
- Critical issue: this operations view is not mounted as a first-class dashboard route/page from resources/ts/pages.

### 1.2 Navigation and routing

- Dashboard nav still points to template entries:
  - resources/ts/navigation/vertical/dashboard.ts
  - resources/ts/navigation/horizontal/dashboard.ts
- No operations dashboard nav entry is present.
- Route guard uses CASL action/subject model:
  - resources/ts/@layouts/plugins/casl.ts
  - resources/ts/plugins/1.router/guards.ts
- Most pages do not define route-level workflow permission metadata.

### 1.3 Auth/ACL integration gap

- Login currently posts to fake endpoint /auth/login:
  - resources/ts/pages/login.vue
- Real backend endpoint is /login:
  - routes/api.php
  - docs/api-map.md
- Fake API plugin is auto-loaded in app bootstrap:
  - resources/ts/@core/utils/plugins.ts
  - resources/ts/plugins/fake-api/index.ts
- Result: auth and profile flows are not production-wired by default.

### 1.4 Profile and user menu mismatch

- Backend profile endpoint exists and is permission-aware:
  - app/Http/Controllers/Api/Users/ProfileController.php
  - docs/api-map.md
- Frontend profile pages still consume demo profile API (/pages/profile):
  - resources/ts/pages/pages/user-profile/[tab].vue
  - resources/ts/views/pages/user-profile/profile/index.vue
  - resources/ts/views/pages/user-profile/UserProfileHeader.vue
- Header user menu points to demo user page routes:
  - resources/ts/layouts/components/UserProfile.vue

### 1.5 Operational backend readiness

Backend workflow APIs already exist and include permission checks:

- Orders: routes/api.php, app/Http/Controllers/Api/Orders/OrderController.php
- Shipper collection: app/Http/Controllers/Api/Orders/ShipperCollectionController.php
- Client settlement: app/Http/Controllers/Api/Orders/ClientSettlementController.php
- Shipper return: app/Http/Controllers/Api/Orders/ShipperReturnController.php
- Client return: app/Http/Controllers/Api/Orders/ClientReturnController.php
- Pickup: app/Http/Controllers/Api/Operations/PickupRequestController.php
- Material request: app/Http/Controllers/Api/Operations/MaterialRequestController.php
- Activity logs: app/Http/Controllers/Api/Orders/ActivityLogController.php
- Settings and ACL matrix: routes/api.php

Note: unlock semantics and approval gating are already handled server-side in collections/returns/settlements controllers.

### 1.6 Existing operations dashboard issues

In resources/ts/views/dashboards/operations/OperationsWorkbench.vue:

- Good: pulls real endpoints, shows pending and eligible queues.
- Gaps:
  - Uses many count calls in parallel (overfetch risk under scale).
  - Mixes summary + health checks in one view without modular data boundaries.
  - No role-focused queue sections yet.
  - No explicit empty/error/skeleton components per section.
  - No deep-link action routing to workflow pages.

## 2) Keep / Remove / Merge / Add

### Keep

- resources/ts/views/dashboards/operations/OperationsWorkbench.vue as initial base.
- Existing API client utility:
  - resources/ts/utils/api.ts
  - resources/ts/composables/useApi.ts
- Existing backend workflow controllers and permission enforcement.
- CASL plugin infrastructure as transport layer for UI gating.

### Remove (from dashboard surface)

- Non-operational dashboard pages from primary nav:
  - analytics, ecommerce, academy, logistics dashboards.
- Decorative/vanity widgets not mapped to workflow decisions.
- Any fake dashboard blocks that are not tied to backend data.

### Merge

- Merge CRM dashboard intent into operations dashboard (single operational cockpit).
- Merge dashboard counters + queue alerts into one normalized summary contract.
- Merge approval queues into one reusable queue card system with per-module adapters.

### Add

- New dedicated page entry: resources/ts/pages/dashboards/operations.vue
- New dashboard module components under resources/ts/views/dashboards/operations/:
  - OperationsSummaryCards.vue
  - OperationsPendingApprovals.vue
  - OperationsWorkflowQueues.vue
  - OperationsExceptionsPanel.vue
  - OperationsRecentActivity.vue
  - OperationsSectionState.vue (loading/empty/error wrapper)
- Domain composables/services:
  - resources/ts/composables/operations/useOperationsDashboard.ts
  - resources/ts/composables/operations/useWorkflowQueues.ts
- Pinia stores:
  - resources/ts/stores/auth.ts
  - resources/ts/stores/acl.ts
  - resources/ts/stores/operationsDashboard.ts
- Route updates:
  - vertical/horizontal dashboard nav -> operations-first

## 3) Final Dashboard Structure (Production)

Top-level sections (all real-data only):

1. Summary cards (real counts)

- total orders in motion
- pending approvals total
- delayed/blocked items
- returns waiting next stage

2. Pending approvals (sensitive actions)

- shipper collections pending
- client settlements pending
- shipper returns pending
- client returns pending
- Each row supports approve/reject only if permitted.

3. Operational queues by workflow stage

- Orders ready for shipper collection
- Orders ready for client settlement
- Orders waiting shipper return
- Orders ready for client return
- Pickup requests by status
- Material requests by status / stock risk if available

4. Exception and blocked workflows

- approval rejected but still pending follow-up
- stage mismatch (example: return requested but prerequisite not met)
- unlock-required transitions

5. Recent important activity

- activity-log feed with pagination and filtering

6. Role-based quick actions

- only actions relevant to current permission profile

## 4) Recommended Vue Component Hierarchy

Page:

- dashboards/operations.vue
  - OperationsWorkbenchContainer.vue
    - OperationsSummaryCards.vue
    - OperationsPendingApprovals.vue
    - OperationsWorkflowQueues.vue
    - OperationsExceptionsPanel.vue
    - OperationsRecentActivity.vue

Reusable blocks:

- QueueStatCard.vue
- QueueTable.vue
- ApprovalActionButtons.vue
- PermissionGate.vue
- SectionStateBoundary.vue

## 5) Recommended State/Data Flow

### Auth + ACL bootstrap

1. POST /login
2. save token and user payload
3. GET /acl
4. normalize acl to frontend permission map
5. build CASL ability from acl map
6. render route/nav/actions according to ability

### Dashboard fetch strategy

- One summary request for top cards.
- Separate paginated queue requests for heavy lists.
- Lazy-load below-the-fold sections (recent activity, exceptions details).
- Refresh mechanism:
  - manual refresh button
  - optional background polling for small summary payload only

### Action flow

- UI emits action -> backend enforces rules -> UI refetches minimal impacted sections.
- No frontend business transitions.
- Optimistic updates only for non-sensitive metadata; not for approve/reject/unlock final states.

## 6) API Integration Plan

### Existing endpoints to use directly

- /orders (with search filters)
- /shipper-collections + /eligible-orders + /approve + /reject
- /client-settlements + /eligible-orders + /approve + /reject
- /shipper-returns + /eligible-orders + /approve + /reject
- /client-returns + /eligible-orders + /approve + /reject
- /pickup-requests
- /material-requests
- /activity-logs
- /acl
- /profile

### Suggested missing endpoints (recommended)

1. GET /dashboard/operations/summary

- returns all high-level counts needed for cards and top alerts.
- avoids 10+ frontend count calls.

2. GET /dashboard/operations/queues

- lightweight queue counts grouped by workflow and status.

3. GET /dashboard/operations/exceptions

- blocked/unlock-required/mismatch items.

4. GET /dashboard/operations/recent-activity

- normalized activity stream for dashboard card (separate from full activity logs page).

5. Optional: GET /dashboard/operations/health

- only if operationally useful; otherwise remove endpoint-health table from dashboard UI.

## 7) Permission Visibility Plan

### Route-level gates

- Operations dashboard route requires operations page access permission set.
- Guard denies route early and redirects to not-authorized.

### Section-level gates

- Summary cards visible only if corresponding module page/view permissions exist.
- Queue panels hidden when user lacks module page/view permission.

### Action-level gates (strict)

- Approve button only for \*.approve.
- Reject button only for \*.reject.
- Unlock/revert actions only for \*.unlock.
- Never render disabled admin-only controls for unauthorized users.

### Column-level respect

- Render tables from backend-filtered payload, avoid assuming unavailable columns.

## 8) Loading / Error / Empty-State Plan

Per section:

- Loading: skeleton card/table (not full-page spinner).
- Empty: actionable empty state with reason and suggested next action.
- Error: inline retry + preserved stale snapshot if available.
- Success feedback: small toast/snackbar on actions.

Global:

- Initial page skeleton + section-level deferred hydration.
- Hard errors do not blank out whole dashboard.

## 9) Performance Plan

1. Stop overfetching

- replace multi-endpoint count fan-out with summary endpoints.

2. Keep lists paginated

- do not fetch full settlement/return/pickup/material datasets for card totals.

3. Parallel only lightweight requests

- summary + one or two critical queues first.

4. Cache policy

- short-lived memory cache for dashboard summary.
- invalidate only affected slices after actions.

5. Render optimization

- split dashboard into independent section components.
- avoid reactive coupling of all sections into one large state object.

6. Async UX

- use non-blocking refresh indicators per card/section.

## 10) Implementation Phases

### Phase A: Foundation (must-do first)

- Wire real login (/login) and ACL fetch (/acl).
- Disable fake API plugin outside explicit dev-mock mode.
- Add auth/acl stores and CASL mapping.

### Phase B: Dashboard route and navigation

- Create operations dashboard page and make it default landing for authorized ops roles.
- Replace template dashboard nav entries with operations-first structure.

### Phase C: Data and components

- Refactor OperationsWorkbench into modular section components.
- Integrate real summary + queues + activity data.
- Add proper loading/error/empty boundaries.

### Phase D: Actionability and permissions

- Add approve/reject/unlock action surfaces per queue.
- Gate all actions and sections by permissions.
- Add audit-friendly feedback and refresh scoping.

### Phase E: Performance hardening

- Introduce dedicated summary endpoints.
- Lazy-load secondary sections.
- Add throttled refresh/poll strategy.

## 11) High-Priority Risks and Mitigations

1. Mixed fake and real APIs

- Risk: inconsistent data and auth behavior.
- Mitigation: explicit environment switch; default to real API in production.

2. Missing route-level permission metadata

- Risk: unauthorized users can navigate to irrelevant pages before component checks.
- Mitigation: add route permission metadata and enforce in guard.

3. Overly chatty dashboard requests

- Risk: slow UX under heavy load.
- Mitigation: backend summary endpoints + paginated queue detail endpoints.

4. Frontend business-rule leakage

- Risk: duplicated logic diverges from backend rules.
- Mitigation: keep transitions/eligibility/validation in backend only.

## 12) Immediate Next Execution Items

1. Replace login and profile UI from fake endpoints to real endpoints (/login, /profile, /acl).
2. Introduce operations dashboard page route and make it the primary dashboard entry.
3. Refactor OperationsWorkbench into modular sections with section-state boundaries.
4. Add permission gate utility and wire action-level guards for approve/reject/unlock.
5. Add backend summary endpoint contract and switch counters to it.
