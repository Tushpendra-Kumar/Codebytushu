const coursesData = [
    {
        id: "java-masterclass",
        title: "Java Masterclass for Beginners",
        category: "Java",
        difficulty: "Beginner",
        duration: "20 Hours",
        lessons: 120,
        instructor: "Tushpendra Kumar",
        rating: 4.8,
        students: 1540,
        price: 0,
        featured: true,
        image: "https://images.unsplash.com/photo-1517694712202-14dd9538aa97?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80",
        description: "Learn Java from scratch. Covers OOP, collections, multithreading, and builds a real-world project.",
        overview: "This course is designed for absolute beginners who want to master Java. We start with the basics of programming and move all the way to advanced concepts like multithreading and the Collections Framework. By the end of this course, you will be able to build robust Java applications.",
        curriculum: [
            { title: "Module 1: Java Basics", details: "Variables, Data Types, Operators, Control Flow" },
            { title: "Module 2: Object-Oriented Programming", details: "Classes, Objects, Inheritance, Polymorphism" },
            { title: "Module 3: Advanced Topics", details: "Collections, Generics, Multithreading, File I/O" }
        ],
        requirements: ["No prior programming experience needed.", "A computer with internet access."]
    },
    {
        id: "react-front-to-back",
        title: "React Front to Back",
        category: "React",
        difficulty: "Intermediate",
        duration: "15 Hours",
        lessons: 95,
        instructor: "Tushpendra Kumar",
        rating: 4.9,
        students: 3200,
        price: 49,
        featured: true,
        image: "https://images.unsplash.com/photo-1633356122544-f134324a6cee?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80",
        description: "Master React by building real-world projects. Hooks, Context API, Redux, and Next.js introduction.",
        overview: "Take your frontend skills to the next level with React. We cover modern React using functional components and Hooks. You'll build multiple projects including a task tracker and a GitHub finder app.",
        curriculum: [
            { title: "Module 1: React Fundamentals", details: "Components, Props, State, JSX" },
            { title: "Module 2: Hooks & Context", details: "useState, useEffect, custom hooks, Context API" },
            { title: "Module 3: State Management", details: "Redux Toolkit, RTK Query" }
        ],
        requirements: ["Basic knowledge of HTML, CSS, and JavaScript.", "Understanding of ES6 features."]
    },
    {
        id: "dsa-interview-prep",
        title: "DSA & Interview Preparation",
        category: "DSA",
        difficulty: "Advanced",
        duration: "30 Hours",
        lessons: 200,
        instructor: "Tushpendra Kumar",
        rating: 5.0,
        students: 5400,
        price: 99,
        featured: true,
        image: "https://images.unsplash.com/photo-1504384308090-c894fdcc538d?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80",
        description: "Crack top tech interviews. Arrays, Trees, Graphs, Dynamic Programming, and System Design basics.",
        overview: "The ultimate guide to cracking technical interviews at FAANG and top product companies. We focus on problem-solving patterns rather than just memorizing solutions.",
        curriculum: [
            { title: "Module 1: Data Structures", details: "Arrays, Linked Lists, Stacks, Queues, Trees, Graphs" },
            { title: "Module 2: Algorithms", details: "Sorting, Searching, Recursion, Dynamic Programming" },
            { title: "Module 3: Interview Strategies", details: "Mock interviews, behavioral questions, resume tips" }
        ],
        requirements: ["Familiarity with at least one programming language (Java/C++/Python)."]
    },
    {
        id: "fullstack-web-dev",
        title: "Fullstack Web Development",
        category: "Web Development",
        difficulty: "Beginner",
        duration: "50 Hours",
        lessons: 350,
        instructor: "Tushpendra Kumar",
        rating: 4.8,
        students: 2100,
        price: 0,
        featured: false,
        image: "https://images.unsplash.com/photo-1498050108023-c5249f4df085?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80",
        description: "From zero to fullstack developer. HTML, CSS, JS, Node.js, Express, and MongoDB.",
        overview: "A comprehensive bootcamp covering everything you need to know to become a fullstack developer. Build and deploy a complete MERN stack application.",
        curriculum: [
            { title: "Module 1: Frontend Basics", details: "HTML5, CSS3, JavaScript, DOM Manipulation" },
            { title: "Module 2: Backend Basics", details: "Node.js, Express, REST APIs" },
            { title: "Module 3: Database & Deployment", details: "MongoDB, Mongoose, JWT Auth, Heroku/Vercel Deployment" }
        ],
        requirements: ["No prior experience required."]
    }
];

function getCourseById(id) {
    return coursesData.find(c => c.id === id);
}
