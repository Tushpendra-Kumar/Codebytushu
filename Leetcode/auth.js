/**
 * ════════════════════════════════════════════════════════════════
 *  auth.js — CodeByTushu LeetCode Firebase Auth Engine
 *
 *  Provides:
 *    cbtAuthGuard()   → call on protected pages (blocks + shows modal)
 *    cbtNavAuth()     → call on public pages (shows login/logout in navbar)
 *
 *  Requires (already loaded via CDN + config script before this file):
 *    firebase-app-compat.js
 *    firebase-auth-compat.js
 *    /firebase-config.js  (gitignored, uploaded via FTP)
 * ════════════════════════════════════════════════════════════════
 */

(function () {
    'use strict';

    /* ══════════════════════════════════════════════
       GOOGLE LOGO SVG (inline, no external request)
       ══════════════════════════════════════════════ */
    var GOOGLE_SVG = '<svg class="google-logo" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/><path fill="none" d="M0 0h48v48H0z"/></svg>';

    /* ══════════════════════════════════════════════
       MODAL HTML TEMPLATE  —  Google Sign-In Only
       ══════════════════════════════════════════════ */
    function _buildModalHTML() {
        return '<div id="cbt-auth-overlay">' +
            '<div class="cbt-auth-modal">' +

                // Lock icon
                '<div class="cbt-lock-ring">' +
                    '<span class="material-symbols-rounded" style="font-size:34px;color:#ffc400;font-variation-settings:\'FILL\' 1,\'wght\' 400,\'GRAD\' 0,\'opsz\' 24">lock</span>' +
                '</div>' +

                // Heading
                '<h2>Unlock <span>Daily DSA</span> Solutions</h2>' +

                // Sub-copy
                '<p class="cbt-subtext">' +
                    'You\'ve found the vault — but it\'s locked.<br>' +
                    '<strong>Every daily LeetCode solution lives here</strong>, written clean, ' +
                    'explained clearly, and absolutely free.<br><br>' +
                    'Just sign in once and you\'re inside forever.<br>' +
                    'No paywalls. No subscriptions. Just code.' +
                '</p>' +

                // Google button
                '<button id="cbt-google-btn">' +
                    GOOGLE_SVG +
                    'Continue with Google' +
                '</button>' +

                // Error message slot
                '<p id="cbt-auth-msg"></p>' +

                // Footer
                '<div class="cbt-auth-footer">' +
                    'By signing in you agree to keep the solutions for personal learning.<br>' +
                    'CodeByTushu &mdash; Free DSA, Every Day.' +
                '</div>' +

            '</div>' +
        '</div>';
    }

    /* ══════════════════════════════════════════════
       SHOW MODAL
       ══════════════════════════════════════════════ */
    function _showModal() {
        // Inject modal into DOM (no blur — overlay blocks content)
        var wrapper = document.createElement('div');
        wrapper.innerHTML = _buildModalHTML();
        document.body.appendChild(wrapper.firstChild);

        // ── Google Sign-In ───────────────────────────────────────
        document.getElementById('cbt-google-btn').addEventListener('click', function () {
            var btn = this;
            btn.classList.add('loading');
            btn.innerHTML = GOOGLE_SVG + 'Signing in…';
            var provider = new firebase.auth.GoogleAuthProvider();
            firebase.auth().signInWithPopup(provider)
                .catch(function (err) {
                    btn.classList.remove('loading');
                    btn.innerHTML = GOOGLE_SVG + 'Continue with Google';
                    _showMsg('Sign-in failed. Please try again.', 'error');
                    console.error('Auth error:', err.code, err.message);
                });
        });
    }


    /* ══════════════════════════════════════════════
       HIDE MODAL (called on successful auth)
       ══════════════════════════════════════════════ */
    function _hideModal() {
        var overlay = document.getElementById('cbt-auth-overlay');
        if (overlay) {
            overlay.style.animation = 'cbt-fade-in 0.3s ease reverse';
            setTimeout(function () { overlay.remove(); }, 280);
        }
    }

    /* ══════════════════════════════════════════════
       SHOW MESSAGE IN MODAL
       ══════════════════════════════════════════════ */
    function _showMsg(text, type) {
        var el = document.getElementById('cbt-auth-msg');
        if (el) {
            el.textContent = text;
            el.className = type || '';
        }
    }

    /* ══════════════════════════════════════════════
       NAVBAR USER WIDGET (updates login/logout in nav)
       ══════════════════════════════════════════════ */
    function _renderNavUser(user) {
        var slot = document.getElementById('cbt-nav-auth-slot');
        if (!slot) return;

        if (user) {
            /* ── Logged-in state ── */
            var displayName = user.displayName || user.email || 'User';
            // Show only first name for clean display
            var firstName = displayName.split(' ')[0];

            var avatarHTML = user.photoURL
                ? '<img class="cbt-nav-avatar" src="' + user.photoURL + '" alt="' + firstName + '">'
                : '<div class="cbt-nav-avatar-placeholder">' + firstName[0].toUpperCase() + '</div>';

            slot.innerHTML =
                '<div class="cbt-nav-user" id="cbt-nav-user-widget">' +
                    '<button class="cbt-username-btn" id="cbt-user-menu-btn" aria-haspopup="true" aria-expanded="false">' +
                        avatarHTML +
                        '<span class="cbt-username-text">' + firstName + '</span>' +
                        '<span class="cbt-chevron">&#9660;</span>' +
                    '</button>' +
                    '<div class="cbt-dropdown" id="cbt-user-dropdown" role="menu">' +
                        '<a class="cbt-dropdown-item" href="https://myaccount.google.com/" target="_blank" rel="noopener" role="menuitem">' +
                            '<span>&#9998;</span> Edit Profile' +
                        '</a>' +
                        '<button class="cbt-dropdown-item cbt-dropdown-logout" id="cbt-logout-btn" role="menuitem">' +
                            '<span>&#10148;</span> Logout' +
                        '</button>' +
                    '</div>' +
                '</div>';

            /* Toggle dropdown on button click */
            var widget  = document.getElementById('cbt-nav-user-widget');
            var menuBtn = document.getElementById('cbt-user-menu-btn');
            var dropdown = document.getElementById('cbt-user-dropdown');

            menuBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var isOpen = widget.classList.toggle('open');
                menuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            /* Close dropdown when clicking anywhere outside */
            document.addEventListener('click', function _outside(e) {
                if (!widget.contains(e.target)) {
                    widget.classList.remove('open');
                    menuBtn.setAttribute('aria-expanded', 'false');
                }
            });

            /* Logout */
            document.getElementById('cbt-logout-btn').addEventListener('click', function () {
                firebase.auth().signOut();
                widget.classList.remove('open');
            });

        } else {
            /* ── Logged-out state ── */
            slot.innerHTML = '<button id="cbt-nav-login-btn" aria-label="Login to CodeByTushu">Login</button>';
            document.getElementById('cbt-nav-login-btn').addEventListener('click', function () {
                if (!document.getElementById('cbt-auth-overlay')) _showModal();
            });
        }
    }


    /* ══════════════════════════════════════════════
       PUBLIC API — AUTH GUARD
       Use on protected pages (problems, day lists, solutions)
       ══════════════════════════════════════════════ */
    window.cbtAuthGuard = function () {
        // Hide body immediately to prevent content flash
        document.body.style.visibility = 'hidden';

        // ── Safety net: if Firebase fails to init, don't leave page blank ──
        var fallback = setTimeout(function () {
            document.body.style.visibility = 'visible';
            if (!document.getElementById('cbt-auth-overlay')) _showModal();
        }, 3000);

        try {
            firebase.auth().onAuthStateChanged(function (user) {
                clearTimeout(fallback);
                document.body.style.visibility = 'visible';

                if (user) {
                    _hideModal();
                    _renderNavUser(user);
                } else {
                    _showModal();
                    _renderNavUser(null);
                }
            });
        } catch (e) {
            // Firebase not initialized (firebase-config.js missing / 404)
            clearTimeout(fallback);
            document.body.style.visibility = 'visible';
            console.error('[CBT Auth] Firebase not initialized. Check firebase-config.js is at /public_html/firebase-config.js on your server.', e);
            if (!document.getElementById('cbt-auth-overlay')) _showModal();
        }
    };

    /* ══════════════════════════════════════════════
       PUBLIC API — NAV AUTH (public pages only)
       Use on index.html (the public LeetCode landing page)
       ══════════════════════════════════════════════ */
    window.cbtNavAuth = function () {
        try {
            firebase.auth().onAuthStateChanged(function (user) {
                _renderNavUser(user);
            });
        } catch (e) {
            // Firebase not initialized — still show the Login button
            console.error('[CBT Auth] Firebase not initialized. Check firebase-config.js path.', e);
            _renderNavUser(null);
        }
    };

})();
