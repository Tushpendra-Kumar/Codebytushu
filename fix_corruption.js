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
        } else if (filePath.endsWith('.php')) {
            fileList.push(filePath);
        }
    }
    return fileList;
}

const allFiles = findPhpFiles('E:/Codebytushu');
let count = 0;

for (const filePath of allFiles) {
    let content = fs.readFileSync(filePath, 'utf-8');
    const idx = content.indexOf('<?php require_once <?php');
    
    if (idx !== -1) {
        console.log(`Fixing ${filePath}...`);
        
        // Calculate depth
        const relPath = path.relative('E:/Codebytushu', filePath);
        const depth = relPath.split(path.sep).length - 1;
        
        let requireStr = '';
        if (depth === 0) {
            requireStr = "__DIR__ . '/includes/ai-widget-loader.php'";
        } else if (depth === 1) {
            requireStr = "__DIR__ . '/../includes/ai-widget-loader.php'";
        } else if (depth === 2) {
            requireStr = "__DIR__ . '/../../includes/ai-widget-loader.php'";
        } else {
            const prefix = '/..'.repeat(depth);
            requireStr = `__DIR__ . '${prefix}/includes/ai-widget-loader.php'`;
        }
        
        const correctEnding = `<?php require_once ${requireStr}; ?>\n</body>\n</html>`;
        const newContent = content.substring(0, idx) + correctEnding;
        
        fs.writeFileSync(filePath, newContent, 'utf-8');
        count++;
    }
}

console.log(`Fixed ${count} files successfully!`);
