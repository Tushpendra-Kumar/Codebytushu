const fs = require('fs');

let dataJs = fs.readFileSync('e:\\Codebytushu\\blogs\\js\\data.js', 'utf8');

// 1. Remove deep research citation markers
// Markers in utf-8 like ã€ 14â€ L43-L47ã€‘ 
// In the text, they might look like: ã€ 14â€&nbsp;L43-L47ã€‘ or a??14?&nbsp;L43-L47a?`
dataJs = dataJs.replace(/ã€ \d+[^ã€ ]*?ã€‘/g, '');
dataJs = dataJs.replace(/a\?\?\d+\?&nbsp;L\d+-L\d+a\?`/g, '');
dataJs = dataJs.replace(/a\?\?\d+\?&nbsp;L\d+-L\d+a\?/g, '');
dataJs = dataJs.replace(/\[\d+†L\d+-L\d+\]/g, '');

// 2. Remove "JAVA" and "Copy" if they are standalone paragraphs
dataJs = dataJs.replace(/<p>JAVA<\/p>\s*<p>Copy<\/p>/gi, '');
dataJs = dataJs.replace(/<p>JAVA<\/p>/gi, '');
dataJs = dataJs.replace(/<p>Copy<\/p>/gi, '');
dataJs = dataJs.replace(/JAVA\s*Copy/g, '');

// 3. Fix common garbled quotes and dashes (Windows-1252 / ISO-8859-1 to UTF-8 issues)
dataJs = dataJs.replace(/â€™/g, "'");
dataJs = dataJs.replace(/â€œ/g, '"');
dataJs = dataJs.replace(/â€\x9D/g, '"'); // â€
dataJs = dataJs.replace(/â€/g, '"'); 
dataJs = dataJs.replace(/â€“/g, '-');
dataJs = dataJs.replace(/â€”/g, '--');
dataJs = dataJs.replace(/\?T/g, "'");
dataJs = dataJs.replace(/\?"/g, '-');

// 4. Remove BOM if present at the very beginning of the file
if (dataJs.charCodeAt(0) === 0xFEFF) {
    dataJs = dataJs.slice(1);
}

// 5. Remove any leftover weird symbols like dY
dataJs = dataJs.replace(/dY`%/g, '');
dataJs = dataJs.replace(/dY>&nbsp;,\?/g, '');
dataJs = dataJs.replace(/dY"~o"/g, '');
dataJs = dataJs.replace(/dY'/g, '');

fs.writeFileSync('e:\\Codebytushu\\blogs\\js\\data.js', dataJs, 'utf8');
console.log("Cleanup applied.");
