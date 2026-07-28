const jsdom = require("jsdom");
const { JSDOM } = jsdom;
const fs = require('fs');

const dom = new JSDOM(`
<!DOCTYPE html>
<html>
<body>
    <div id="blogGrid"></div>
</body>
</html>
`);

global.window = dom.window;
global.document = dom.window.document;
global.URLSearchParams = dom.window.URLSearchParams;
global.Event = dom.window.Event;
global.CustomEvent = dom.window.CustomEvent;

try {
    let dataJsCode = fs.readFileSync('blogs/js/data.js', 'utf-8');
    dataJsCode = dataJsCode.replace('const BLOG_POSTS', 'var BLOG_POSTS');
    dataJsCode = dataJsCode.replace('const BLOG_CATEGORIES', 'var BLOG_CATEGORIES');
    dataJsCode = dataJsCode.replace('const BLOG_TAGS', 'var BLOG_TAGS');
    eval(dataJsCode);
    console.log('Loaded data.js. BLOG_POSTS count:', BLOG_POSTS.length);
    
    let blogsJsCode = fs.readFileSync('blogs/js/blogs.js', 'utf-8');
    eval(blogsJsCode);
    console.log('Loaded blogs.js');
    
    // Dispatch DOMContentLoaded
    const event = new Event('DOMContentLoaded');
    document.dispatchEvent(event);
    
    const grid = document.getElementById('blogGrid');
    console.log('Grid innerHTML length:', grid.innerHTML.length);
} catch (e) {
    console.error('Error:', e);
}
