const sharp = require('sharp');
const path = require('path');

const SRC = 'c:/xampp/htdocs/admin/android-app/app/src/main/res/drawable/ic_dey_logo.jpg';
const DRAWABLE = 'c:/xampp/htdocs/admin/android-app/app/src/main/res/drawable';
const RES = 'c:/xampp/htdocs/admin/android-app/app/src/main/res';
const PREVIEW = 'c:/xampp/htdocs/admin/android-app/icon-preview';

function isOutsideBg(r, g, b) {
  const min = Math.min(r, g, b);
  const max = Math.max(r, g, b);
  return min >= 210 && (max - min) <= 35;
}

async function makeCutout() {
  const { data, info } = await sharp(SRC).ensureAlpha().raw().toBuffer({ resolveWithObject: true });
  const w = info.width;
  const h = info.height;
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
    const r = data[i];
    const g = data[i + 1];
    const b = data[i + 2];
    if (!isOutsideBg(r, g, b)) continue;
    out[i + 3] = 0;
    stack.push([x + 1, y], [x - 1, y], [x, y + 1], [x, y - 1]);
  }

  for (let y = 1; y < h - 1; y++) {
    for (let x = 1; x < w - 1; x++) {
      const i = (y * w + x) * 4;
      if (out[i + 3] === 0) continue;
      const r = out[i];
      const g = out[i + 1];
      const b = out[i + 2];
      if (!isOutsideBg(r, g, b)) continue;
      let transparentNeighbors = 0;
      const neighbors = [[1, 0], [-1, 0], [0, 1], [0, -1]];
      for (const [dx, dy] of neighbors) {
        if (out[((y + dy) * w + (x + dx)) * 4 + 3] === 0) transparentNeighbors++;
      }
      if (transparentNeighbors >= 1) {
        const whiteness = (r + g + b) / (3 * 255);
        out[i + 3] = Math.max(0, Math.round(255 * (1 - whiteness) * 2.2));
      }
    }
  }

  const cutoutPath = path.join(DRAWABLE, 'ic_dey_logo_cutout.png');
  await sharp(out, { raw: { width: w, height: h, channels: 4 } }).png().toFile(cutoutPath);
  await sharp(out, { raw: { width: w, height: h, channels: 4 } }).png().toFile(path.join(PREVIEW, 'cutout.png'));
  console.log('cutout saved', cutoutPath);
  return cutoutPath;
}

async function makeIcons(cutoutPath) {
  const bg = { r: 2, g: 41, b: 108, alpha: 1 };
  const sizes = {
    'mipmap-mdpi': 48,
    'mipmap-hdpi': 72,
    'mipmap-xhdpi': 96,
    'mipmap-xxhdpi': 144,
    'mipmap-xxxhdpi': 192,
  };
  // Fill circle tightly while keeping full gold border visible
  const scale = 0.78;

  for (const [folder, size] of Object.entries(sizes)) {
    const logoSize = Math.round(size * scale);
    const left = Math.round((size - logoSize) / 2);
    const top = left;
    const logoBuf = await sharp(cutoutPath)
      .resize(logoSize, logoSize, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } })
      .png()
      .toBuffer();

    const composed = await sharp({
      create: { width: size, height: size, channels: 4, background: bg }
    })
      .composite([{ input: logoBuf, left, top }])
      .png()
      .toBuffer();

    await sharp(composed).toFile(path.join(RES, folder, 'ic_launcher.png'));

    const circleSvg = Buffer.from(
      `<svg width="${size}" height="${size}"><circle cx="${size / 2}" cy="${size / 2}" r="${size / 2}" fill="white"/></svg>`
    );
    await sharp(composed)
      .composite([{ input: circleSvg, blend: 'dest-in' }])
      .png()
      .toFile(path.join(RES, folder, 'ic_launcher_round.png'));

    console.log('wrote', folder, size);
  }

  const size = 512;
  const logoSize = Math.round(size * scale);
  const left = Math.round((size - logoSize) / 2);
  const logoBuf = await sharp(cutoutPath).resize(logoSize, logoSize).png().toBuffer();
  const composed = await sharp({
    create: { width: size, height: size, channels: 4, background: bg }
  })
    .composite([{ input: logoBuf, left, top: left }])
    .png()
    .toBuffer();

  await sharp(composed).toFile(path.join(PREVIEW, 'square.png'));
  const circleSvg = Buffer.from(
    `<svg width="${size}" height="${size}"><circle cx="${size / 2}" cy="${size / 2}" r="${size / 2}" fill="white"/></svg>`
  );
  await sharp(composed)
    .composite([{ input: circleSvg, blend: 'dest-in' }])
    .png()
    .toFile(path.join(PREVIEW, 'round.png'));

  await sharp(cutoutPath).resize(512, 512).png().toFile(path.join(PREVIEW, 'logo-only.png'));
  console.log('previews ok');
}

(async () => {
  const cutout = await makeCutout();
  await makeIcons(cutout);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
