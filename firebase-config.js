// ─────────────────────────────────────────────────────────────────────────────
//  firebase-config.js
//  ⚠️  THIS FILE IS GITIGNORED — DO NOT COMMIT TO GITHUB
//  Upload this file manually to your Hostinger server via FTP
//  Path on server: /public_html/firebase-config.js  (root of your domain)
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
