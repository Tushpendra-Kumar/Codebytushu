/**
 * cbt-auth.js — CodeByTushu PHP Session Auth Frontend Engine
 * Replaces the old Firebase auth.js
 *
 * Auto-detects the base path so it works on:
 *   • localhost/Codebytushu/  (development)
 *   • codebytushu.com/        (production)
 */

(function () {
    'use strict';

    // ─── Dynamic base path detection ────────────────────────────────────────
    // On localhost the project lives inside /Codebytushu/.
    // On the live server it lives at the root /.
    // We detect the "site root" by finding the segment before /Leetcode/ or /auth/ etc.
    var _base = (function () {
        var h = window.location.hostname;
        var p = window.location.pathname;

        // Check if the project is being served from the /Codebytushu/ folder (e.g., XAMPP)
        if (p.indexOf('/Codebytushu/') === 0 || p === '/Codebytushu') {
            return '/Codebytushu';
        }
        return ''; // production: site is at root
    })();

    // Helper – build a fully-correct absolute URL
    function _url(path) {
        // path should start with '/'
        return _base + path;
    }

    // Build the login modal HTML
    function _buildModalHTML() {
        var next = encodeURIComponent(window.location.pathname + window.location.search);
        return '<div id="cbt-auth-overlay">' +
            '<div class="cbt-auth-modal">' +
                '<div class="cbt-lock-ring">' +
                    '<span class="material-symbols-rounded" style="font-size:34px;color:#ffc400;">lock</span>' +
                '</div>' +
                '<h2>Unlock <span>Daily DSA</span> Solutions</h2>' +
                '<p class="cbt-subtext">' +
                    'You\'ve found the vault — but it\'s locked.<br>' +
                    '<strong>Every daily LeetCode solution lives here</strong>, written clean, ' +
                    'explained clearly, and absolutely free.<br><br>' +
                    'Just sign in once and you\'re inside forever.<br>' +
                '</p>' +
                '<a href="' + _url('/auth/login.php') + '?next=' + next + '" id="cbt-google-btn" style="text-decoration:none;display:block;text-align:center;">' +
                    'Sign In to Continue' +
                '</a>' +
                '<div class="cbt-auth-footer">' +
                    'CodeByTushu &mdash; Free DSA, Every Day.' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    function _showModal() {
        var wrapper = document.createElement('div');
        wrapper.innerHTML = _buildModalHTML();
        document.body.appendChild(wrapper.firstChild);
    }

    function _hideModal() {
        var overlay = document.getElementById('cbt-auth-overlay');
        if (overlay) {
            overlay.style.animation = 'cbt-fade-in 0.3s ease reverse';
            setTimeout(function () { overlay.remove(); }, 280);
        }
    }

    function _renderNavUser(user) {
        var slot = document.getElementById('cbt-nav-auth-slot');
        if (!slot) return;

        if (user && user.logged_in) {
            var firstName = user.user.full_name.split(' ')[0];
            var avatarHTML = user.user.photo_url
                ? '<img class="cbt-nav-avatar" src="' + user.user.photo_url + '" alt="' + firstName + '">'
                : '<div class="cbt-nav-avatar-placeholder">' + firstName[0].toUpperCase() + '</div>';

            // If user is admin, show Admin Panel link in dropdown
            var adminLink = (user.user.role === 'admin' || user.user.role === 'super_admin')
                ? '<a class="cbt-dropdown-item" href="' + _url('/admin/') + '" role="menuitem"><span>⚙️</span> Admin Panel</a>'
                : '';

            slot.innerHTML =
                '<div class="cbt-nav-user" id="cbt-nav-user-widget">' +
                    '<button class="cbt-username-btn" id="cbt-user-menu-btn" aria-haspopup="true" aria-expanded="false">' +
                        avatarHTML +
                        '<span class="cbt-username-text">' + firstName + '</span>' +
                        '<span class="cbt-chevron">&#9660;</span>' +
                    '</button>' +
                    '<div class="cbt-dropdown" id="cbt-user-dropdown" role="menu">' +
                        adminLink +
                        '<a class="cbt-dropdown-item" href="' + _url('/user/profile.php') + '" role="menuitem">' +
                            '<span>&#9998;</span> Edit Profile' +
                        '</a>' +
                        '<a class="cbt-dropdown-item cbt-dropdown-logout" href="' + _url('/auth/logout.php') + '" role="menuitem">' +
                            '<span>&#10148;</span> Logout' +
                        '</a>' +
                    '</div>' +
                '</div>';

            var widget  = document.getElementById('cbt-nav-user-widget');
            var menuBtn = document.getElementById('cbt-user-menu-btn');

            menuBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var isOpen = widget.classList.toggle('open');
                menuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            document.addEventListener('click', function _outside(e) {
                if (!widget.contains(e.target)) {
                    widget.classList.remove('open');
                    menuBtn.setAttribute('aria-expanded', 'false');
                }
            });

        } else {
            var next = encodeURIComponent(window.location.pathname);
            slot.innerHTML = '<a href="' + _url('/auth/login.php') + '?next=' + next + '"><button id="cbt-nav-login-btn" aria-label="Login to CodeByTushu">Login</button></a>';
        }
    }

    // Fetches auth status from API — uses dynamic base URL
    function fetchAuthStatus(callback) {
        fetch(_url('/api/auth/status.php'))
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if(data && data.success) {
                    callback(data.data);
                } else {
                    callback(null);
                }
            })
            .catch(function(err) {
                console.error("Auth status fetch error:", err);
                callback(null);
            });
    }

    // Used on protected pages (like problem details)
    window.cbtAuthGuard = function () {
        document.body.style.visibility = 'hidden';

        fetchAuthStatus(function(authData) {
            document.body.style.visibility = 'visible';
            if (authData && authData.logged_in) {
                _hideModal();
                _renderNavUser(authData);
            } else {
                _showModal();
                _renderNavUser(authData);
            }
        });
    };

    // Used on public pages (like homepage)
    window.cbtNavAuth = function () {
        fetchAuthStatus(function(authData) {
            _renderNavUser(authData);
        });
    };

})();
