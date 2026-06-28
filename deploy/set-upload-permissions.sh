#!/bin/bash
# Run on VPS as root:
#   cd /var/www/student && bash deploy/set-upload-permissions.sh
#
# Gives PHP/nginx (www-data) read/write access to all admin upload folders.

set -e

ROOT="${1:-/var/www/student}"
WEB_USER="${2:-www-data}"
WEB_GROUP="${3:-www-data}"

if [ ! -d "$ROOT" ]; then
  echo "Error: project root not found: $ROOT"
  exit 1
fi

# Folders used by admin/add*.php and Laravel API uploads
UPLOAD_DIRS=(
  "$ROOT/admin/event"
  "$ROOT/admin/promotional"
  "$ROOT/admin/gallery"
  "$ROOT/admin/slider"
  "$ROOT/admin/category"
  "$ROOT/admin/service"
  "$ROOT/admin/testimonial"
  "$ROOT/admin/news"
  "$ROOT/admin/pdf"
  "$ROOT/admin/images"
  "$ROOT/admin/uploads"
  "$ROOT/admin/uploads/materials"
  "$ROOT/img"
  "$ROOT/pages/uploads"
  "$ROOT/laravel-api/storage"
  "$ROOT/laravel-api/bootstrap/cache"
)

echo "Project: $ROOT"
echo "Web user: $WEB_USER:$WEB_GROUP"
echo ""

for dir in "${UPLOAD_DIRS[@]}"; do
  mkdir -p "$dir"
  chown -R "$WEB_USER:$WEB_GROUP" "$dir"
  find "$dir" -type d -exec chmod 775 {} \;
  find "$dir" -type f -exec chmod 664 {} \;
  echo "OK  $dir"
done

# Allow www-data to create new subfolders under admin (promotional/, gallery/, etc.)
chown "$WEB_USER:$WEB_GROUP" "$ROOT/admin"
chmod 775 "$ROOT/admin"

echo ""
echo "Done. Upload folders are writable by the web app."
echo "Test: upload a course image from admin panel, then run:"
echo "  cd $ROOT/laravel-api && php artisan images:verify"
