// ─────────────────────────────────────────────────────────────────────────────
//  firebase-config.example.js  ← SAFE TO COMMIT TO GITHUB
//  This is just a template. Copy it, rename to firebase-config.js,
//  fill in your real values from Firebase Console, and upload via FTP.
// ─────────────────────────────────────────────────────────────────────────────

firebase.initializeApp({
  apiKey:            "YOUR_API_KEY",
  authDomain:        "YOUR_PROJECT.firebaseapp.com",
  projectId:         "YOUR_PROJECT_ID",
  storageBucket:     "YOUR_PROJECT.firebasestorage.app",
  messagingSenderId: "YOUR_SENDER_ID",
  appId:             "YOUR_APP_ID",
  measurementId:     "YOUR_MEASUREMENT_ID"   // optional
});

try { firebase.analytics(); } catch (_) {}
