# VPS deploy checklist (images + API)

## 1. Pull latest code

```bash
cd /var/www/student
git pull origin main
```

## 2. Laravel `.env`

```env
PUBLIC_ASSET_BASE=http://187.127.187.70
```

```bash
cd /var/www/student/laravel-api
php artisan config:clear
php artisan config:cache
systemctl restart php8.3-fpm
```

## 3. Nginx (if not already done)

Copy `deploy/student-api.nginx` to `/etc/nginx/sites-enabled/student-api`, then:

```bash
nginx -t && systemctl reload nginx
```

## 4. Verify image files vs database

```bash
cd /var/www/student/laravel-api
php artisan images:verify
```

Fix any `[MISSING]` rows by re-uploading in admin or copying files to the path shown.

## 5. Test

```bash
curl -s http://127.0.0.1/api/api/home | grep -o '"image_url":"[^"]*"' | head -3
curl -I http://127.0.0.1/admin/event/012.png
```

Expect full `http://187.127.187.70/admin/...` URLs and `Content-Type: image/png` or `image/jpeg`.

## 6. Android

Rebuild and reinstall the app from `android-app/` in Android Studio.
