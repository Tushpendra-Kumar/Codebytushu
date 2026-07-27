# 🌐 CodeByTushu

**Live Website:** [https://codebytushu.com](https://codebytushu.com)  
**Author:** Tushpendra Kumar  
**Email:** [tushpendrakumar@gmail.com](mailto:tushpendrakumar@gmail.com)

---

## 🧠 About

**CodeByTushu** is a premium educational platform built for developers. It offers:

- 🎓 **Programming Courses** — Premium & free courses with PDF downloads
- 💻 **LeetCode Unlocked** — Daily DSA solutions with explanations
- 🎬 **Video Editing Resources** — Templates, assets, and tools for editors
- 📝 **Developer Blogs** — In-depth technical articles
- 🛍️ **Digital Store** — Development resources and assets

---

## 🏗️ Tech Stack

| Layer | Technology |
|---|---|
| **Frontend** | HTML5, Vanilla CSS3, Vanilla JavaScript |
| **Backend** | PHP 8+ (Custom MVC-inspired — no Laravel/Symfony) |
| **Database** | MySQL (PDO with strict prepared statements) |
| **Authentication** | Native PHP Sessions + Google OAuth 2.0 |
| **Email** | PHPMailer (SMTP via Gmail/custom) |
| **Hosting** | Hostinger (Shared Hosting / VPS) |
| **Environment** | vlucas/phpdotenv (Composer) |

---

## ✨ Key Features

- 🔐 Full authentication system — Google OAuth, Email/Password, Email Verification, Forgot Password, Remember Me
- 🖥️ Comprehensive Admin Panel — Full CMS for Blogs, Courses, LeetCode, Users, Analytics, Settings
- 📦 Course Purchase Flow — Cart, Checkout, Order Management, Secure PDF Download
- 📊 Analytics Dashboard — Visitor tracking, device types, referrers, top pages
- 🛡️ Security — CSRF protection, rate limiting, PDO prepared statements, secure file uploads
- 🌙 Premium Dark UI — Glassmorphism design with `#ffc400` gold accent

---

## 🚀 Local Setup

> ⚠️ This project requires **PHP 8+** and **MySQL**. It cannot run as a static site.

### Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.4+
- Composer
- A local server (XAMPP, Laragon, MAMP, or Herd)

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/Tushpendra-Kumar/Codebytushu.git
   cd Codebytushu
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your DB credentials, Google OAuth keys, SMTP settings
   ```

4. **Set up the database**
   ```bash
   # Import the main schema
   mysql -u root -p your_db_name < database/schema.sql
   
   # Run all migrations
   php database/run_migrations.php
   ```

5. **Point your web server** to the project root and visit `http://localhost`

---

## 📁 Project Structure (Key Directories)

```
/
├── index.html           # Public landing page
├── config/              # app.php (constants), database.php (PDO singleton)
├── classes/             # Auth.php, Mailer.php, Upload.php
├── includes/            # navbar.php, functions.php, analytics.php
├── api/                 # All AJAX/JSON backend endpoints
├── auth/                # Login, Signup, Forgot/Reset Password pages
├── admin/               # Admin Panel (CMS Dashboard)
├── user/                # User Dashboard, Purchases, Profile
├── blogs/               # Blog module
├── courses/             # Courses module
├── Leetcode/            # LeetCode module
├── video-editing/       # Video Editing module
├── store/               # Digital Store module
├── database/            # schema.sql + migrations/
├── docs/                # Developer & AI documentation
└── private/             # Server-only files (PDFs, logs) — never public
```

---

## 📚 Documentation

All detailed documentation is in the `/docs/` directory:

| File | Contents |
|---|---|
| `docs/README_AI.md` | AI/Developer onboarding guide + rules |
| `docs/full_description.md` | Complete codebase overview |
| `docs/connection.md` | Architecture, request lifecycle, API map |
| `docs/project_flow.md` | Auth flows, purchase flow, data flows |
| `docs/schemas.md` | Database schema documentation |
| `docs/api_documentation.md` | API endpoint reference |
| `docs/security.md` | Security architecture & audit |

---

## 📜 License

This project is proprietary. All rights reserved by Tushpendra Kumar.

---

## 👨‍💻 Author

**Tushpendra Kumar**  
📧 [tushpendrakumar@gmail.com](mailto:tushpendrakumar@gmail.com)  
🌍 [https://codebytushu.com](https://codebytushu.com)

> *"Code isn't just logic — it's creativity in syntax."*
