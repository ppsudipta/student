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

## 5. Upload folder permissions (admin image uploads)

PHP runs as `www-data`. Upload folders must be owned by `www-data` and writable.

```bash
cd /var/www/student
bash deploy/set-upload-permissions.sh
```

Or manually:

```bash
ROOT=/var/www/student
WEB=www-data

mkdir -p $ROOT/admin/{event,promotional,gallery,slider,category,service,testimonial,news,pdf,images,uploads,uploads/materials}
mkdir -p $ROOT/img $ROOT/pages/uploads

chown -R $WEB:$WEB $ROOT/admin/event $ROOT/admin/promotional $ROOT/admin/gallery \
  $ROOT/admin/slider $ROOT/admin/category $ROOT/admin/service $ROOT/admin/testimonial \
  $ROOT/admin/news $ROOT/admin/pdf $ROOT/admin/images $ROOT/admin/uploads $ROOT/img $ROOT/pages/uploads

chmod -R 775 $ROOT/admin/event $ROOT/admin/promotional $ROOT/admin/gallery $ROOT/admin/slider \
  $ROOT/admin/category $ROOT/admin/service $ROOT/admin/testimonial $ROOT/admin/news \
  $ROOT/admin/pdf $ROOT/admin/images $ROOT/admin/uploads $ROOT/img $ROOT/pages/uploads

chmod 775 $ROOT/admin
```

Folders written by the web app:

| Path | Used for |
|------|----------|
| `admin/event/` | Courses (Our Courses) |
| `admin/promotional/` | Promotional images |
| `admin/slider/` | Home sliders |
| `admin/gallery/` | Gallery uploads |
| `admin/images/` | Why/about images |
| `admin/category/` | Categories |
| `admin/uploads/` | Notices, materials |
| `img/` | Student profile photos |
| `pages/uploads/` | Homework, enquiries (API) |

## 6. Test

```bash
curl -s http://127.0.0.1/api/api/home | grep -o '"image_url":"[^"]*"' | head -3
curl -I http://127.0.0.1/admin/event/012.png
```

Expect full `http://187.127.187.70/admin/...` URLs and `Content-Type: image/png` or `image/jpeg`.

## 7. Android

Rebuild and reinstall the app from `android-app/` in Android Studio.
