# Admin Operations: Audit & Activity Logs

K-YEY Logistics features a high-fidelity audit log to track all data changes.

## Logged Actions

- **Create**: When a model is added.
- **Update**: When a model matches but a field changes.
- **Delete**: When a model is soft-deleted.

## What is Logged?

- **User**: The ID and name of the person who made the change.
- **Model**: The type of data changed (e.g. `Order`, `User`).
- **Data**: A JSON representation of the changes (previous vs current).
- **IP Address**: The origin IP of the request.
- **Timestamp**: The exact time the action was performed.

## Management Section

- **Location**: `dashboards/activity-logs`.
- **Search**: Filter by User, Model, or ID specifically to find a change.

## Security Traceability

- **EntityObserver.php**: This observer is registered in `AppServiceProvider.php` and is responsible for capturing all Eloquent events.
- **ActivityLog.php**: The model stores these events in a dedicated `activity_logs` table.
- **Auditing**: This log is the source of truth for all system investigations or debugging of data inconsistencies.
