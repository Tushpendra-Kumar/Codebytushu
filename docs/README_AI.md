# AI Context & Developer Onboarding Guide

Welcome! If you are an AI model or a new developer joining the CodeByTushu project, this document is your starting point. It provides the essential context needed to understand the codebase without risking sensitive data exposure.

## Project Context
CodeByTushu is a platform built for developers, offering premium courses, LeetCode daily solutions, video editing assets, and technical blogs. The site recently migrated from static HTML/JavaScript with Firebase to a robust **Server-Side PHP + MySQL** architecture.

## How to Navigate the Docs
Before suggesting architectural changes or modifying core files, please review the following files in the `/docs/` directory:

1. **`connection.md`**: Understand how the frontend views connect to backend APIs.
2. **`schemas.md`**: Review the MySQL database schema before writing queries.
3. **`project_flow.md`**: Understand the Authentication and User Journey flows.
4. **`api_documentation.md`**: Review the standards for writing JSON API endpoints.
5. **`security.md`**: Read the strict security requirements that must be followed.

## AI Assistant Rules
As an AI modifying this codebase, you MUST adhere to the following rules:

1. **No Data Leaks**: Never print, expose, or write API keys, `.env` variables, or database passwords in any generated code, console output, or documentation.
2. **Design Language**: If you create a new UI component, it must match the global aesthetic defined in `styles.css` (Dark theme, `#0a0a0a` background, `#ffc400` accent, glassmorphism). Do NOT introduce Tailwind CSS or external component libraries.
3. **Server-Side Auth**: Always rely on `Auth::user()` and PHP Sessions for determining login state. Do NOT use client-side JavaScript fetching for critical auth checks.
4. **Security First**: All database interactions must use PDO prepared statements. All POST forms must include CSRF tokens.

By following these guidelines, you will help maintain a secure, fast, and beautiful platform for CodeByTushu users.
