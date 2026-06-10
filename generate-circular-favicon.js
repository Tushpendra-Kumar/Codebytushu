/**
 * generate-circular-favicon.js
 *
 * Source : Black Logo.PNG  (black+gold monitor icon on WHITE background)
 * Process: Remove white background pixel-by-pixel, place on BLACK circle,
 *          apply gold border ring + circular alpha mask.
 * Result : Transparent PNGs — black circle with gold monitor, NO white square.
 */

const sharp = require('sharp');
const path  = require('path');
const fs    = require('fs');

const ROOT   = path.resolve(__dirname);
const SOURCE = path.join(ROOT, 'image1', 'Black Logo.PNG');

/* ─── Remove white background from an image buffer ──────────────────── */
async function removeWhiteBg(inputBuffer, threshold = 235) {
    const { data, info } = await sharp(inputBuffer)
        .ensureAlpha()
        .raw()
        .toBuffer({ resolveWithObject: true });

    const { width, height } = info;
    const buf = Buffer.from(data); // mutable copy

    for (let i = 0; i < buf.length; i += 4) {
        const r = buf[i], g = buf[i + 1], b = buf[i + 2];
        // Near-white pixel → fully transparent
        if (r >= threshold && g >= threshold && b >= threshold) {
            buf[i + 3] = 0;
        }
        // Near-grey/off-white → partially transparent (soft edge)
        else if (r >= 200 && g >= 200 && b >= 200) {
            const whiteness = Math.min(r, g, b);
            buf[i + 3] = Math.round(255 * (1 - (whiteness - 200) / 55));
        }
    }

    return sharp(buf, { raw: { width, height, channels: 4 } })
        .png()
        .toBuffer();
}

/* ─── SVG helpers ────────────────────────────────────────────────────── */
function circleBg(size, fill, strokeColor, sw) {
    const r = (size / 2) - (sw / 2);
    return Buffer.from(
        `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}">` +
        `<circle cx="${size/2}" cy="${size/2}" r="${r}" fill="${fill}" ` +
        `stroke="${strokeColor}" stroke-width="${sw}"/>` +
        `</svg>`
    );
}

function circleMask(size) {
    return Buffer.from(
        `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}">` +
        `<circle cx="${size/2}" cy="${size/2}" r="${size/2}" fill="white"/>` +
        `</svg>`
    );
}

/* ─── Build one circular favicon ─────────────────────────────────────── */
async function buildCircle(size) {
    const meta = await sharp(SOURCE).metadata();
    const srcW = meta.width, srcH = meta.height;

    /* Crop: keep only the monitor icon (top ~58%, trim 10% left+right) */
    const cropH = Math.floor(srcH * 0.58);
    const cropL = Math.floor(srcW * 0.10);
    const cropW = srcW - cropL * 2;

    const bw      = Math.max(1, Math.round(size * 0.045)); // gold border
    const padding = Math.max(2, Math.round(size * 0.12));  // inner gap
    const inner   = size - padding * 2;

    /* 1. Crop icon region */
    const cropped = await sharp(SOURCE)
        .extract({ left: cropL, top: 0, width: cropW, height: cropH })
        .resize(inner, inner, {
            fit:        'contain',
            background: { r: 255, g: 255, b: 255, alpha: 1 }
        })
        .png()
        .toBuffer();

    /* 2. Remove white background → transparent pixels */
    const transparent = await removeWhiteBg(cropped, 230);

    /* 3. Composite: BLACK circle + gold border → icon (no white) → circular mask */
    const bg   = circleBg(size, '#0a0a0a', '#ffc400', bw);
    const mask = circleMask(size);

    return sharp({
        create: { width: size, height: size, channels: 4,
                  background: { r: 0, g: 0, b: 0, alpha: 0 } }
    })
    .composite([
        { input: bg },
        { input: transparent, top: padding, left: padding },
        { input: mask, blend: 'dest-in' }
    ])
    .png({ compressionLevel: 9 })
    .toBuffer();
}

/* ─── Build multi-size .ico ──────────────────────────────────────────── */
function buildIco(images) {
    let offset = 6 + 16 * images.length;
    const parts = [];
    const hdr = Buffer.alloc(6);
    hdr.writeUInt16LE(0, 0); hdr.writeUInt16LE(1, 2);
    hdr.writeUInt16LE(images.length, 4);
    parts.push(hdr);
    for (const img of images) {
        const e = Buffer.alloc(16);
        e.writeUInt8(img.w > 255 ? 0 : img.w, 0);
        e.writeUInt8(img.h > 255 ? 0 : img.h, 1);
        e.writeUInt8(0, 2); e.writeUInt8(0, 3);
        e.writeUInt16LE(1, 4); e.writeUInt16LE(32, 6);
        e.writeUInt32LE(img.buf.length, 8);
        e.writeUInt32LE(offset, 12);
        offset += img.buf.length; parts.push(e);
    }
    for (const img of images) parts.push(img.buf);
    return Buffer.concat(parts);
}

/* ─── Main ───────────────────────────────────────────────────────────── */
async function main() {
    if (!fs.existsSync(SOURCE)) {
        console.error('Not found:', SOURCE); process.exit(1);
    }
    console.log('Source : Black Logo.PNG  →  black circle + gold border');

    const jobs = [
        { size: 512, file: 'android-chrome-512x512.png' },
        { size: 192, file: 'android-chrome-192x192.png' },
        { size: 180, file: 'apple-touch-icon.png'       },
        { size: 48,  file: 'favicon-48x48.png'          },
        { size: 32,  file: 'favicon-32x32.png'          },
        { size: 16,  file: 'favicon-16x16.png'          },
    ];

    const icoBufs = [];
    for (const { size, file } of jobs) {
        const buf  = await buildCircle(size);
        const dest = path.join(ROOT, file);
        fs.writeFileSync(dest, buf);
        fs.copyFileSync(dest, path.join(ROOT, 'favicon logo', file));
        const kb = (buf.length / 1024).toFixed(1);
        console.log(`  ✅  ${file.padEnd(32)} ${size}px  ${kb} KB`);
        if ([16, 32, 48].includes(size)) icoBufs.push({ w: size, h: size, buf });
    }

    const ico = buildIco(icoBufs.reverse());
    fs.writeFileSync(path.join(ROOT, 'favicon.ico'), ico);
    fs.copyFileSync(path.join(ROOT, 'favicon.ico'), path.join(ROOT, 'favicon logo', 'favicon.ico'));
    console.log(`  ✅  favicon.ico (16+32+48)  ${(ico.length/1024).toFixed(1)} KB`);
    console.log('\n  🎉  Done — black circle favicon ready!\n');
}

main().catch(e => { console.error('FAILED:', e); process.exit(1); });
