const fs = require('fs');
let data = fs.readFileSync('blogs/js/data.js', 'utf-8');

// The issue is that the content string is wrapped in backticks (`) 
// and contains ${...} which JS tries to interpolate.
// To fix this, we replace all unescaped `${` with `\${`.
// But to be safe, since it's already generated, we can just replace `${` with `\${`.
// Wait, if we replace `${` with `\${`, in a template literal, the output will actually be `${`. This is what we want!

data = data.replace(/\$\{/g, '\\${');

fs.writeFileSync('blogs/js/data.js', data, 'utf-8');
console.log('Fixed interpolation in data.js');
