#!/usr/bin/env bash
set -euo pipefail

SDK_ROOT="${ANDROID_SDK_ROOT:-/usr/local/share/android-commandlinetools}"
BUILD_TOOLS="$SDK_ROOT/build-tools/34.0.0"
PLATFORM="$SDK_ROOT/platforms/android-34/android.jar"
APP_DIR="$(cd "$(dirname "$0")" && pwd)"
OUT="$APP_DIR/build"

rm -rf "$OUT"
mkdir -p "$OUT/compiled" "$OUT/classes" "$OUT/dex"

"$BUILD_TOOLS/aapt2" compile --dir "$APP_DIR/res" -o "$OUT/compiled/resources.zip"
"$BUILD_TOOLS/aapt2" link \
  -I "$PLATFORM" \
  --manifest "$APP_DIR/AndroidManifest.xml" \
  -o "$OUT/app-unsigned.apk" \
  "$OUT/compiled/resources.zip" \
  --java "$OUT/generated"

javac -source 17 -target 17 \
  -classpath "$PLATFORM" \
  -d "$OUT/classes" \
  $(find "$APP_DIR/src" "$OUT/generated" -name '*.java')

"$BUILD_TOOLS/d8" \
  --lib "$PLATFORM" \
  --output "$OUT/dex" \
  $(find "$OUT/classes" -name '*.class')

cd "$OUT/dex"
zip -q -u "$OUT/app-unsigned.apk" classes.dex
cd "$APP_DIR"

"$BUILD_TOOLS/zipalign" -f 4 "$OUT/app-unsigned.apk" "$OUT/app-aligned.apk"

if [ ! -f "$OUT/debug.keystore" ]; then
  keytool -genkeypair \
    -keystore "$OUT/debug.keystore" \
    -storepass android \
    -keypass android \
    -alias androiddebugkey \
    -keyalg RSA \
    -keysize 2048 \
    -validity 10000 \
    -dname "CN=Android Debug,O=Android,C=US" >/dev/null
fi

"$BUILD_TOOLS/apksigner" sign \
  --ks "$OUT/debug.keystore" \
  --ks-pass pass:android \
  --key-pass pass:android \
  --out "$OUT/dey-education-debug.apk" \
  "$OUT/app-aligned.apk"

"$BUILD_TOOLS/apksigner" verify "$OUT/dey-education-debug.apk"
echo "$OUT/dey-education-debug.apk"
