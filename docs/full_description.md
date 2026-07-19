# CodeByTushu - Full Codebase Description

This document provides a comprehensive overview of the CodeByTushu platform. It is designed to act as a primer for future AI models or developers joining the project.

## 1. Platform Overview
CodeByTushu is a premium educational platform offering programming courses, LeetCode solutions, video editing assets, and developer blogs. The platform emphasizes a modern, dark-themed, glassmorphism UI with a robust and secure backend.

## 2. Tech Stack
- **Frontend**: HTML5, Vanilla CSS3 (custom CSS architecture, no Tailwind), Vanilla JavaScript.
- **Backend**: Core PHP 8+ (No heavy frameworks like Laravel, purely custom MVC-inspired routing).
- **Database**: MySQL (accessed via PHP PDO with strict prepared statements).
- **Authentication**: Native PHP Sessions + Google OAuth 2.0 integration.

## 3. Directory Structure
Understanding where files live is critical for navigating the codebase:

- `/` (Root): Public HTML/PHP entry points (`index.php`, `about-platform.php`).
- `/api/`: Backend API endpoints (e.g., `/api/auth/login.php`, `/api/auth/google.php`). These scripts only return JSON or perform redirects.
- `/classes/`: Core PHP logic and objects (e.g., `Auth.php` for session management).
- `/config/`: Application and database configuration (`app.php`, `database.php`).
- `/database/`: SQL schemas and migration scripts.
- `/includes/`: Shared UI components (e.g., `navbar.php`, `footer.php`).
- `/Leetcode/`: Dedicated sub-application for DSA and daily LeetCode solutions.
- `/courses/`: Course listing and video player pages.
- `/blogs.php`: The blog listing system.

## 4. Design Aesthetics
The platform uses a strict design system defined globally in `styles.css`:
- **Color Palette**: Dark background (`#0a0a0a`), slightly lighter cards (`#111111`), with vibrant gold/yellow accents (`#ffc400`).
- **Typography**: Inter/Poppins font families.
- **Interactions**: Smooth hover states, micro-animations, and glassmorphism (translucent backgrounds with blur).

## 5. Development Principles
1. **No Client-Side Secrets**: All sensitive logic (API keys, DB credentials) must reside in `.env` and be executed on the server via PHP.
2. **Server-Side Authentication**: The UI depends on `Auth::user()` server-side to render elements (like the Avatar vs Login button), eliminating client-side UI flashing.
3. **Vanilla Mastery**: We avoid heavy frontend frameworks (React/Vue) or CSS frameworks (Tailwind) unless explicitly requested, preferring optimized Vanilla code.
