const fs = require('fs');
const files = fs.readdirSync('E:/Codebytushu/Store Product Images').filter(f => f.endsWith('.png'));
const ids = files.map(f => f.replace('.png', ''));

const customData = {
    'after-effects-templates-01': { cat: 'Digital Templates', price: 1499, desc: 'Professional After Effects templates to elevate your video production. Perfect for content creators and motion designers.' },
    'backpack-01': { cat: 'Accessories', price: 1999, desc: 'Durable and spacious backpack designed for developers on the go. Features multiple compartments for laptops, gadgets, and everyday essentials.' },
    'baseball-cap-01': { cat: 'Accessories', price: 499, desc: 'Stylish and comfortable baseball cap with a subtle developer-themed logo. Perfect for casual wear or sunny outdoor coding sessions.' },
    'bomber-jacket-01': { cat: 'Hoodies', price: 2499, desc: 'Premium bomber jacket combining street style with tech culture. Keep warm while looking sharp during your late-night coding marathons.' },
    'cable-organizer-01': { cat: 'Accessories', price: 299, desc: 'Keep your workspace clutter-free with this sleek cable organizer. Perfect for managing charging cables, USB cords, and peripheral wires.' },
    'career-guidance-01': { cat: 'Services', price: 999, desc: 'Expert career guidance session tailored for aspiring developers. Get actionable advice on resume building, interviews, and career progression.' },
    'career-roadmaps-01': { cat: 'E-books', price: 299, desc: 'Comprehensive career roadmaps for various tech domains including Frontend, Backend, DevOps, and Data Science. Your step-by-step guide to success.' },
    'cheat-sheets-01': { cat: 'E-books', price: 199, desc: 'High-quality developer cheat sheets covering HTML, CSS, JavaScript, Git, and more. A quick reference guide for your everyday coding needs.' },
    'coffee-mug-01': { cat: 'Mugs', price: 399, desc: 'Classic ceramic coffee mug for your daily caffeine fix. Features a witty coding joke to start your morning right.' },
    'cover-letter-templates-01': { cat: 'Digital Templates', price: 149, desc: 'Professional cover letter templates designed specifically for tech roles. Stand out to recruiters with a clean and impactful design.' },
    'desk-calendar-01': { cat: 'Accessories', price: 249, desc: 'Minimalist desk calendar to help you track project deadlines and milestones. A perfect addition to any productive workspace.' },
    'desk-clock-01': { cat: 'Accessories', price: 899, desc: 'Modern digital desk clock with temperature and humidity display. Keep track of time during intense coding sessions in style.' },
    'desk-mat-01': { cat: 'Accessories', price: 799, desc: 'Large, anti-slip desk mat providing a smooth surface for your mouse and keyboard. Enhances desk aesthetics and protects your workspace.' },
    'desk-organizer-01': { cat: 'Accessories', price: 599, desc: 'Multi-compartment desk organizer to keep your pens, flash drives, and small gadgets neatly arranged. Maximize your desk space.' },
    'desk-plant-01': { cat: 'Accessories', price: 349, desc: 'Low-maintenance artificial desk plant to bring a touch of greenery and calm to your workstation without the hassle of watering.' },
    'developer-quote-stickers-01': { cat: 'Stickers', price: 149, desc: 'Pack of high-quality vinyl stickers featuring relatable and humorous developer quotes. Perfect for customizing your laptop or workspace.' },
    'developer-stickers-01': { cat: 'Stickers', price: 199, desc: 'Assorted collection of popular programming languages and framework logos. Show off your tech stack with these durable laptop stickers.' },
    'devops-course-01': { cat: 'Courses', price: 3999, desc: 'Comprehensive DevOps course covering CI/CD, Docker, Kubernetes, and AWS. Master modern deployment pipelines and infrastructure.' },
    'dsa-course-01': { cat: 'Courses', price: 2999, desc: 'Deep dive into Data Structures and Algorithms. Crack your technical interviews with confidence through hands-on problem solving.' },
    'e-book-collection-01': { cat: 'E-books', price: 999, desc: 'A curated collection of top-rated E-books covering modern web development, clean code practices, and system design principles.' },
    'ergonomic-chair-01': { cat: 'Accessories', price: 8999, desc: 'Premium ergonomic office chair with lumbar support and adjustable armrests. Prevent back pain during long hours of coding.' },
    'extended-mouse-pad-01': { cat: 'Accessories', price: 699, desc: 'Extended gaming-grade mouse pad featuring a sleek dark theme. Provides ultimate precision and comfort for long working hours.' },
    'extended-mouse-pad-03': { cat: 'Accessories', price: 699, desc: 'Premium extended mouse pad with stitched edges and a waterproof surface. Ideal for developers who prefer a clean, minimal desk setup.' },
    'figma-templates-01': { cat: 'Digital Templates', price: 1299, desc: 'High-fidelity Figma UI templates for web and mobile apps. Accelerate your design process with these ready-to-use component libraries.' },
    'framed-poster-01': { cat: 'Accessories', price: 899, desc: 'High-quality framed poster featuring motivational tech artwork. Add a professional and inspiring touch to your home office wall.' },
    'freelancing-consultation-01': { cat: 'Services', price: 1499, desc: 'One-on-one consultation to jumpstart your freelance career. Learn how to find clients, price your services, and build a strong portfolio.' },
    'full-stack-web-development-01': { cat: 'Courses', price: 4999, desc: 'The ultimate Full Stack Web Development bootcamp. Learn MERN stack from scratch and build production-ready applications.' },
    'hoodie-01': { cat: 'Hoodies', price: 1499, desc: 'Extremely comfortable and warm hoodie with a minimalist developer logo. The ultimate uniform for late-night debugging.' },
    'icons-pack-01': { cat: 'Digital Templates', price: 499, desc: 'Comprehensive pack of premium SVG icons tailored for tech and startup websites. Clean, scalable, and easy to integrate.' },
    'interview-preparation-course-01': { cat: 'Courses', price: 1999, desc: 'Complete interview preparation course covering HR rounds, technical questions, and mock interviews to help you land your dream job.' },
    'interview-preparation-guide-01': { cat: 'E-books', price: 399, desc: 'A detailed guide packed with the most frequently asked technical interview questions, optimal solutions, and negotiation tips.' },
    'java-masterclass-01': { cat: 'Courses', price: 2499, desc: 'Master Java programming from basic concepts to advanced multithreading and Spring Boot framework integration.' },
    'javascript-masterclass-01': { cat: 'Courses', price: 2499, desc: 'In-depth JavaScript masterclass covering ES6+, async programming, closures, and modern DOM manipulation techniques.' },
    'javascript-notes-pdf-01': { cat: 'E-books', price: 199, desc: 'Handwritten-style digital notes for JavaScript. Simplifies complex topics into easily digestible visual explanations.' },
    'keyboard-stickers-01': { cat: 'Stickers', price: 149, desc: 'Shortcut keyboard stickers to boost your productivity. Master your IDE and OS shortcuts with these durable, easy-to-apply decals.' },
    'landing-page-templates-01': { cat: 'Digital Templates', price: 899, desc: 'Conversion-optimized HTML/CSS landing page templates. Launch your startup or portfolio quickly with responsive, clean code.' },
    'laptop-bag-01': { cat: 'Accessories', price: 1299, desc: 'Stylish and protective laptop bag with water-resistant fabric. Safely carry your tech gear with multiple padded compartments.' },
    'laptop-skin-01': { cat: 'Accessories', price: 399, desc: 'Premium vinyl laptop skin featuring a sleek carbon fiber texture. Protect your device from scratches while adding a touch of style.' },
    'laptop-sleeve-01': { cat: 'Accessories', price: 599, desc: 'Slim and lightweight laptop sleeve with soft fleece lining. Provides excellent protection against dust, bumps, and accidental drops.' },
    'laptop-stand-01': { cat: 'Accessories', price: 899, desc: 'Ergonomic aluminum laptop stand to elevate your screen to eye level. Improves posture and enhances laptop cooling.' },
    'laptop-stickers-01': { cat: 'Stickers', price: 249, desc: 'A massive pack of diverse tech and pop-culture stickers to fully personalize the lid of your laptop. High quality and residue-free.' },
    'linux-course-01': { cat: 'Courses', price: 1499, desc: 'Master Linux command line, shell scripting, and system administration. Essential skills for every backend developer and DevOps engineer.' },
    'long-sleeve-tshirt-01': { cat: 'T-Shirts', price: 699, desc: 'Comfortable long-sleeve t-shirt for breezy days. Features a subtle, elegant design that screams code without being loud.' },
    'lower-thirds-01': { cat: 'Digital Templates', price: 499, desc: 'Professional lower thirds templates for Premiere Pro and After Effects. Perfect for YouTube tutorials, interviews, and vlogs.' },
    'luts-01': { cat: 'Digital Templates', price: 399, desc: 'Cinematic color grading LUTs pack to instantly enhance your video projects. Compatible with all major video editing software.' },
    'magic-mug-01': { cat: 'Mugs', price: 499, desc: 'Heat-sensitive magic mug that reveals a hidden developer joke or code snippet when filled with hot coffee or tea.' },
    'mentorship-01': { cat: 'Services', price: 4999, desc: 'One-month exclusive mentorship program. Get personalized code reviews, architecture discussions, and career guidance from industry experts.' },
    'mobile-cover-01': { cat: 'Accessories', price: 299, desc: 'Durable, shock-absorbing mobile cover featuring an exclusive circuit board design. Protect your phone in tech-inspired style.' },
    'mock-interview-01': { cat: 'Services', price: 899, desc: '1-hour rigorous technical mock interview with actionable feedback. Identify your weak points and improve your problem-solving under pressure.' },
    'motion-graphics-pack-01': { cat: 'Digital Templates', price: 1999, desc: 'Massive pack of pre-animated motion graphics including transitions, titles, and backgrounds to supercharge your video editing.' },
    'mouse-pad-01': { cat: 'Accessories', price: 249, desc: 'Standard size, high-precision mouse pad with a non-slip rubber base. Ideal for everyday browsing and precise UI/UX design work.' },
    'nodejs-masterclass-01': { cat: 'Courses', price: 2499, desc: 'Complete Node.js masterclass covering Express, MongoDB, authentication, and REST API development. Build scalable backend systems.' },
    'nodejs-notes-pdf-01': { cat: 'E-books', price: 199, desc: 'Comprehensive PDF notes on Node.js and Express. Detailed explanations of the event loop, streams, and backend architecture.' },
    'notebook-01': { cat: 'Accessories', price: 299, desc: 'High-quality dot-grid notebook, perfect for sketching out UI wireframes, algorithm logic, or taking meeting notes.' },
    'oversized-tshirt-01': { cat: 'T-Shirts', price: 699, desc: 'Trendy oversized t-shirt made from heavy cotton. Maximum comfort for long coding sessions or casual meetups.' },
    'phone-stand-01': { cat: 'Accessories', price: 199, desc: 'Adjustable desktop phone stand. Perfect for keeping your device visible for notifications, video calls, or testing mobile apps.' },
    'planner-01': { cat: 'Accessories', price: 399, desc: 'Daily productivity planner tailored for software engineers. Plan your sprints, track bugs, and manage your daily goals effectively.' },
    'polo-tshirt-01': { cat: 'T-Shirts', price: 899, desc: 'Premium polo t-shirt with an embroidered tech logo. A smart-casual choice for office days, client meetings, or tech conferences.' },
    'pop-socket-01': { cat: 'Accessories', price: 149, desc: 'Convenient phone grip and stand featuring a cool developer icon. Enhances your grip and prevents accidental drops.' },
    'portfolio-review-01': { cat: 'Services', price: 499, desc: 'Professional review of your developer portfolio and GitHub profile. Get constructive feedback to make your projects stand out to employers.' },
    'portfolio-templates-01': { cat: 'Digital Templates', price: 599, desc: 'Responsive and modern portfolio website templates. Showcase your projects and skills with these easily customizable HTML/React templates.' },
    'premiere-pro-presets-01': { cat: 'Digital Templates', price: 699, desc: 'Time-saving Premiere Pro presets including text effects, color grades, and smooth transitions to speed up your video workflow.' },
    'premium-diary-01': { cat: 'Accessories', price: 499, desc: 'Elegant leather-bound diary with premium quality paper. Perfect for journaling, brainstorming, and writing down your next big startup idea.' },
    'premium-fonts-collection-01': { cat: 'Digital Templates', price: 399, desc: 'A curated collection of modern, developer-friendly fonts for coding and UI design. Includes popular monospaced and sans-serif typefaces.' },
    'premium-pen-01': { cat: 'Accessories', price: 299, desc: 'Sleek, smooth-writing premium rollerball pen. A great companion for your notebook and everyday note-taking.' },
    'premium-pen-set-01': { cat: 'Accessories', price: 799, desc: 'Luxury pen set presented in a stylish case. Makes for an excellent gift for a fellow developer, manager, or client.' },
    'premium-tshirt-01': { cat: 'T-Shirts', price: 799, desc: 'Ultra-soft, premium tri-blend t-shirt. Offers superior fit and unmatched comfort, featuring an exclusive minimalist design.' },
    'react-masterclass-01': { cat: 'Courses', price: 2999, desc: 'Master React.js from fundamentals to advanced concepts like hooks, context API, Redux, and performance optimization.' },
    'react-notes-pdf-01': { cat: 'E-books', price: 199, desc: 'Visual PDF notes covering React components, state management, and lifecycle methods. An excellent quick-revision tool.' },
    'resume-review-01': { cat: 'Services', price: 399, desc: 'Detailed resume critique by industry professionals. Learn how to optimize your ATS score and highlight your core technical skills.' },
    'resume-review-02': { cat: 'Services', price: 599, desc: 'Premium resume overhaul service. We rewrite and format your resume to maximize your chances of passing recruiter screenings.' },
    'resume-templates-01': { cat: 'Digital Templates', price: 299, desc: 'ATS-friendly, professionally designed resume templates. Available in Word, Figma, and LaTeX formats to suit your preference.' },
    'screen-cleaner-01': { cat: 'Accessories', price: 249, desc: 'All-in-one screen cleaning kit featuring a microfiber cloth and safe cleaning solution. Keep your monitors and laptops spotless.' },
    'social-media-templates-01': { cat: 'Digital Templates', price: 499, desc: 'Ready-to-use social media post templates for Instagram, LinkedIn, and Twitter. Perfect for tech influencers and startup pages.' },
    'sound-effects-pack-01': { cat: 'Digital Templates', price: 599, desc: 'High-quality sound effects library tailored for UI interactions, app notifications, and tech-related video content.' },
    'sql-database-course-01': { cat: 'Courses', price: 1999, desc: 'Comprehensive guide to SQL and relational databases. Learn complex queries, indexing, normalization, and database design.' },
    'sql-notes-pdf-01': { cat: 'E-books', price: 149, desc: 'Clear and concise PDF notes on SQL syntax and best practices. A handy reference for backend devs and data analysts.' },
    'steel-bottle-01': { cat: 'Accessories', price: 699, desc: 'High-grade stainless steel water bottle. Double-wall vacuum insulation keeps your drinks cold or hot during long coding sessions.' },
    'sticky-notes-set-01': { cat: 'Accessories', price: 149, desc: 'Colorful set of sticky notes for brainstorming, kanban boards, and quick reminders. An essential tool for agile planning.' },
    'sweatshirt-01': { cat: 'Hoodies', price: 1299, desc: 'Classic crewneck sweatshirt with a comfortable, relaxed fit. Features a stylish tech motif for your everyday casual look.' },
    'thumbnail-templates-01': { cat: 'Digital Templates', price: 299, desc: 'Eye-catching YouTube thumbnail templates specifically designed for tech tutorials, coding vlogs, and product reviews.' },
    'tote-bag-01': { cat: 'Accessories', price: 399, desc: 'Eco-friendly canvas tote bag with a fun programming pun. Great for carrying groceries, books, or your everyday essentials.' },
    'travel-mug-01': { cat: 'Mugs', price: 599, desc: 'Spill-proof, insulated travel mug. Keep your coffee piping hot while commuting to the office or working from a cafe.' },
    'tshirt-01': { cat: 'T-Shirts', price: 499, desc: 'Our classic flagship developer t-shirt. Soft, breathable cotton featuring the iconic CodeByTushu logo.' },
    'ui-kits-01': { cat: 'Digital Templates', price: 1499, desc: 'Massive UI kit containing hundreds of web and mobile components. Speed up your prototyping and design workflow effortlessly.' },
    'usb-hub-01': { cat: 'Accessories', price: 899, desc: 'Multi-port USB-C hub adapter. Expand your laptop\'s connectivity with extra USB-A, HDMI, and card reader slots.' },
    'webcam-cover-01': { cat: 'Accessories', price: 99, desc: 'Ultra-thin sliding webcam cover. Protect your privacy and prevent unwanted spying without interfering with laptop closure.' },
    'website-templates-01': { cat: 'Digital Templates', price: 1999, desc: 'Premium, fully responsive website templates. Jumpstart your next client project with these beautifully designed themes.' },
    'wrist-rest-01': { cat: 'Accessories', price: 349, desc: 'Ergonomic memory foam wrist rest for keyboards. Prevents fatigue and carpal tunnel during intensive typing sessions.' },
    'youtube-intro-templates-01': { cat: 'Digital Templates', price: 499, desc: 'Dynamic and professional YouTube intro sequences. Grab your audience\'s attention right from the first second.' }
};

let jsContent = `/**
 * CodeByTushu Store - Product Data
 * Auto-generated by script for 90 unique products
 */

const storeData = [
`;

ids.forEach((id, index) => {
    const defaultTitle = id.replace(/-0[1-9]$/, '').split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
    const data = customData[id] || { cat: 'Accessories', price: 499, desc: 'Premium quality ' + defaultTitle + ' designed for developers and tech enthusiasts.' };
    
    // Generate some dynamic mock data
    // Use pseudo-random so it doesn't change on every run
    const num = id.length;
    const rating = (4.5 + (num % 6) * 0.1).toFixed(1);
    const reviews = 50 + (num * 17) % 350;
    const isFeatured = (num % 5) === 0;
    const isNew = (num % 7) === 0;
    
    jsContent += `    {
        "id": "${id}",
        "title": "${defaultTitle}",
        "category": "${data.cat}",
        "price": ${data.price},
        "rating": "${rating}",
        "reviews": ${reviews},
        "stockStatus": "in-stock",
        "featured": ${isFeatured},
        "newArrival": ${isNew},
        "image": "../Store Product Images/${id}.png",
        "gallery": [
            "../Store Product Images/${id}.png"
        ],
        "description": "${data.desc}",
        "features": [
            "Premium Quality",
            "Modern Design",
            "Developer Friendly",
            "Perfect for Workspaces",
            "Fast Delivery"
        ]
    }${index === ids.length - 1 ? '' : ','}
`;
});

jsContent += `];

// Export for module systems (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = storeData;
}
`;

fs.writeFileSync('E:/Codebytushu/store/js/data.js', jsContent, 'utf8');
console.log('Successfully generated data.js with 90 unique products.');
