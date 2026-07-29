const fs = require('fs');

const products = [
    { id: "tshirt-01", category: "T-Shirts", price: 499 },
    { id: "tshirt-02", category: "T-Shirts", price: 499 },
    { id: "premium-tshirt-01", category: "T-Shirts", price: 799 },
    { id: "oversized-tshirt-01", category: "T-Shirts", price: 699 },
    { id: "polo-tshirt-01", category: "T-Shirts", price: 899 },
    { id: "hoodie-01", category: "Hoodies", price: 1299 },
    { id: "coffee-mug-01", category: "Mugs", price: 299 },
    { id: "magic-mug-01", category: "Mugs", price: 399 },
    { id: "steel-bottle-01", category: "Accessories", price: 599 },
    { id: "water-bottle-01", category: "Accessories", price: 349 },
    { id: "travel-mug-01", category: "Mugs", price: 499 },
    { id: "notebook-01", category: "Accessories", price: 249 },
    { id: "premium-diary-01", category: "Accessories", price: 499 },
    { id: "planner-01", category: "Accessories", price: 399 },
    { id: "backpack-01", category: "Accessories", price: 1499 },
    { id: "laptop-bag-01", category: "Accessories", price: 799 },
    { id: "mobile-cover-01", category: "Accessories", price: 299 },
    { id: "pop-socket-01", category: "Accessories", price: 149 },
    { id: "phone-stand-01", category: "Accessories", price: 399 },
    { id: "laptop-stand-01", category: "Accessories", price: 999 },
    { id: "usb-hub-01", category: "Accessories", price: 1299 },
    { id: "keyboard-stickers-01", category: "Stickers", price: 149 },
    { id: "developer-stickers-01", category: "Stickers", price: 199 },
    { id: "mouse-pad-01", category: "Accessories", price: 299 },
    { id: "extended-mouse-pad-01", category: "Accessories", price: 599 },
    { id: "desk-mat-01", category: "Accessories", price: 899 },
    { id: "poster-01", category: "Accessories", price: 249 },
    { id: "framed-poster-01", category: "Accessories", price: 699 },
    { id: "wall-art-01", category: "Accessories", price: 899 },
    { id: "pen-drive-01", category: "Accessories", price: 499 },
    { id: "hard-drive-01", category: "Accessories", price: 3999 },
    { id: "power-bank-01", category: "Accessories", price: 1299 },
    { id: "cable-organizer-01", category: "Accessories", price: 299 },
    { id: "desk-organizer-01", category: "Accessories", price: 499 },
    { id: "headphones-stand-01", category: "Accessories", price: 599 },
    { id: "webcam-cover-01", category: "Accessories", price: 149 },
    { id: "screen-cleaner-01", category: "Accessories", price: 199 },
    { id: "monitor-lamp-01", category: "Accessories", price: 1499 },
    { id: "ring-light-01", category: "Accessories", price: 899 },
    { id: "microphone-01", category: "Accessories", price: 2499 },
    { id: "mechanical-keyboard-01", category: "Accessories", price: 4999 },
    { id: "wireless-mouse-01", category: "Accessories", price: 999 },
    { id: "wrist-rest-01", category: "Accessories", price: 399 },
    { id: "blue-light-glasses-01", category: "Accessories", price: 899 },
    { id: "ergonomic-chair-01", category: "Accessories", price: 7999 },
    { id: "standing-desk-01", category: "Accessories", price: 14999 },
    { id: "foot-rest-01", category: "Accessories", price: 999 },
    { id: "coffee-coasters-01", category: "Accessories", price: 199 },
    { id: "desk-plant-01", category: "Accessories", price: 349 },
    { id: "neon-sign-01", category: "Accessories", price: 1999 },
    { id: "java-notes-pdf-01", category: "E-books", price: 199 },
    { id: "react-notes-pdf-01", category: "E-books", price: 249 },
    { id: "resume-templates-01", category: "Digital Templates", price: 149 },
    { id: "portfolio-templates-01", category: "Digital Templates", price: 499 },
    { id: "thumbnail-templates-01", category: "Digital Templates", price: 299 },
    { id: "api-collection-01", category: "Digital Templates", price: 399 },
    { id: "html-css-templates-01", category: "Digital Templates", price: 199 },
    { id: "cheat-sheets-01", category: "E-books", price: 99 },
    { id: "e-book-collection-01", category: "E-books", price: 499 },
    { id: "java-masterclass-01", category: "Courses", price: 999 },
    { id: "javascript-masterclass-01", category: "Courses", price: 999 },
    { id: "react-masterclass-01", category: "Courses", price: 1299 },
    { id: "nodejs-masterclass-01", category: "Courses", price: 1299 },
    { id: "full-stack-web-development-01", category: "Courses", price: 2999 },
    { id: "dsa-course-01", category: "Courses", price: 1499 },
    { id: "sql-database-course-01", category: "Courses", price: 799 },
    { id: "interview-preparation-01", category: "Courses", price: 899 },
    { id: "devops-course-01", category: "Courses", price: 1999 },
    { id: "linux-course-01", category: "Courses", price: 699 },
    { id: "premiere-pro-presets-01", category: "Digital Templates", price: 399 },
    { id: "after-effects-templates-01", category: "Digital Templates", price: 499 },
    { id: "motion-graphics-pack-01", category: "Digital Templates", price: 599 },
    { id: "thumbnail-templates-02", category: "Digital Templates", price: 299 },
    { id: "youtube-intro-templates-01", category: "Digital Templates", price: 499 },
    { id: "lower-thirds-01", category: "Digital Templates", price: 199 },
    { id: "sound-effects-pack-01", category: "Digital Templates", price: 299 },
    { id: "luts-01", category: "Digital Templates", price: 399 },
    { id: "premium-fonts-collection-01", category: "Digital Templates", price: 249 },
    { id: "icons-pack-01", category: "Digital Templates", price: 199 },
    { id: "figma-templates-01", category: "Digital Templates", price: 499 },
    { id: "ui-kits-01", category: "Digital Templates", price: 799 },
    { id: "website-templates-01", category: "Digital Templates", price: 999 },
    { id: "landing-page-templates-01", category: "Digital Templates", price: 599 },
    { id: "social-media-templates-01", category: "Digital Templates", price: 399 },
    { id: "mentorship-01", category: "Services", price: 1999 },
    { id: "resume-review-01", category: "Services", price: 499 },
    { id: "mock-interview-01", category: "Services", price: 999 },
    { id: "portfolio-review-01", category: "Services", price: 799 },
    { id: "career-guidance-01", category: "Services", price: 899 },
    { id: "freelancing-consultation-01", category: "Services", price: 1499 }
];

function titleCase(str) {
    return str.replace(/-/g, ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}

const finalData = products.map((p, i) => {
    return {
        id: p.id,
        title: titleCase(p.id.replace('-01', '').replace('-02', ' 2').replace('pdf', '(PDF)')),
        category: p.category,
        price: p.price,
        rating: (4.5 + Math.random() * 0.5).toFixed(1),
        reviews: Math.floor(Math.random() * 400) + 50,
        stockStatus: "in-stock",
        featured: i % 5 === 0,
        newArrival: i % 7 === 0,
        image: `../Store Product Images/${p.id}.png`,
        gallery: [
            `../Store Product Images/${p.id}.png`
        ],
        description: `Premium quality ${titleCase(p.id.replace('-01', '').replace('-02', ''))} for developers, designers, and tech enthusiasts. Boost your productivity and style with CodeByTushu.`,
        features: ["Premium Quality", "Modern Design", "Developer Friendly", "Perfect for Workspaces", "Fast Delivery"]
    };
});

const dataContent = `/**
 * CodeByTushu Store - Product Data
 * Auto-generated by script
 */

const storeData = ${JSON.stringify(finalData, null, 4)};

// ─── Helper Functions ──────────────────────────────────────────────────────────

function getProductById(id) {
    return storeData.find(p => p.id === id);
}

function getAllProducts() {
    return storeData;
}

function getFeaturedProducts() {
    return storeData.filter(p => p.featured);
}

function getNewArrivals() {
    return storeData.filter(p => p.newArrival);
}
`;

fs.writeFileSync('E:/Codebytushu/store/js/data.js', dataContent);
console.log('data.js updated with 90 products!');
