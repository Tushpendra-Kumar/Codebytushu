const storeData = [
    {
        id: "tshirt-dev-mode",
        title: "Developer Mode T-Shirt",
        category: "T-Shirts",
        price: 25,
        rating: 4.8,
        reviews: 120,
        stockStatus: "in-stock",
        featured: true,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80",
            "https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
        ],
        description: "Premium cotton t-shirt with 'Developer Mode: ON' minimal typography. Perfect for long coding sessions and everyday wear.",
        features: ["100% Premium Cotton", "Comfortable fit", "High-quality durable print", "Pre-shrunk fabric"]
    },
    {
        id: "hoodie-bug-hunter",
        title: "Bug Hunter Hoodie",
        category: "Hoodies",
        price: 45,
        rating: 4.9,
        reviews: 340,
        stockStatus: "in-stock",
        featured: true,
        newArrival: true,
        image: "https://images.unsplash.com/photo-1556821840-3a63f95609a7?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1556821840-3a63f95609a7?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
        ],
        description: "Stay warm while squashing bugs. This fleece-lined hoodie features a sleek bug icon on the chest and a cozy oversized fit.",
        features: ["Ultra-soft fleece lining", "Adjustable drawstring hood", "Kangaroo pocket", "Ribbed cuffs and hem"]
    },
    {
        id: "mug-coffee-code",
        title: "Coffee to Code Mug",
        category: "Mugs",
        price: 15,
        rating: 4.7,
        reviews: 85,
        stockStatus: "in-stock",
        featured: false,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
        ],
        description: "The essential tool for every developer. 11oz ceramic mug featuring the classic 'Coffee -> Code' conversion flow.",
        features: ["11oz capacity", "Microwave safe", "Dishwasher safe", "Glossy finish"]
    },
    {
        id: "stickers-dev-pack",
        title: "Developer Sticker Pack",
        category: "Stickers",
        price: 10,
        rating: 5.0,
        reviews: 420,
        stockStatus: "in-stock",
        featured: false,
        newArrival: true,
        image: "https://images.unsplash.com/photo-1572375992501-4b0892d50c69?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1572375992501-4b0892d50c69?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
        ],
        description: "Deck out your laptop with 15 high-quality, die-cut vinyl stickers featuring popular tech stacks, memes, and CodeByTushu branding.",
        features: ["Pack of 15 unique stickers", "Waterproof vinyl", "Residue-free removal", "Matte finish"]
    },
    {
        id: "template-portfolio",
        title: "Premium Portfolio Template",
        category: "Digital Templates",
        price: 39,
        rating: 4.9,
        reviews: 210,
        stockStatus: "in-stock",
        featured: true,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1481481600465-9856f6cebd4d?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1481481600465-9856f6cebd4d?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
        ],
        description: "A fully responsive, highly customizable React & Next.js portfolio template with a premium dark theme. Includes project showcases, blog, and contact forms.",
        features: ["Built with React & Next.js", "Tailwind CSS styling", "Dark & Light mode support", "SEO optimized", "Lifetime updates"]
    },
    {
        id: "presets-cinematic",
        title: "Cinematic Coding Presets",
        category: "Presets",
        price: 19,
        rating: 4.6,
        reviews: 95,
        stockStatus: "in-stock",
        featured: false,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1542038784456-1ea8e935640e?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1542038784456-1ea8e935640e?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
        ],
        description: "10 premium Lightroom & Premiere Pro presets specifically designed to make your coding setup, desk space, and vlogs look cinematic and moody.",
        features: ["10 .xmp Lightroom Presets", "10 .cube Premiere Pro LUTs", "Works on Desktop & Mobile", "Instant Download"]
    },
    {
        id: "ebook-dsa",
        title: "The DSA Handbook",
        category: "E-books",
        price: 29,
        rating: 5.0,
        reviews: 580,
        stockStatus: "in-stock",
        featured: true,
        newArrival: true,
        image: "https://images.unsplash.com/photo-1544947950-fa07a98d237f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1544947950-fa07a98d237f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
        ],
        description: "A comprehensive 300-page e-book covering all Data Structures and Algorithms patterns required for FAANG interviews, with visual diagrams and Java/C++ code.",
        features: ["300 pages of deep-dive content", "Visual diagrams for complex algorithms", "Code in Java & C++", "Instant PDF & EPUB download"]
    },
    {
        id: "tshirt-404",
        title: "404 Sleep Not Found",
        category: "T-Shirts",
        price: 25,
        rating: 4.5,
        reviews: 45,
        stockStatus: "out-of-stock",
        featured: false,
        newArrival: false,
        image: "https://images.unsplash.com/photo-1503342394128-c104d54dba01?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80",
        gallery: [
            "https://images.unsplash.com/photo-1503342394128-c104d54dba01?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
        ],
        description: "For the late-night coders. Classic fit t-shirt with a humorous 404 error code design.",
        features: ["Breathable cotton blend", "Classic fit", "Tagless neck label"]
    }
];

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
