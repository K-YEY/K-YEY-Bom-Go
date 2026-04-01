# Flutter Mobile Onboarding Guide

Follow these steps to set up and maintain the Flutter application for representatives.

## 1. Prerequisites

- **Flutter SDK 3.19+** (Check with `flutter --version`).
- **Dart 3.x**.
- **Android Studio** or **VS Code** with the Flutter/Dart extension.
- **Xcode** (if building for iOS).

## 2. Dev Setup

1. Navigate to the mobile app directory.
2. Run `flutter pub get` to download dependencies.
3. Copy `.env.example` to `.env` (if used) or configure `lib/core/constants/api_config.dart`.
4. Configure the `BASE_URL` to point to your Laravel API.

## 3. Key Commands

### Generate Models (json_serializable)

If you modify any model file:

```bash
flutter pub run build_runner build --delete-conflicting-outputs
```

### Run Project

- **Debug**: `flutter run` (or press F5 in VS Code).
- **Release**: `flutter build apk --release` (or `flutter build ios --release`).

## 4. Key Logic (Cubit & State)

- **Authentication**: `AuthCubit` handles login and token persistence via `Shared_preferences`.
- **Order Tracking**: `OrderCubit` provides state for `OrderListScreen` and `OrderDetailScreen`.
- **Scanning**: The app uses `mobile_scanner` to fetch order details by code and trigger `updateStatus` via the `ScanCubit`.

## 5. UI & Styling

- The app uses a custom theme in `lib/presentation/theme/`.
- All reusable widgets are in `lib/presentation/widgets/`.
- Localization is handled via `intl` or a custom JSON provider.

## 6. How to trace a bug

1. Check the `Dio` logs for the API response.
2. Use the `BlocObserver` (if configured in `main.dart`) to see the state transitions.
3. Verify the `Repository` logic for any data-mapping errors.

## 7. Recommended Future Improvements

- **Offline Support**: Cache orders in a local SQL database (Hive or Sembast) for offline status updates.
- **Push Notifications**: Integrate Firebase Messaging for new order assignments.
- **Biometric Login**: Add FaceID/Fingerprint authentication for representatives.
