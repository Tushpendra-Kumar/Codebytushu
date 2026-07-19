(function() {
    // 1. Inject Avatar CSS
    var css = `
        .cbt-user-dropdown { position: relative; }
        .cbt-user-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid #ffc400; }
        .cbt-user-initials { width: 36px; height: 36px; border-radius: 50%; background: #ffc400; color: #111; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; }
        .cbt-user-menu { position: absolute; top: 120%; right: 0; background: #1a1a1a; border: 1px solid #333; border-radius: 8px; width: 220px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); opacity: 0; visibility: hidden; transform: translateY(10px); transition: all 0.2s ease; z-index: 9999; }
        .cbt-user-menu.open { opacity: 1; visibility: visible; transform: translateY(0); }
        .cbt-user-menu-header { padding: 16px; border-bottom: 1px solid #333; }
        .cbt-user-menu-name { font-size: 14px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px; }
        .cbt-user-menu-email { font-size: 11px; color: #888; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px; }
        .cbt-user-menu-item { display: flex; align-items: center; gap: 8px; padding: 12px 16px; color: #ccc; font-size: 14px; text-decoration: none; transition: background 0.15s, color 0.15s; }
        .cbt-user-menu-item:hover { background: rgba(255,196,0,0.08); color: #ffc400; }
        .cbt-user-menu-item.logout { color: #ff6b6b; }
        .cbt-user-menu-item.logout:hover { background: rgba(255,77,77,0.1); color: #ff4d4d; }
    `;
    var style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);

    // 2. Fetch auth status from API
    fetch('/api/auth/status.php', { credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.logged_in) return; // Keep static Login button

            var user = data.user;
            var name = user.full_name || user.username || 'User';
            var email = user.email || '';
            var photo = user.profile_image || '';
            var initials = name.split(' ').map(function(w){return w[0];}).join('').toUpperCase().slice(0,2);

            // Build avatar element
            var avatarHtml = photo
                ? '<img src="' + photo + '" class="cbt-user-avatar" alt="' + name + '" id="cbt-user-avatar-img" onerror="this.style.display=\'none\';document.getElementById(\'cbt-user-init\').style.display=\'inline-flex\';"><span class="cbt-user-initials" id="cbt-user-init" style="display:none;">' + initials + '</span>'
                : '<span class="cbt-user-initials" id="cbt-user-init">' + initials + '</span>';

            var dropdownHtml = '<div class="cbt-user-dropdown" id="cbt-user-dropdown">'
                + '<div id="cbt-avatar-trigger" style="cursor:pointer;">' + avatarHtml + '</div>'
                + '<div class="cbt-user-menu" id="cbt-user-menu">'
                + '<div class="cbt-user-menu-header">'
                + '<div class="cbt-user-menu-name">' + name + '</div>'
                + '<div class="cbt-user-menu-email">' + email + '</div>'
                + '</div>'
                + '<a href="/user/dashboard.php" class="cbt-user-menu-item">'
                + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.58-7 8-7s8 3 8 7"/></svg>My Dashboard</a>'
                + '<a href="/api/auth/logout.php" class="cbt-user-menu-item logout">'
                + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout</a>'
                + '</div></div>';

            // Replace desktop auth area
            var desktopArea = document.getElementById('cbt-auth-area');
            if (desktopArea) desktopArea.innerHTML = dropdownHtml;

            // Replace mobile auth area with just the initials
            var mobArea = document.getElementById('cbt-auth-area-mob');
            if (mobArea) {
                var mobAvatarHtml = photo
                    ? '<img src="' + photo + '" class="cbt-user-avatar" style="width:32px;height:32px;" alt="' + name + '" onclick="window.location.href=\'/user/dashboard.php\'">'
                    : '<span class="cbt-user-initials" style="width:32px;height:32px;font-size:12px;" onclick="window.location.href=\'/user/dashboard.php\'">' + initials + '</span>';
                mobArea.innerHTML = mobAvatarHtml;
            }

            // Update drawer login link (if drawer exists)
            var drawerLi = document.getElementById('drawer-auth-li');
            if (drawerLi) {
                drawerLi.outerHTML = '<li role="none"><a href="/user/dashboard.php" class="cbt-drawer-link" role="menuitem" style="color:#ffc400;">My Dashboard</a></li>'
                                   + '<li role="none"><a href="/api/auth/logout.php" class="cbt-drawer-link" id="drawer-logout" role="menuitem" style="color:#ff6b6b;">Logout</a></li>';
            }

            // Toggle dropdown on avatar click
            document.addEventListener('click', function(e) {
                var trigger = document.getElementById('cbt-avatar-trigger');
                var menu = document.getElementById('cbt-user-menu');
                if (!trigger || !menu) return;
                if (trigger.contains(e.target)) {
                    menu.classList.toggle('open');
                } else {
                    menu.classList.remove('open');
                }
            });
        })
        .catch(function() { /* Network error — keep Login button */ });
})();
