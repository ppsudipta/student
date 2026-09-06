# Firebase / Push notifications setup

Push uses **Firebase Cloud Messaging**. Notices stay in the existing `notices` table.
A separate `student_device_tokens` table only stores each phone's FCM token.

## 1. Create a Firebase project

1. Open [Firebase Console](https://console.firebase.google.com/)
2. Create a project (any name)
3. Add an **Android** app with package name: `com.deyeducation.student`
4. Download **`google-services.json`** and replace:
   `android-app/app/google-services.json`
5. In Project Settings → **Service accounts** → Generate new private key
6. Save the JSON as:
   `laravel-api/storage/app/firebase-service-account.json`

## 2. Laravel API env

In `laravel-api/.env`:

```
FCM_PUSH_SECRET=change-me-push-secret
FCM_CREDENTIALS=
```

Leave `FCM_CREDENTIALS` empty to use `storage/app/firebase-service-account.json`.
Keep `FCM_PUSH_SECRET` the same as `admin/push_config.php` → `push_secret`.

Create the tokens table (pick one):

```bash
cd laravel-api
php artisan migrate
```

Or run `sql/student_device_tokens.sql` on the same DB Laravel uses.

## 3. Admin bridge

Edit `admin/push_config.php`:

- Local: `http://127.0.0.1/admin/laravel-api/public/api/push/notices`
- Production: your live API URL ending in `/push/notices`

After Add Notice saves rows, it calls this endpoint so students get a system push.

## 4. Android

Rebuild the app after replacing `google-services.json`.
On first open (Android 13+), allow notifications.
After login, the app registers the FCM token via `POST /device-token`.

## Flow

1. Student logs in → app stores FCM token
2. Admin adds notice → rows in `notices` + push request
3. Laravel looks up tokens → FCM → phone notification
4. Tap opens Notices tab
