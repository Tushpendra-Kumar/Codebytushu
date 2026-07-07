// Mock Data for Blogs Module

const BLOG_CATEGORIES = [
    "All", "Java", "JavaScript", "React", "Node.js", 
    "HTML & CSS", "SQL", "DSA", "Interview Preparation", 
    "Career Guidance", "AI", "Web Development"
];

const BLOG_TAGS = [
    "Frontend", "Backend", "Algorithms", "Career", "React Hooks", "Tips & Tricks", "Database", "AI Tools"
];

const BLOG_POSTS = [
    {
        id: "blog-1",
        title: "Mastering Object-Oriented Programming in Java",
        category: "Java",
        tags: ["Backend", "Tips & Tricks"],
        author: "Tushpendra Kumar",
        date: "Oct 12, 2025",
        readTime: "8 Min Read",
        thumbnail: "https://images.unsplash.com/photo-1517694712202-14dd9538aa97?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80",
        shortDesc: "Understand the core concepts of OOPs in Java with real-world examples and practical code snippets. Perfect for beginners and interview prep.",
        content: `
            <p>Object-Oriented Programming (OOP) is a programming paradigm based on the concept of "objects", which can contain data and code: data in the form of fields (often known as attributes or properties), and code, in the form of procedures (often known as methods).</p>
            
            <h3>The 4 Pillars of OOP</h3>
            <ul>
                <li><strong>Encapsulation:</strong> Wrapping data and methods into a single unit (class).</li>
                <li><strong>Inheritance:</strong> Acquiring properties of one class into another.</li>
                <li><strong>Polymorphism:</strong> The ability to take many forms.</li>
                <li><strong>Abstraction:</strong> Hiding internal implementation and showing only functionality.</li>
            </ul>

            <h3>Example: Encapsulation in Java</h3>
            <pre><code>public class Student {
    private String name;

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }
}</code></pre>

            <blockquote>"Mastering OOP is the first step to building scalable and maintainable enterprise applications." - Tushpendra</blockquote>

            <p>Make sure you practice these concepts daily. In upcoming articles, we will dive deep into advanced Java multithreading and performance optimization.</p>
        `
    },
    {
        id: "blog-2",
        title: "Top 10 React Hooks Every Developer Should Know",
        category: "React",
        tags: ["Frontend", "React Hooks"],
        author: "Tushpendra Kumar",
        date: "Nov 05, 2025",
        readTime: "6 Min Read",
        thumbnail: "https://images.unsplash.com/photo-1633356122544-f134324a6cee?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80",
        shortDesc: "React hooks completely transformed how we write components. Discover the most essential hooks and how to use them effectively.",
        content: `
            <p>Hooks were introduced in React 16.8. They let you use state and other React features without writing a class.</p>
            
            <h3>1. useState</h3>
            <p>The most basic and essential hook for managing state in a functional component.</p>
            <pre><code>const [count, setCount] = useState(0);</code></pre>

            <h3>2. useEffect</h3>
            <p>Used for side effects like fetching data, manually changing the DOM, or setting up subscriptions.</p>

            <h3>3. useContext</h3>
            <p>Lets you subscribe to React context without introducing nesting.</p>
            
            <p>We'll also look at <code>useReducer</code>, <code>useCallback</code>, and <code>useMemo</code> in later sections. Understanding these hooks is crucial for building performant React applications.</p>
        `
    },
    {
        id: "blog-3",
        title: "Cracking the Coding Interview: Graph Algorithms",
        category: "DSA",
        tags: ["Algorithms", "Interview Preparation"],
        author: "Tushpendra Kumar",
        date: "Nov 18, 2025",
        readTime: "12 Min Read",
        thumbnail: "https://images.unsplash.com/photo-1516116216624-53e697fedbea?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80",
        shortDesc: "Graphs can be intimidating, but they are frequently asked in FAANG interviews. Learn BFS, DFS, and shortest path algorithms.",
        content: `
            <p>Graph theory is arguably the most important topic for advanced coding interviews.</p>
            
            <h3>Breadth-First Search (BFS)</h3>
            <p>BFS explores the graph layer by layer. It is extremely useful for finding the shortest path in unweighted graphs.</p>
            
            <h3>Depth-First Search (DFS)</h3>
            <p>DFS explores as far as possible along each branch before backtracking. Great for cycle detection and topological sorting.</p>
            
            <pre><code>// Pseudocode for DFS
function DFS(node):
    if node is null: return
    mark node as visited
    for each neighbor of node:
        if neighbor is not visited:
            DFS(neighbor)
            </code></pre>

            <p>Practice standard problems like Number of Islands, Course Schedule, and Word Ladder to get comfortable with graphs.</p>
        `
    },
    {
        id: "blog-4",
        title: "Building RESTful APIs with Node.js and Express",
        category: "Node.js",
        tags: ["Backend", "Tips & Tricks"],
        author: "Tushpendra Kumar",
        date: "Dec 02, 2025",
        readTime: "10 Min Read",
        thumbnail: "https://images.unsplash.com/photo-1555099962-4199c345e5dd?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80",
        shortDesc: "A complete step-by-step guide to setting up a production-ready RESTful API using Node.js, Express, and MongoDB.",
        content: `
            <p>Node.js combined with Express is the most popular way to build web servers in JavaScript.</p>
            <h3>Setting up the Server</h3>
            <pre><code>const express = require('express');
const app = express();
app.use(express.json());

app.get('/api/users', (req, res) => {
    res.json({ message: 'List of users' });
});

app.listen(3000, () => console.log('Server running'));
</code></pre>
            <p>Always remember to handle errors properly and implement middleware for authentication and logging.</p>
        `
    },
    {
        id: "blog-5",
        title: "Getting Started with AI: How to use ChatGPT APIs",
        category: "AI",
        tags: ["AI Tools", "Tips & Tricks"],
        author: "Tushpendra Kumar",
        date: "Dec 15, 2025",
        readTime: "7 Min Read",
        thumbnail: "https://images.unsplash.com/photo-1677442136019-21780ecad995?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80",
        shortDesc: "Learn how to integrate OpenAI's GPT models into your own applications using Node.js and REST APIs.",
        content: `
            <p>AI is transforming the software industry. As a developer, knowing how to integrate LLMs into your apps is a superpower.</p>
            <p>In this guide, we walk through obtaining an OpenAI API key, setting up the Node.js SDK, and generating text responses programmatically.</p>
        `
    },
    {
        id: "blog-6",
        title: "Advanced SQL: Window Functions Explained",
        category: "SQL",
        tags: ["Database", "Backend"],
        author: "Tushpendra Kumar",
        date: "Jan 05, 2026",
        readTime: "9 Min Read",
        thumbnail: "https://images.unsplash.com/photo-1544383835-bda2bc66a55d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80",
        shortDesc: "Stop writing complex self-joins. Learn how SQL Window Functions can simplify your complex data analysis queries.",
        content: `
            <p>Window functions perform a calculation across a set of table rows that are somehow related to the current row.</p>
            <pre><code>SELECT employee_id, salary, 
       RANK() OVER(ORDER BY salary DESC) as rank 
FROM employees;</code></pre>
            <p>This is extremely useful for running totals, moving averages, and ranking.</p>
        `
    }
];

// Utility functions
function getAllBlogs() {
    return BLOG_POSTS;
}

function getBlogById(id) {
    return BLOG_POSTS.find(blog => blog.id === id);
}

function getRecentBlogs(count = 3) {
    return [...BLOG_POSTS].slice(0, count);
}
