const fs = require('fs');

let dataJs = fs.readFileSync('e:\\Codebytushu\\blogs\\js\\data.js', 'utf8');

// Remove remaining citations
dataJs = dataJs.replace(/ã€ [^ã€ ]*?ã€‘/g, '');

// Clean any remaining â€ symbols
dataJs = dataJs.replace(/â€™/g, "'");
dataJs = dataJs.replace(/â€œ/g, '"');
dataJs = dataJs.replace(/â€\x9D/g, '"'); // â€ 
dataJs = dataJs.replace(/â€/g, '"'); 
dataJs = dataJs.replace(/â€“/g, '-');
dataJs = dataJs.replace(/â€”/g, '--');

// Also remove JAVA and Copy if they are standalone paragraphs
dataJs = dataJs.replace(/<p>JAVA<\/p>/gi, '');
dataJs = dataJs.replace(/<p>Copy<\/p>/gi, '');

fs.writeFileSync('e:\\Codebytushu\\blogs\\js\\data.js', dataJs, 'utf8');
console.log("Cleanup done.");
