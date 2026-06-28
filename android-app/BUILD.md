# Dey Education Android App

Modern native Android app (API 24–35) matching the PHP student web design.

## Requirements

- Android Studio Ladybug (2024.2+) or newer
- JDK 17 (bundled with Android Studio)

## Build APK

1. Open `android-app/` in Android Studio.
2. Wait for Gradle sync to finish.
3. **Build → Build Bundle(s) / APK(s) → Build APK(s)**

Debug APK output:

`android-app/app/build/outputs/apk/debug/app-debug.apk`

Release APK:

1. **Build → Generate Signed Bundle / APK**
2. Choose APK, create or use a keystore, select `release`.

## Why the old install warning is gone

| Setting | Old app | New app |
|---------|---------|---------|
| targetSdk | Missing / very low | **35** |
| minSdk | Unknown | **24** (Android 7.0+) |
| Build system | Manual `aapt2` script | **Gradle + Android Studio** |
| UI | Programmatic views only | **Material 3 XML layouts** |

## Design (matches `pages/home.php`)

- Primary blue `#2196F3` toolbar and login header
- Greeting + address row with bell and WhatsApp
- 4-column service grid (Academy, About, Gallery, More)
- Fee alert banner
- Horizontal course and promotional carousels
- Bottom navigation: Home, Explore, Notices, Gallery, Profile
- Side drawer menu for fees, homework, enquiry, logout

## API URL

Production default:

`http://187.127.187.70/api/api`

Local XAMPP (on phone use your PC LAN IP):

`http://192.168.x.x/admin/laravel-api/public/api`

### Image URLs (server folder setup)

Images live in the legacy PHP folders (`admin/event/`, `admin/promotional/`, `img/`).
Set this in `laravel-api/.env` on the server so the API returns correct full URLs:

```
PUBLIC_ASSET_BASE=http://187.127.187.70
```

Local XAMPP:

```
PUBLIC_ASSET_BASE=http://localhost/admin
```

The API adds an `image_url` field alongside `image`. The Android app uses `image_url` when present.

### Nginx: images must be served as static files

If `curl -I http://YOUR_IP/admin/event/some.jpg` returns `Content-Type: text/html`, add the locations from `deploy/nginx-static-images.conf.example`, then:

```bash
nginx -t && systemctl reload nginx
```

Without this, the API URLs are correct but the server returns HTML instead of JPEG/PNG.
