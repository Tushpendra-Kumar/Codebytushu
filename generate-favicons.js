/**
 * generate-favicons.js
 * Generates all required favicon sizes from the CodeByTushu White Logo.
 * Source: /image1/White Logo.png  (gold+white logo on black background)
 * Output: all files placed at project root — clean paths, no subfolder spaces.
 */
const sharp = require('sharp');
const path  = require('path');
const fs    = require('fs');

const ROOT   = path.resolve(__dirname);
const SOURCE = path.join(ROOT, 'image1', 'White Logo.png');

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
                fit: 'contain',           // keep aspect ratio, pad remaining area
                background: { r:0, g:0, b:0, alpha:1 }   // black background (matches logo)
            })
            .png({ quality: 95, compressionLevel: 9 })
            .toFile(dest);
        const { size: bytes } = fs.statSync(dest);
        console.log(`✅  ${file}  (${size}×${size})  →  ${bytes} bytes`);
    }

    // Create favicon.ico from the 32x32 PNG
    // Modern browsers accept a PNG wrapped as .ico
    // For a true multi-size ICO we write the raw ICO binary ourselves.
    const ico32 = await sharp(SOURCE)
        .resize(32, 32, { fit:'contain', background:{r:0,g:0,b:0,alpha:1} })
        .png()
        .toBuffer();

    const ico16 = await sharp(SOURCE)
        .resize(16, 16, { fit:'contain', background:{r:0,g:0,b:0,alpha:1} })
        .png()
        .toBuffer();

    // Build a simple ICO (ICONDIR + ICONDIRENTRY×2 + image data)
    function icoBuffer(images) {
        // images = [{width, height, buffer}]
        const headerSize = 6;
        const entrySize  = 16;
        const entries    = images.length;
        let offset = headerSize + entrySize * entries;

        const parts = [];

        // ICONDIR
        const header = Buffer.alloc(6);
        header.writeUInt16LE(0, 0);      // reserved
        header.writeUInt16LE(1, 2);      // type = ICO
        header.writeUInt16LE(entries, 4);
        parts.push(header);

        // ICONDIRENTRY for each image
        for (const img of images) {
            const entry = Buffer.alloc(16);
            entry.writeUInt8(img.width  > 255 ? 0 : img.width,  0);
            entry.writeUInt8(img.height > 255 ? 0 : img.height, 1);
            entry.writeUInt8(0, 2);  // color count (0 = more than 256)
            entry.writeUInt8(0, 3);  // reserved
            entry.writeUInt16LE(1, 4); // planes
            entry.writeUInt16LE(32, 6); // bit count
            entry.writeUInt32LE(img.buffer.length, 8);
            entry.writeUInt32LE(offset, 12);
            offset += img.buffer.length;
            parts.push(entry);
        }

        // Image data
        for (const img of images) {
            parts.push(img.buffer);
        }

        return Buffer.concat(parts);
    }

    const icoData = icoBuffer([
        { width: 16, height: 16, buffer: ico16 },
        { width: 32, height: 32, buffer: ico32 },
    ]);

    const icoDest = path.join(ROOT, 'favicon.ico');
    fs.writeFileSync(icoDest, icoData);
    console.log(`✅  favicon.ico  (16+32 multi-size ICO)  →  ${icoData.length} bytes`);

    // Also copy into the 'favicon logo' folder to keep it in sync
    const favDir = path.join(ROOT, 'favicon logo');
    for (const { file } of [...jobs, {file:'favicon.ico'}]) {
        fs.copyFileSync(path.join(ROOT, file), path.join(favDir, file));
    }
    console.log('✅  Synced copies to "favicon logo/" folder');

    console.log('\n✅  All favicon files generated from White Logo.png!');
}

generate().catch(err => { console.error('FAILED:', err); process.exit(1); });
