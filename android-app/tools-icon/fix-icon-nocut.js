const sharp = require('sharp');
const path = require('path');
const fs = require('fs');

const SRC = 'c:/xampp/htdocs/admin/android-app/app/src/main/res/drawable/ic_dey_logo.jpg';
const DRAWABLE = 'c:/xampp/htdocs/admin/android-app/app/src/main/res/drawable';
const RES = 'c:/xampp/htdocs/admin/android-app/app/src/main/res';
const PREVIEW = 'c:/xampp/htdocs/admin/android-app/icon-preview';
const COLORS = 'c:/xampp/htdocs/admin/android-app/app/src/main/res/values/colors.xml';
const ANYDPI = 'c:/xampp/htdocs/admin/android-app/app/src/main/res/mipmap-anydpi-v26';

// Full gold-border logo, sized to fit inside circular mask (no clipped corners).
const LOGO_SCALE = 0.55;

function isOutsideBg(r, g, b) {
  const min = Math.min(r, g, b);
  const max = Math.max(r, g, b);
  return min >= 190 && (max - min) <= 40;
}

function isMostlyWhite(r, g, b) {
  return r >= 200 && g >= 200 && b >= 200;
}

async function buildCutout() {
  const { data, info } = await sharp(SRC).ensureAlpha().raw().toBuffer({ resolveWithObject: true });
  const w = info.width;
  const h = info.height;

  let sumR = 0, sumG = 0, sumB = 0, n = 0;
  for (let y = 0; y < h; y += 4) {
    for (let x = 0; x < w; x += 4) {
      const i = (y * w + x) * 4;
      const r = data[i], g = data[i + 1], b = data[i + 2];
      if (isMostlyWhite(r, g, b)) continue;
      if (b > 60 && b > r + 20 && b > g + 10 && r < 80) {
        sumR += r; sumG += g; sumB += b; n++;
      }
    }
  }
  const bg = {
    r: Math.round(sumR / Math.max(n, 1)),
    g: Math.round(sumG / Math.max(n, 1)),
    b: Math.round(sumB / Math.max(n, 1)),
    alpha: 255
  };
  console.log('logo blue', bg);

  const out = Buffer.from(data);
  const visited = new Uint8Array(w * h);
  const stack = [
    [0, 0], [w - 1, 0], [0, h - 1], [w - 1, h - 1],
    [w >> 1, 0], [w >> 1, h - 1], [0, h >> 1], [w - 1, h >> 1]
  ];
  while (stack.length) {
    const [x, y] = stack.pop();
    if (x < 0 || y < 0 || x >= w || y >= h) continue;
    const idx = y * w + x;
    if (visited[idx]) continue;
    visited[idx] = 1;
    const i = idx * 4;
    if (!isOutsideBg(data[i], data[i + 1], data[i + 2])) continue;
    out[i] = bg.r;
    out[i + 1] = bg.g;
    out[i + 2] = bg.b;
    out[i + 3] = 0;
    stack.push([x + 1, y], [x - 1, y], [x, y + 1], [x, y - 1]);
  }

  // Clear white fringe next to transparent edge
  for (let y = 1; y < h - 1; y++) {
    for (let x = 1; x < w - 1; x++) {
      const i = (y * w + x) * 4;
      if (out[i + 3] === 0) continue;
      if (!isMostlyWhite(out[i], out[i + 1], out[i + 2]) && !isOutsideBg(out[i], out[i + 1], out[i + 2])) continue;
      let near = false;
      for (const [dx, dy] of [[1, 0], [-1, 0], [0, 1], [0, -1]]) {
        if (out[((y + dy) * w + (x + dx)) * 4 + 3] === 0) { near = true; break; }
      }
      if (near) {
        out[i] = bg.r; out[i + 1] = bg.g; out[i + 2] = bg.b; out[i + 3] = 0;
      }
    }
  }

  const cutoutPath = path.join(DRAWABLE, 'ic_dey_logo_cutout.png');
  await sharp(out, { raw: { width: w, height: h, channels: 4 } }).png().toFile(cutoutPath);
  return { cutoutPath, bg };
}

/** Fully opaque RGB icon: solid logo-blue plate + gold-border logo. Never white. */
async function makeOpaqueIcon(cutoutPath, size, bg) {
  const logoSize = Math.round(size * LOGO_SCALE);
  const left = Math.round((size - logoSize) / 2);
  const logoBuf = await sharp(cutoutPath)
    .resize(logoSize, logoSize, { fit: 'fill' })
    .png()
    .toBuffer();

  return sharp({
    create: {
      width: size,
      height: size,
      channels: 3,
      background: { r: bg.r, g: bg.g, b: bg.b }
    }
  })
    .composite([{ input: logoBuf, left, top: left }])
    .png()
    .toBuffer();
}

function updateLauncherColor(bg) {
  const hex = '#' + [bg.r, bg.g, bg.b].map((v) => v.toString(16).padStart(2, '0')).join('').toUpperCase();
  let xml = fs.readFileSync(COLORS, 'utf8');
  xml = xml.replace(
    /<color name="launcher_background">#[0-9A-Fa-f]{6}<\/color>/,
    `<color name="launcher_background">${hex}</color>`
  );
  fs.writeFileSync(COLORS, xml);
  console.log('launcher_background', hex);
  return hex;
}

async function main() {
  const { cutoutPath, bg } = await buildCutout();
  updateLauncherColor(bg);
  fs.mkdirSync(ANYDPI, { recursive: true });

  // Adaptive icons (needed so launcher does NOT put logo on a white circle plate)
  const adaptive = `<?xml version="1.0" encoding="utf-8"?>
<adaptive-icon xmlns:android="http://schemas.android.com/apk/res/android">
    <background android:drawable="@color/launcher_background" />
    <foreground android:drawable="@mipmap/ic_launcher_foreground" />
</adaptive-icon>
`;
  fs.writeFileSync(path.join(ANYDPI, 'ic_launcher.xml'), adaptive);
  fs.writeFileSync(path.join(ANYDPI, 'ic_launcher_round.xml'), adaptive);

  const densities = [
    ['mdpi', 48, 108],
    ['hdpi', 72, 162],
    ['xhdpi', 96, 216],
    ['xxhdpi', 144, 324],
    ['xxxhdpi', 192, 432],
  ];

  for (const [dpi, mipSize, fgSize] of densities) {
    const mipDir = path.join(RES, `mipmap-${dpi}`);
    const fgDir = path.join(RES, `drawable-${dpi}`);
    fs.mkdirSync(mipDir, { recursive: true });
    fs.mkdirSync(fgDir, { recursive: true });

    const legacy = await makeOpaqueIcon(cutoutPath, mipSize, bg);
    const fg = await makeOpaqueIcon(cutoutPath, fgSize, bg);

    await sharp(legacy).toFile(path.join(mipDir, 'ic_launcher.png'));
    await sharp(legacy).toFile(path.join(mipDir, 'ic_launcher_round.png'));
    await sharp(fg).toFile(path.join(mipDir, 'ic_launcher_foreground.png'));
    await sharp(fg).toFile(path.join(fgDir, 'ic_launcher_foreground.png'));
    console.log(dpi, 'ok');
  }

  fs.mkdirSync(PREVIEW, { recursive: true });
  const preview = await makeOpaqueIcon(cutoutPath, 512, bg);
  await sharp(preview).toFile(path.join(PREVIEW, 'square.png'));
  const circleSvg = Buffer.from(
    `<svg width="512" height="512"><circle cx="256" cy="256" r="256" fill="white"/></svg>`
  );
  await sharp(preview)
    .composite([{ input: circleSvg, blend: 'dest-in' }])
    .png()
    .toFile(path.join(PREVIEW, 'round.png'));

  // Verify no white corners
  const check = await sharp(preview).raw().ensureAlpha().toBuffer({ resolveWithObject: true });
  const c = check.data;
  console.log('corner rgba', c[0], c[1], c[2], c[3], 'expected blue', bg);
  console.log('done scale', LOGO_SCALE);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
