/**
 * generate-favicons.js
 * Generates all required favicon sizes from the CodeByTushu Black Logo.
 * Source: /image1/Black Logo.PNG  (gold+black logo on WHITE background)
 * Output: all files placed at project root — clean paths, no subfolder spaces.
 */
const sharp = require('sharp');
const path  = require('path');
const fs    = require('fs');

const ROOT   = path.resolve(__dirname);
const SOURCE = path.join(ROOT, 'image1', 'Black Logo.PNG');

async function generate() {
    console.log('Source image:', SOURCE);
    if (!fs.existsSync(SOURCE)) {
        console.error('ERROR: Source image not found!');
        process.exit(1);
    }

    const jobs = [
        { size: 512, file: 'android-chrome-512x512.png' },
        { size: 192, file: 'android-chrome-192x192.png' },
        { size: 180, file: 'apple-touch-icon.png'       },
        { size: 32,  file: 'favicon-32x32.png'          },
        { size: 16,  file: 'favicon-16x16.png'          },
    ];

    for (const { size, file } of jobs) {
        const dest = path.join(ROOT, file);
        await sharp(SOURCE)
            .resize(size, size, {
                fit: 'contain',
                background: { r:255, g:255, b:255, alpha:1 }  // white bg matches Black Logo
            })
            .png({ quality: 95, compressionLevel: 9 })
            .toFile(dest);
        const { size: bytes } = fs.statSync(dest);
        console.log(`✅  ${file}  (${size}×${size})  →  ${bytes} bytes`);
    }

    // Create favicon.ico (multi-size: 16+32)
    const ico32 = await sharp(SOURCE)
        .resize(32, 32, { fit:'contain', background:{r:255,g:255,b:255,alpha:1} })
        .png()
        .toBuffer();

    const ico16 = await sharp(SOURCE)
        .resize(16, 16, { fit:'contain', background:{r:255,g:255,b:255,alpha:1} })
        .png()
        .toBuffer();

    // Build proper multi-size ICO binary
    function icoBuffer(images) {
        const headerSize = 6;
        const entrySize  = 16;
        const entries    = images.length;
        let offset = headerSize + entrySize * entries;
        const parts = [];

        const header = Buffer.alloc(6);
        header.writeUInt16LE(0, 0);
        header.writeUInt16LE(1, 2);
        header.writeUInt16LE(entries, 4);
        parts.push(header);

        for (const img of images) {
            const entry = Buffer.alloc(16);
            entry.writeUInt8(img.width  > 255 ? 0 : img.width,  0);
            entry.writeUInt8(img.height > 255 ? 0 : img.height, 1);
            entry.writeUInt8(0, 2);
            entry.writeUInt8(0, 3);
            entry.writeUInt16LE(1, 4);
            entry.writeUInt16LE(32, 6);
            entry.writeUInt32LE(img.buffer.length, 8);
            entry.writeUInt32LE(offset, 12);
            offset += img.buffer.length;
            parts.push(entry);
        }

        for (const img of images) parts.push(img.buffer);
        return Buffer.concat(parts);
    }

    const icoData = icoBuffer([
        { width: 16, height: 16, buffer: ico16 },
        { width: 32, height: 32, buffer: ico32 },
    ]);

    const icoDest = path.join(ROOT, 'favicon.ico');
    fs.writeFileSync(icoDest, icoData);
    console.log(`✅  favicon.ico  (16+32 multi-size ICO)  →  ${icoData.length} bytes`);

    // Sync copies to 'favicon logo' folder
    const favDir = path.join(ROOT, 'favicon logo');
    for (const { file } of [...jobs, {file:'favicon.ico'}]) {
        fs.copyFileSync(path.join(ROOT, file), path.join(favDir, file));
    }
    console.log('✅  Synced copies to "favicon logo/" folder');

    console.log('\n✅  All favicon files generated from Black Logo.PNG!');
}

generate().catch(err => { console.error('FAILED:', err); process.exit(1); });
