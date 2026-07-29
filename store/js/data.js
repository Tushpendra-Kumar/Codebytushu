/**
 * CodeByTushu Store - Product Data
 * 
 * IMAGE REPLACEMENT GUIDE:
 * When your actual product images are ready, place them in:
 *   /assets/images/products/
 * Then update the image paths below from the Unsplash URLs
 * to relative paths like: "../assets/images/products/tshirt-01.webp"
 * 
 * Each product's image filename is listed in its comment above it.
 */

const storeData = [

    // ─── T-SHIRTS ──────────────────────────────────────────────────────────────

    {
        // Image file: tshirt-01.webp
        id: "tshirt-01",
        title: "Developer Mode T-Shirt",
        category: "T-Shirts",
        price: 499,
        rating: 4.8,
        reviews: 120,
        stockStatus: "in-stock",
        featured: true,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Premium cotton t-shirt with 'Developer Mode: ON' minimal typography. Perfect for long coding sessions and everyday wear.",
        features: ["100% Premium Cotton", "Comfortable Regular Fit", "High-quality Durable Print", "Pre-shrunk Fabric", "Tagless for comfort"]
    },
    {
        // Image file: tshirt-02.webp
        id: "tshirt-02",
        title: "Eat Sleep Code T-Shirt",
        category: "T-Shirts",
        price: 499,
        rating: 4.7,
        reviews: 95,
        stockStatus: "in-stock",
        featured: false,
        newArrival: true,
        image: "https://images.unsplash.com/photo-1622445275576-721325763afe?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1622445275576-721325763afe?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1503342394128-c104d54dba01?w=800&auto=format&fit=crop&q=80"
        ],
        description: "The classic developer mantra, printed bold. Soft, breathable fabric made for long productive sessions. Wear your passion every day.",
        features: ["100% Cotton Blend", "Regular Fit", "Breathable Fabric", "Bold Typography Print", "Machine Washable"]
    },
    {
        // Image file: premium-tshirt-01.webp
        id: "premium-tshirt-01",
        title: "Premium Tech T-Shirt",
        category: "T-Shirts",
        price: 799,
        rating: 4.9,
        reviews: 210,
        stockStatus: "in-stock",
        featured: true,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1562157873-818bc0726f68?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Elevate your everyday style with this premium quality t-shirt designed for the modern tech professional. Supima cotton, tailored fit.",
        features: ["Supima Premium Cotton", "Tailored Slim Fit", "Wrinkle-resistant", "Reinforced Stitching", "Enzyme Washed for Softness"]
    },
    {
        // Image file: oversized-tshirt-01.webp
        id: "oversized-tshirt-01",
        title: "Bug Hunter Oversized T-Shirt",
        category: "T-Shirts",
        price: 699,
        rating: 4.8,
        reviews: 150,
        stockStatus: "in-stock",
        featured: false,
        newArrival: true,
        image: "https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Maximum comfort while you debug. This boxy oversized t-shirt offers a trendy streetwear silhouette with heavyweight cotton.",
        features: ["Heavyweight 240gsm Cotton", "Oversized Boxy Fit", "Drop Shoulder Design", "Chest Bug Hunter Graphic", "Pre-shrunk"]
    },
    {
        // Image file: polo-tshirt-01.webp
        id: "polo-tshirt-01",
        title: "Smart Coder Polo T-Shirt",
        category: "T-Shirts",
        price: 899,
        rating: 4.6,
        reviews: 80,
        stockStatus: "in-stock",
        featured: false,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1598033129183-c4f50c736f10?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1598033129183-c4f50c736f10?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Smart-casual polo for tech meetings or casual Fridays. Features a subtle embroidered code bracket logo on the chest.",
        features: ["Premium Pique Cotton", "Embroidered Logo", "Ribbed Collar & Cuffs", "3-button Placket", "Regular Fit"]
    },

    // ─── HOODIES ──────────────────────────────────────────────────────────────

    {
        // Image file: hoodie-01.webp
        id: "hoodie-01",
        title: "Night Owl Developer Hoodie",
        category: "Hoodies",
        price: 1299,
        rating: 4.9,
        reviews: 340,
        stockStatus: "in-stock",
        featured: true,
        newArrival: true,
        image: "https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Stay warm during those late-night coding grinds. Ultra-soft fleece-lined interior with a sleek tech-inspired graphic design.",
        features: ["Ultra-soft Fleece Lining", "Adjustable Drawstring Hood", "Front Kangaroo Pocket", "Ribbed Cuffs & Hem", "Unisex Oversized Fit"]
    },

    // ─── MUGS ─────────────────────────────────────────────────────────────────

    {
        // Image file: coffee-mug-01.webp
        id: "coffee-mug-01",
        title: "Code Fuel Coffee Mug",
        category: "Mugs",
        price: 299,
        rating: 4.7,
        reviews: 185,
        stockStatus: "in-stock",
        featured: true,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1610631787813-9eeb1a2386cc?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Convert coffee into code with maximum efficiency. 11oz ceramic mug featuring the classic 'Coffee → Code' developer formula.",
        features: ["11oz Ceramic", "Microwave Safe", "Dishwasher Safe", "Glossy Finish", "C-handle Grip"]
    },
    {
        // Image file: magic-mug-01.webp
        id: "magic-mug-01",
        title: "Dark Mode Magic Mug",
        category: "Mugs",
        price: 399,
        rating: 4.8,
        reviews: 120,
        stockStatus: "in-stock",
        featured: false,
        newArrival: true,
        image: "https://images.unsplash.com/photo-1577937927133-66ef06acdf18?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1577937927133-66ef06acdf18?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Pour hot liquid and watch the hidden code reveal itself! A color-changing magic mug — black in normal mode, full-of-code in dark mode.",
        features: ["Heat-sensitive Coating", "11oz Capacity", "Hand Wash Recommended", "Reveals Hidden Design", "Gift-ready Packaging"]
    },
    {
        // Image file: travel-mug-01.webp
        id: "travel-mug-01",
        title: "Nomad Developer Travel Mug",
        category: "Mugs",
        price: 499,
        rating: 4.6,
        reviews: 90,
        stockStatus: "in-stock",
        featured: false,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1570600930-dfa2c8c2b6fb?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1570600930-dfa2c8c2b6fb?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Take your coffee on the go. Insulated stainless steel travel mug keeps drinks hot for 6 hours and cold for 12 hours.",
        features: ["Double-wall Insulation", "Spill-proof Twist Lid", "Fits Standard Cup Holders", "400ml Capacity", "BPA-free"]
    },

    // ─── DESK / ACCESSORIES ───────────────────────────────────────────────────

    {
        // Image file: steel-bottle-01.webp
        id: "steel-bottle-01",
        title: "Stay Hydrated Steel Bottle",
        category: "Accessories",
        price: 599,
        rating: 4.9,
        reviews: 230,
        stockStatus: "in-stock",
        featured: true,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1602143407151-7111542de6e8?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1602143407151-7111542de6e8?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1612164801713-79ef75c6e91a?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Premium stainless steel water bottle. Keeps water ice cold for 24 hours so you stay hydrated during those marathon coding sessions.",
        features: ["18/8 Stainless Steel", "Vacuum Insulated", "Leak-proof Lid", "750ml Capacity", "Wide Mouth"]
    },
    {
        // Image file: water-bottle-01.webp
        id: "water-bottle-01",
        title: "Minimalist Water Bottle",
        category: "Accessories",
        price: 349,
        rating: 4.5,
        reviews: 75,
        stockStatus: "in-stock",
        featured: false,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1523362628745-0c100150b504?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1523362628745-0c100150b504?w=800&auto=format&fit=crop&q=80"
        ],
        description: "A lightweight and durable water bottle with a sleek, minimalist design. Great for your desk or gym.",
        features: ["BPA-free Tritan Plastic", "Lightweight", "Easy Carry Loop", "500ml Capacity", "Dishwasher Safe"]
    },
    {
        // Image file: notebook-01.webp
        id: "notebook-01",
        title: "Code Architecture Notebook",
        category: "Accessories",
        price: 249,
        rating: 4.7,
        reviews: 110,
        stockStatus: "in-stock",
        featured: false,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1517842645767-c639042777db?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1517842645767-c639042777db?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1531346878377-a5be20888e57?w=800&auto=format&fit=crop&q=80"
        ],
        description: "A dotted-grid notebook built for developers. Sketch system architectures, ERDs, and algorithm diagrams with precision.",
        features: ["A5 Size (148x210mm)", "Dotted Grid Pages", "120gsm Premium Paper", "Lay-flat Binding", "Hardcover"]
    },
    {
        // Image file: premium-diary-01.webp
        id: "premium-diary-01",
        title: "Tech Executive Premium Diary",
        category: "Accessories",
        price: 499,
        rating: 4.8,
        reviews: 85,
        stockStatus: "in-stock",
        featured: true,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1531346878377-a5be20888e57?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1531346878377-a5be20888e57?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1517842645767-c639042777db?w=800&auto=format&fit=crop&q=80"
        ],
        description: "A luxurious leather-bound diary for your daily stand-up notes, to-dos, and big ideas. A must-have for every serious developer.",
        features: ["Faux Leather Cover", "Ribbon Bookmark", "200 Ruled Pages", "Elastic Closure", "Pen Loop Included"]
    },
    {
        // Image file: planner-01.webp
        id: "planner-01",
        title: "Sprint & Goals Planner",
        category: "Accessories",
        price: 399,
        rating: 4.6,
        reviews: 65,
        stockStatus: "in-stock",
        featured: false,
        newArrival: true,
        image: "https://images.unsplash.com/photo-1506784365847-bbad939e9335?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1506784365847-bbad939e9335?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Organize your weeks in sprints. A dedicated planner to track coding goals, habits, and personal projects like a pro.",
        features: ["Undated Weekly Planner", "Sprint Tracking Pages", "Habit Tracker", "Goal Setting Section", "Minimalist Design"]
    },
    {
        // Image file: backpack-01.webp
        id: "backpack-01",
        title: "Tech Nomad Backpack",
        category: "Accessories",
        price: 1499,
        rating: 4.9,
        reviews: 280,
        stockStatus: "in-stock",
        featured: true,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1622560480605-d83c853bc5c3?w=800&auto=format&fit=crop&q=80"
        ],
        description: "A spacious, water-resistant backpack designed specifically to carry all your tech gear safely. Perfect for coders on the move.",
        features: ["Water-resistant Nylon", "Padded Laptop Sleeve (up to 16\")", "USB Charging Port", "Multiple Compartments", "Anti-theft Hidden Pocket"]
    },
    {
        // Image file: laptop-bag-01.webp
        id: "laptop-bag-01",
        title: "Minimalist Laptop Sleeve",
        category: "Accessories",
        price: 799,
        rating: 4.7,
        reviews: 140,
        stockStatus: "in-stock",
        featured: false,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1547949003-9792a18a2601?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1547949003-9792a18a2601?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Protect your machine with this sleek, slim-profile laptop sleeve. Clean minimalist design that fits right into any professional setting.",
        features: ["Soft Microfiber Inner Lining", "Slim Profile", "Water-repellent Exterior", "Magnetic Closure", "Fits up to 15-inch Laptops"]
    },
    {
        // Image file: mobile-cover-01.webp
        id: "mobile-cover-01",
        title: "Circuit Board Mobile Cover",
        category: "Accessories",
        price: 299,
        rating: 4.5,
        reviews: 200,
        stockStatus: "in-stock",
        featured: false,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Show off your inner hardware geek. Durable circuit board pattern mobile cover with military-grade drop protection.",
        features: ["Shock-absorbent TPU", "Matte Finish", "Precise Port Cutouts", "Raised Edge Screen Protection", "Anti-slip Grip"]
    },
    {
        // Image file: pop-socket-01.webp
        id: "pop-socket-01",
        title: "Terminal Icon Pop Socket",
        category: "Accessories",
        price: 149,
        rating: 4.4,
        reviews: 95,
        stockStatus: "in-stock",
        featured: false,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1583394838336-acd977736f90?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1583394838336-acd977736f90?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Get a better grip on your phone and show off your terminal love. Features the classic '$ _' terminal prompt icon.",
        features: ["Strong 3M Adhesive", "Collapsible & Foldable", "360° Swivel", "Phone Stand Function", "Re-stickable"]
    },
    {
        // Image file: phone-stand-01.webp
        id: "phone-stand-01",
        title: "Aluminum Phone Stand",
        category: "Accessories",
        price: 399,
        rating: 4.8,
        reviews: 155,
        stockStatus: "in-stock",
        featured: false,
        newArrival: true,
        image: "https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Keep your phone at the perfect eye level on your desk while you code. Sturdy brushed aluminum with a premium feel.",
        features: ["Brushed Aluminum Alloy", "Adjustable Viewing Angle", "Anti-slip Rubber Pads", "Foldable for Travel", "Universal Fit"]
    },
    {
        // Image file: laptop-stand-01.webp
        id: "laptop-stand-01",
        title: "Ergonomic Laptop Stand",
        category: "Accessories",
        price: 999,
        rating: 4.9,
        reviews: 320,
        stockStatus: "in-stock",
        featured: true,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1587614387466-0a72ca909e16?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Improve your posture and reduce neck strain with this foldable, adjustable aluminum laptop stand. Supports up to 17-inch laptops.",
        features: ["Aircraft-grade Aluminum", "6 Adjustable Height Levels", "Hollow Cooling Design", "Foldable & Portable", "Supports up to 8kg"]
    },
    {
        // Image file: usb-hub-01.webp
        id: "usb-hub-01",
        title: "7-in-1 Type-C USB Hub",
        category: "Accessories",
        price: 1299,
        rating: 4.7,
        reviews: 210,
        stockStatus: "in-stock",
        featured: true,
        newArrival: true,
        image: "https://images.unsplash.com/photo-1625895197185-efcec01cffe0?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1625895197185-efcec01cffe0?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Expand your laptop's connectivity with this essential 7-in-1 USB-C hub. The perfect companion for modern ultrabooks and MacBooks.",
        features: ["4K HDMI Output", "3x USB 3.0 Ports", "SD & TF Card Reader", "100W PD Charging", "Plug & Play"]
    },

    // ─── STICKERS ─────────────────────────────────────────────────────────────

    {
        // Image file: keyboard-stickers-01.webp
        id: "keyboard-stickers-01",
        title: "Shortcut Keys Sticker Pack",
        category: "Stickers",
        price: 149,
        rating: 4.8,
        reviews: 420,
        stockStatus: "in-stock",
        featured: true,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1572375992501-4b0892d50c69?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Boost your productivity and flex your shortcuts. High-quality keyboard sticker pack for VS Code, Chrome, Mac OS and more.",
        features: ["Pack of 20 Stickers", "Waterproof Vinyl", "Residue-free Removal", "Matte Finish", "UV Resistant"]
    },
    {
        // Image file: developer-quote-stickers-01.webp
        id: "developer-quote-stickers-01",
        title: "Dev Quotes Sticker Pack",
        category: "Stickers",
        price: 199,
        rating: 4.9,
        reviews: 310,
        stockStatus: "in-stock",
        featured: false,
        newArrival: true,
        image: "https://images.unsplash.com/photo-1572375992501-4b0892d50c69?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1572375992501-4b0892d50c69?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Decorate your laptop with funny and relatable developer quotes, memes, and tech logos. 15 unique die-cut stickers.",
        features: ["Pack of 15 Stickers", "Die-cut Design", "Durable Vinyl", "Waterproof & Scratch-proof", "Glossy Finish"]
    },

    // ─── E-BOOKS ──────────────────────────────────────────────────────────────

    {
        // Image file: java-notes-pdf-01.webp
        id: "java-notes-pdf-01",
        title: "Java Mastery Notes (PDF)",
        category: "E-books",
        price: 199,
        rating: 5.0,
        reviews: 450,
        stockStatus: "in-stock",
        featured: true,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Comprehensive, handwritten-style PDF notes for mastering Java — from OOP fundamentals to Multithreading and Spring Boot basics.",
        features: ["Instant PDF Download", "Visual Diagrams & Flowcharts", "100+ Code Snippets", "Java 17+ Covered", "Interview Q&A Section"]
    },
    {
        // Image file: react-notes-pdf-01.webp
        id: "react-notes-pdf-01",
        title: "React.js Complete Guide (PDF)",
        category: "E-books",
        price: 249,
        rating: 4.9,
        reviews: 380,
        stockStatus: "in-stock",
        featured: true,
        newArrival: true,
        image: "https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=800&auto=format&fit=crop&q=80"
        ],
        description: "Your ultimate resource for mastering React.js. Covers Hooks, Context API, Redux, performance optimization, and real project patterns.",
        features: ["Instant PDF Download", "Project-based Examples", "Hooks Cheatsheet Included", "React 18 Covered", "Best Practices Guide"]
    },

    // ─── DIGITAL TEMPLATES ────────────────────────────────────────────────────

    {
        // Image file: resume-templates-01.webp
        id: "resume-templates-01",
        title: "ATS-Friendly Resume Templates",
        category: "Digital Templates",
        price: 149,
        rating: 4.8,
        reviews: 275,
        stockStatus: "in-stock",
        featured: false,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1586281380117-5a60ae2050cc?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1586281380117-5a60ae2050cc?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1512314889357-e157c22f938d?w=800&auto=format&fit=crop&q=80"
        ],
        description: "A collection of 5 highly effective, ATS-friendly resume templates crafted specifically for software engineers and developers.",
        features: ["5 Template Designs", "Word & Google Docs Format", "ATS Score Optimized", "Cover Letter Template Included", "Instant Download"]
    },
    {
        // Image file: portfolio-templates-01.webp
        id: "portfolio-templates-01",
        title: "Modern Developer Portfolio Kit",
        category: "Digital Templates",
        price: 499,
        rating: 4.9,
        reviews: 190,
        stockStatus: "in-stock",
        featured: true,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1512314889357-e157c22f938d?w=800&auto=format&fit=crop&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1512314889357-e157c22f938d?w=800&auto=format&fit=crop&q=80",
            "https://images.unsplash.com/photo-1586281380117-5a60ae2050cc?w=800&auto=format&fit=crop&q=80"
        ],
        description: "A premium, fully responsive developer portfolio template built with Next.js and Tailwind CSS. Dark mode, animations, and full customization.",
        features: ["Next.js 14 + Tailwind CSS", "Dark & Light Mode", "Framer Motion Animations", "SEO Optimized", "Lifetime Access & Updates"]
    }
];

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
