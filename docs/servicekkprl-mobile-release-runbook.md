# ServiceKKPRL Mobile Release Runbook

## Required secrets and tools

- PHP 8.2+ and Composer.
- Node.js and npm compatible with `mobile/package-lock.json`.
- JDK 21 and Android SDK with API 36/build tools.
- Production Laravel `APP_KEY`.
- Summary identity endpoint access.
- Firebase project ID and service-account JSON outside the repository.
- `mobile/android/app/google-services.json`.
- Android release keystore and protected signing values.
- Firebase CLI access or an approved App Distribution CI action.

## Backend deployment

```powershell
php artisan down --retry=60
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan up
php artisan queue:restart
```

Run a dedicated worker:

```powershell
php artisan queue:work database --queue=push,default --tries=5 --backoff=10,30,120,300
```

Required environment values are documented in `.env.example`. Keep
`FIREBASE_CREDENTIALS` outside the web root and readable only by the worker
account.

## Mobile verification

```powershell
cd mobile
npm ci
npm test
npm run build
npm run android:sync
```

Confirm `mobile/android/variables.gradle` still declares `minSdkVersion = 29`.
Capacitor upgrades can regenerate this file, so this is a release gate.

## Signed APK

Configure signing with CI-injected Gradle properties or a protected local
`keystore.properties` that is excluded from source control. Never add the
keystore or passwords to Gradle files.

```powershell
cd mobile/android
gradlew.bat clean assembleRelease
Get-FileHash app/build/outputs/apk/release/app-release.apk -Algorithm SHA256
```

Archive the APK, checksum, commit SHA, version, build number, and release notes
together.

## Firebase App Distribution

1. Upload the signed APK.
2. Assign only the pilot groups first.
3. Verify installation and push on at least one Android 10 and one current
   Android device.
4. Set `MOBILE_ANDROID_LATEST_*` and the distribution URL.
5. Do not raise `MOBILE_ANDROID_MIN_BUILD` during the initial pilot.
6. Raise the minimum only when the replacement APK is accessible and verified.

## Smoke test

- App configuration endpoint responds.
- Authorized and unauthorized Summary users behave correctly.
- Dashboard and All/My Requests are permission filtered.
- Create/change a schedule and verify only intended devices receive a safe push.
- Create/reassign an assignment and test deep linking.
- Submit feedback and verify recipient matrix.
- Revoke the role during a session and verify the next protected request is
  forbidden.
- Logout and verify the device receives no later push.
- Open a private document and verify an unrelated ticket cannot access it.

## Rollback

If the APK is faulty, keep API v1 backward compatible, lower the latest build
display if needed, and redistribute the last known-good signed APK. Do not lower
the minimum below a build with known security flaws.

If backend notification delivery is faulty, stop only the `push` worker. The
durable inbox, established transactions, Filament UI, and e-mail flow must
continue. Correct the worker/configuration, then restart queued delivery.

Database rollback is a last resort. The mobile migration adds isolated token,
device, inbox, and delivery tables; verify no active tokens or needed inbox
history before reversing it.

