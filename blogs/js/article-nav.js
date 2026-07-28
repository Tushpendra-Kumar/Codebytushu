// article-nav.js - CodeByTushu Article Navbar Functionality

document.addEventListener('DOMContentLoaded', () => {
    const articleNavbar = document.getElementById('articleNavbar');
    if (!articleNavbar) return;

    const articleNavRight = document.getElementById('articleNavRight');
    const mobileToggleBtn = document.getElementById('articleNavMobileToggle');

    // 1. Mobile Menu Toggle
    if (mobileToggleBtn && articleNavRight) {
        mobileToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            articleNavRight.classList.toggle('mobile-open');
        });
    }

    // 2. Dropdown Toggles
    const dropdowns = document.querySelectorAll('.article-nav-dropdown');
    
    dropdowns.forEach(dropdown => {
        const toggleBtn = dropdown.querySelector('.dropdown-toggle');
        if (!toggleBtn) return;
        
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isActive = dropdown.classList.contains('active');
            
            // Close all dropdowns
            dropdowns.forEach(d => d.classList.remove('active'));
            
            // Toggle clicked one
            if (!isActive) {
                dropdown.classList.add('active');
            }
        });
    });

    // Close dropdowns and mobile menu on outside click
    document.addEventListener('click', () => {
        dropdowns.forEach(d => d.classList.remove('active'));
        if (articleNavRight) {
            articleNavRight.classList.remove('mobile-open');
        }
    });

    // Prevent closing when clicking inside dropdown menu
    document.querySelectorAll('.article-dropdown-menu').forEach(menu => {
        menu.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    });

    // 2. Dynamic Table of Contents (TOC)
    const tocList = document.getElementById('tocList');
    const contentArea = document.getElementById('b-content');
    
    // We use a MutationObserver because the blog content is loaded dynamically via data.js
    const observer = new MutationObserver((mutations) => {
        if (contentArea && contentArea.innerHTML.indexOf('Loading content') === -1 && contentArea.innerHTML.trim() !== '') {
            generateTOC();
            observer.disconnect();
        }
    });
    
    if (contentArea) {
        observer.observe(contentArea, { childList: true, subtree: true });
        // Run immediately in case content is already loaded
        if (contentArea.innerHTML.indexOf('Loading content') === -1) {
            generateTOC();
            observer.disconnect();
        }
    }

    function generateTOC() {
        if (!tocList || !contentArea) return;
        
        // Find all headings (h2, h3, h4) inside the content
        const headings = contentArea.querySelectorAll('h1, h2, h3, h4');
        
        if (headings.length === 0) {
            tocList.innerHTML = '<div style="padding:10px 12px; color:#666; font-size:13px;">No headings found in this article.</div>';
            return;
        }

        tocList.innerHTML = '';
        let validHeadings = [];
        
        headings.forEach((heading, index) => {
            // Assign a unique ID if the heading doesn't have one
            if (!heading.id) {
                heading.id = 'heading-' + index;
            }
            
            validHeadings.push(heading);

            const a = document.createElement('a');
            a.href = '#' + heading.id;
            a.className = 'toc-item level-' + heading.tagName.toLowerCase();
            a.textContent = heading.textContent;
            
            a.addEventListener('click', (e) => {
                e.preventDefault();
                // Close dropdown & mobile menu
                dropdowns.forEach(d => d.classList.remove('active'));
                if (articleNavRight) articleNavRight.classList.remove('mobile-open');
                
                // Smooth scroll calculation: 
                // Main navbar (~80px) + Article navbar (~56px) + small padding (20px) = 156px offset
                const offset = 156; 
                const topPos = heading.getBoundingClientRect().top + window.scrollY - offset;
                
                window.scrollTo({
                    top: topPos,
                    behavior: 'smooth'
                });
            });

            tocList.appendChild(a);
        });
        
        setupScrollSpy(validHeadings);
    }
    
    // 3. ScrollSpy for TOC Highlights
    function setupScrollSpy(headings) {
        window.addEventListener('scroll', () => {
            let currentHeading = null;
            // The scroll position where a heading is considered "active"
            const scrollPos = window.scrollY + 160; 

            headings.forEach(heading => {
                if (heading.offsetTop <= scrollPos) {
                    currentHeading = heading;
                }
            });

            // Remove active class from all items
            document.querySelectorAll('.toc-item').forEach(item => {
                item.classList.remove('active');
            });

            // Add active class to current
            if (currentHeading) {
                const activeItem = document.querySelector(`.toc-item[href="#${currentHeading.id}"]`);
                if (activeItem) {
                    activeItem.classList.add('active');
                }
            }
        });
    }

    // 4. Category Button (Java) Smooth Scroll to First Content Section
    const categoryBtn = document.getElementById('navCategoryBtn');
    if (categoryBtn && contentArea) {
        categoryBtn.addEventListener('click', () => {
            // Find first heading, else just scroll to content top
            const firstHeading = contentArea.querySelector('h1, h2, h3');
            const targetElement = firstHeading || contentArea;
            
            const offset = 156;
            const topPos = targetElement.getBoundingClientRect().top + window.scrollY - offset;
            
            window.scrollTo({
                top: topPos,
                behavior: 'smooth'
            });
        });
    }
});
