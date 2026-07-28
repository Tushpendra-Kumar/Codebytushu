const fs = require('fs');

const dataFile = 'blogs/js/data.js';
let dataContent = fs.readFileSync(dataFile, 'utf8');

// Use the old react_blog.html if I can regenerate it.
// Oh wait, I deleted react_blog.html in the cleanup!
// Let me write a script to generate it again.
