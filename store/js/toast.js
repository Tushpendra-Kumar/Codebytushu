/**
 * CodeByTushu Store — Premium Toast Notification System
 * Replaces all alert() calls with beautiful, non-blocking toasts
 * Usage: showToast('message', 'success'|'error'|'info'|'warning')
 */

(function() {
    // Inject toast styles once
    if (!document.getElementById('cbt-toast-styles')) {
        const style = document.createElement('style');
        style.id = 'cbt-toast-styles';
        style.textContent = `
            #cbt-toast-container {
                position: fixed;
                bottom: 30px;
                right: 30px;
                z-index: 999999;
                display: flex;
                flex-direction: column;
                gap: 12px;
                pointer-events: none;
            }
            .cbt-toast {
                display: flex;
                align-items: center;
                gap: 12px;
                min-width: 280px;
                max-width: 380px;
                padding: 14px 18px;
                border-radius: 12px;
                font-family: 'Outfit', 'Inter', sans-serif;
                font-size: 0.9rem;
                font-weight: 500;
                color: #fff;
                background: #1a1a1a;
                border: 1px solid rgba(255,255,255,0.08);
                box-shadow: 0 8px 32px rgba(0,0,0,0.5);
                pointer-events: auto;
                transform: translateX(120%);
                opacity: 0;
                transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
            }
            .cbt-toast.show {
                transform: translateX(0);
                opacity: 1;
            }
            .cbt-toast.hide {
                transform: translateX(120%);
                opacity: 0;
            }
            .cbt-toast-icon {
                font-size: 1.3rem;
                flex-shrink: 0;
                width: 28px;
                height: 28px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .cbt-toast-message { flex: 1; line-height: 1.4; }
            .cbt-toast-close {
                background: none;
                border: none;
                color: rgba(255,255,255,0.5);
                cursor: pointer;
                font-size: 1.1rem;
                padding: 0;
                line-height: 1;
                flex-shrink: 0;
                transition: color 0.2s;
            }
            .cbt-toast-close:hover { color: #fff; }

            /* Types */
            .cbt-toast.success { border-left: 3px solid #22c55e; }
            .cbt-toast.success .cbt-toast-icon { background: rgba(34,197,94,0.15); color: #22c55e; }
            .cbt-toast.error   { border-left: 3px solid #ef4444; }
            .cbt-toast.error   .cbt-toast-icon { background: rgba(239,68,68,0.15); color: #ef4444; }
            .cbt-toast.info    { border-left: 3px solid #ffc400; }
            .cbt-toast.info    .cbt-toast-icon { background: rgba(255,196,0,0.15); color: #ffc400; }
            .cbt-toast.warning { border-left: 3px solid #f59e0b; }
            .cbt-toast.warning .cbt-toast-icon { background: rgba(245,158,11,0.15); color: #f59e0b; }
            .cbt-toast.cart    { border-left: 3px solid #ffc400; }
            .cbt-toast.cart    .cbt-toast-icon { background: rgba(255,196,0,0.15); color: #ffc400; }

            @media (max-width: 480px) {
                #cbt-toast-container { bottom: 20px; right: 15px; left: 15px; }
                .cbt-toast { min-width: unset; max-width: 100%; }
            }
        `;
        document.head.appendChild(style);
    }

    function getToastContainer() {
        let container = document.getElementById('cbt-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'cbt-toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    const ICONS = {
        success: '✓',
        error:   '✕',
        info:    '●',
        warning: '⚠',
        cart:    '🛒'
    };

    window.showToast = function(message, type = 'info', duration = 3500) {
        const container = getToastContainer();

        const toast = document.createElement('div');
        toast.className = `cbt-toast ${type}`;
        toast.innerHTML = `
            <div class="cbt-toast-icon">${ICONS[type] || ICONS.info}</div>
            <div class="cbt-toast-message">${message}</div>
            <button class="cbt-toast-close" aria-label="Close">×</button>
        `;

        const closeBtn = toast.querySelector('.cbt-toast-close');
        const dismiss = () => {
            toast.classList.add('hide');
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        };
        closeBtn.addEventListener('click', dismiss);

        container.appendChild(toast);

        // Trigger entrance animation
        requestAnimationFrame(() => {
            requestAnimationFrame(() => toast.classList.add('show'));
        });

        // Auto dismiss
        setTimeout(dismiss, duration);
    };
})();
