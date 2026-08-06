const fs = require('fs');
const path = require('path');

function findPhpFiles(dir, fileList = []) {
    const files = fs.readdirSync(dir);
    for (const file of files) {
        const filePath = path.join(dir, file);
        if (fs.statSync(filePath).isDirectory()) {
            if (!filePath.includes('vendor') && !filePath.includes('node_modules')) {
                findPhpFiles(filePath, fileList);
            }
        } else if (filePath.endsWith('.php') || filePath.endsWith('.html')) {
            fileList.push(filePath);
        }
    }
    return fileList;
}

const allFiles = findPhpFiles('E:/Codebytushu');
let count = 0;

for (const filePath of allFiles) {
    const buffer = fs.readFileSync(filePath);
    
    // Check if the file starts with UTF-8 BOM (EF BB BF)
    if (buffer.length >= 3 && buffer[0] === 0xEF && buffer[1] === 0xBB && buffer[2] === 0xBF) {
        console.log(`Removing BOM from ${filePath}...`);
        
        // Write the file back without the first 3 bytes
        const newBuffer = buffer.subarray(3);
        fs.writeFileSync(filePath, newBuffer);
        count++;
    }
}

console.log(`Removed BOM from ${count} files successfully!`);
