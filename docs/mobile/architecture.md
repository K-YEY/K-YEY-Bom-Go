# Flutter Mobile Handover: Architecture & Cubit

The mobile application is a high-performance representative tool built with **Flutter** and the **Cubit** state management pattern.

## Architecture Pattern

The app follows a Clean Architecture approach:

- **Presentation**: UI + Cubits + States.
- **Business Logic**: Logic implemented in Cubits (from the `flutter_bloc` package).
- **Data Layer**: Repositories, Providers, and Models (with JSON serialization).

## Why Cubit?

We chose **Cubit** over the full BLoC pattern for its simplicity and reduced boilerplate. It provides:

- **Predictable States**: Immutable states (Loading, Success, Error).
- **Reactive UI**: Automatic UI updates on state changes.
- **Testability**: Clear separation between UI and Logic.

## Folder Structure

```text
lib/
├── core/             # API clients (Dio), constants, routing
├── data/
│   ├── models/       # Order, User, Stats models
│   ├── repositories/ # Abstract data source handling
│   └── providers/    # Direct Dio/HTTP calls to the Laravel API
├── business_logic/
│   ├── cubits/       # Logic for Orders, Auth, Scanning
│   └── states/       # State classes (Initial, Loaded, Failed)
└── presentation/
    ├── screens/      # Feature-specific pages (ScanScreen, OrderList)
    ├── widgets/      # Recurring components (OrderCard, AppButton)
    └── theme/        # Global styling and colors
```

## How State Flows

1. **Event**: User clicks a button in the UI.
2. **Toggle**: The UI calls a method in the `Cubit`.
3. **Action**: The Cubit calls the `Repository`.
4. **Emit**: The Cubit receives data (or an error) and `emits` a new state.
5. **Rebuild**: `BlocBuilder` in the UI hears the state change and rebuilds the relevant widgets.

## How to add a new screen/feature

1. **Model**: Create the data model and run `build_runner` for JSON serialization.
2. **API**: Add the endpoint to the `Provider` and a method to the `Repository`.
3. **Logic**: Create a new `Cubit` and `State` class.
4. **UI**: Create the widget and wrap it with `BlocProvider` and `BlocBuilder`.
5. **Route**: Add the new screen to the App Router.

## API Integration Tips

- We use **Dio** for network requests.
- All requests must include the **Bearer Token** in the header.
- Error handling is centralized in the `base_repository` or via a Dio `Interceptor`.

## Debugging

- Use **Bloc Observer** to trace all state transitions in the terminal.
- Use **Flutter DevTools** to inspect the widget tree and network calls.

### Common Issues

- **Stale State**: Ensure `emit()` is always called with a new instance of the state class.
- **Provider Context**: Always check that you are calling `BlocProvider.of<T>(context)` from a child of the provider.
