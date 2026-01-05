// Login-Signup-Form/index.js
const container = document.querySelector('.container');
const LoginLink = document.querySelector('.SignInLink');
const RegisterLink = document.querySelector('.SignUpLink');

// toggle animations (your existing)
RegisterLink.addEventListener('click', (e) => {
  e.preventDefault();
  container.classList.add('active');
});
LoginLink.addEventListener('click', (e) => {
  e.preventDefault();
  container.classList.remove('active');
});

// ---- Auth logic (localStorage prototype) ----
const LForm = document.querySelector('.form-box.Login form');
const RForm = document.querySelector('.form-box.Register form');

// Helper: get users object from localStorage
function getUsers() {
  try {
    return JSON.parse(localStorage.getItem('cb_users')) || {};
  } catch (e) {
    return {};
  }
}

// Helper: save users
function saveUsers(u) {
  localStorage.setItem('cb_users', JSON.stringify(u));
}

// Helper: set current session
function setSession(email) {
  // For prototype, simple session token
  localStorage.setItem('cb_session', JSON.stringify({
    email,
    token: btoa(email + ':' + Date.now()) // not secure, just for demo
  }));
}

// Helper: clear session
function clearSession() {
  localStorage.removeItem('cb_session');
}

// Register form submit
if (RForm) {
  RForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const username = RForm.querySelector('input[type="text"]').value.trim();
    const email = RForm.querySelector('input[type="email"]').value.trim().toLowerCase();
    const password = RForm.querySelector('input[type="password"]').value;

    if (!username || !email || !password) {
      alert('Please fill all fields');
      return;
    }

    const users = getUsers();
    if (users[email]) {
      alert('Account already exists. Please login.');
      return;
    }

    // WARNING: storing cleartext password — only for prototype!
    users[email] = { username, password };
    saveUsers(users);

    setSession(email);
    alert('Registered & logged in!');
    window.location.href = '/index.html'; // redirect to main site
  });
}

// Login form submit
if (LForm) {
  LForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const usernameOrEmail = LForm.querySelector('input[type="text"]').value.trim();
    const password = LForm.querySelector('input[type="password"]').value;

    if (!usernameOrEmail || !password) {
      alert('Please enter credentials');
      return;
    }

    const users = getUsers();

    // try match by email first then username
    let foundEmail = null;
    if (users[usernameOrEmail?.toLowerCase()]) foundEmail = usernameOrEmail.toLowerCase();
    else {
      // search by username
      for (const em of Object.keys(users)) {
        if (users[em].username.toLowerCase() === usernameOrEmail.toLowerCase()) {
          foundEmail = em;
          break;
        }
      }
    }

    if (!foundEmail || users[foundEmail].password !== password) {
      alert('Invalid credentials. If you do not have account, please Sign Up first');
      return;
    }

    setSession(foundEmail);
    alert('Login successful!');
    window.location.href = '/index.html';
  });
}

// Optional: auto-redirect if already logged-in
(function checkAlreadyLogged() {
  const session = JSON.parse(localStorage.getItem('cb_session') || 'null');
  if (session && window.location.pathname.includes('/Login-Signup-Form/')) {
    // if user is at login page but already logged in, go to site root
    window.location.href = '/index.html';
  }
})();

// inside Login-Signup-Form/index.js (forgot link handler)
const forgotLink = document.getElementById('forgotPwdLink');
if (forgotLink) {
  forgotLink.addEventListener('click', (e) => {
    e.preventDefault();
    let input = prompt('Enter your registered email OR username to receive reset code:');
    if (!input) return;
    input = input.trim();
    const users = getUsers();

    let emailFound = null;
    if (users[input.toLowerCase()]) emailFound = input.toLowerCase();
    else {
      for (const em of Object.keys(users)) {
        if (users[em].username.toLowerCase() === input.toLowerCase()) { emailFound = em; break; }
      }
    }

    if (!emailFound) {
      alert('No account found with that email/username.');
      return;
    }

    const code = Math.floor(100000 + Math.random() * 900000).toString(); // 6-digit
    const expiresInMs = 15 * 60 * 1000; // 15 minutes
    const tokenKey = 'cb_reset_' + emailFound;
    const tokenData = { code, created: Date.now(), expires: Date.now() + expiresInMs };
    localStorage.setItem(tokenKey, JSON.stringify(tokenData));

    // PROTOTYPE: show code (simulate email)
    alert('Reset code generated (prototype): ' + code + '\nOpen Reset Password page and enter it.');

    window.location.href = '/Login-Signup-Form/reset.html';
  });
}

