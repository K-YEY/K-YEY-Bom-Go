# Mobile App: Cubit & State Management

K-YEY Mobile uses the **Cubit** pattern from the `flutter_bloc` package for deterministic and reactive state management.

## State Management Principles

- **Cubit**: The logic class that emits states.
- **State**: An immutable class representing a snapshot of the UI's data and status.
- **BlocListener**: To trigger side effects (e.g. snackbars, navigation) on state change.
- **BlocBuilder**: To rebuild the widget tree based on the current state.

## Core States

Most features share a common state pattern:

1. **Initial**: Before any action is taken.
2. **Loading**: While waiting for the API.
3. **Success**: When data is successfully received or action is completed.
4. **Error**: When something goes wrong (e.g. 403, 401, 500).

## Why Cubit?

We prefer **Cubit** over **BLoC** (which uses Events) because:

- **Less Boilerplate**: No need to define event classes.
- **Simpler Flow**: UI calls methods directly.
- **Predictable**: Emissions are easy to follow in a linear flow.

## Example: AuthCubit Logic

1. **UI**: Calls `login(username, password)`.
2. **Cubit**: Emits `AuthLoading()`.
3. **Repository**: `authRepository.login()` is called.
4. **Emit**: If success, `emit(AuthAuthenticated(user))`. If failed, `emit(AuthError(message))`.

## State Management Guidelines

- Always use **Equatable** (from the `equatable` package) for state classes to ensure proper `emit()` comparisons.
- **Keep Cubits focused**: One Cubit per feature (e.g., `OrderCollectionCubit`, `ScannerCubit`).
- **Avoid UI logic in Cubits**: Cubits should only handle data flow and business logic.
- **Clean up**: Always check if a Cubit needs to be closed or if a subscription in a Cubit needs to be cancelled (using `close()` or `onClose()`).

## Debugging State

- **BlocObserver**: Logs all state changes to the console.
- **Flutter DevTools**: Use the Bloc tab to inspect the current state values and history.
