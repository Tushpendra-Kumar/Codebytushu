// ─────────────────────────────────────────────────────────────────────────────
//  firebase-config.js
//  ⚠️  THIS FILE IS GITIGNORED — DO NOT COMMIT TO GITHUB
//  Upload this file manually to your Hostinger server via FTP
//  Path on server: /public_html/firebase-config.js  (root of your domain)
// ─────────────────────────────────────────────────────────────────────────────
//
//  PHP MIGRATION NOTE:
//  This file is the ONLY server-sensitive JS file in the project.
//  During PHP + MySQL migration, it will be fully replaced by:
//    1. A PHP session system (session_start() + $_SESSION['user'])
//    2. A `users` MySQL table (id, google_uid, email, name, photo_url, created_at)
//    3. A PHP OAuth handler (e.g., using Google OAuth via league/oauth2-google)
//       OR a simpler email+password system using PHP password_hash()
//  The Firebase SDK CDN scripts and this config file will be removed entirely.
//  auth.js (Leetcode/auth.js) will be replaced by PHP session checks at
//  the top of each protected .php page: if (!isset($_SESSION['user'])) redirect.
// ─────────────────────────────────────────────────────────────────────────────

// Firebase compat SDK is loaded via CDN before this file.
// No imports needed — just initialize directly.

firebase.initializeApp({
  apiKey:            "AIzaSyDNJ838xYCbJG_PEhh1Cl-kPL5L-bFCW4o",
  authDomain:        "codebytushu-834dd.firebaseapp.com",
  projectId:         "codebytushu-834dd",
  storageBucket:     "codebytushu-834dd.firebasestorage.app",
  messagingSenderId: "590490302567",
  appId:             "1:590490302567:web:ad5803cd3c1870f43ebcc2",
  measurementId:     "G-0XCZXL9C4T"
});

// Analytics (optional — silently fails in localhost/blocked environments)
try { firebase.analytics(); } catch (_) {}

