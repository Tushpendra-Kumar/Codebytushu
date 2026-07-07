// blogs.js - Logic for the Blogs Module

document.addEventListener('DOMContentLoaded', () => {
    // 1. Check if we are on the listing page or details page
    const blogGrid = document.getElementById('blogGrid');
    const categoryContainer = document.getElementById('categoryFilters');
    const tagContainer = document.getElementById('tagFilters');
    const searchInput = document.getElementById('blogSearch');
    
    // For single blog page
    const urlParams = new URLSearchParams(window.location.search);
    const blogId = urlParams.get('id');

    // === LISTING PAGE LOGIC ===
    if (blogGrid) {
        let currentCategory = 'All';
        let currentTag = '';
        let searchQuery = '';

        // Initialize Categories
        if (categoryContainer) {
            BLOG_CATEGORIES.forEach(cat => {
                const btn = document.createElement('button');
                btn.className = `cbt-filter-chip ${cat === 'All' ? 'active' : ''}`;
                btn.textContent = cat;
                btn.onclick = () => {
                    document.querySelectorAll('#categoryFilters .cbt-filter-chip').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentCategory = cat;
                    currentTag = ''; // Reset tag filter on category change
                    document.querySelectorAll('#tagFilters .cbt-filter-chip').forEach(b => b.classList.remove('active'));
                    renderGrid();
                };
                categoryContainer.appendChild(btn);
            });
        }

        // Initialize Tags
        if (tagContainer) {
            BLOG_TAGS.forEach(tag => {
                const btn = document.createElement('button');
                btn.className = 'cbt-filter-chip';
                btn.textContent = tag;
                btn.onclick = () => {
                    // Remove active from All Tags button
                    const allBtn = document.getElementById('btn-all-tags');
                    if(allBtn) allBtn.classList.remove('active');

                    // Toggle tag
                    if (currentTag === tag) {
                        currentTag = '';
                        btn.classList.remove('active');
                        if(allBtn) allBtn.classList.add('active'); // Re-activate All Tags if no tag selected
                    } else {
                        document.querySelectorAll('#tagFilters .cbt-filter-chip').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                        currentTag = tag;
                    }
                    renderGrid();
                };
                tagContainer.appendChild(btn);
            });
            
            // Listen for global reset
            window.addEventListener('reset-tags-event', () => {
                currentTag = '';
                renderGrid();
            });
        }

        // Search Input
        const searchDropdown = document.getElementById('blogSearchDropdown');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                searchQuery = e.target.value.toLowerCase();
                renderGrid(); // still filter the main grid
                
                // Dropdown logic
                if (searchQuery.trim() === '') {
                    searchDropdown.style.display = 'none';
                    return;
                }
                
                const filtered = BLOG_POSTS.filter(blog => {
                    return blog.title.toLowerCase().includes(searchQuery) || 
                           blog.shortDesc.toLowerCase().includes(searchQuery) ||
                           blog.tags.some(t => t.toLowerCase().includes(searchQuery)) ||
                           blog.category.toLowerCase().includes(searchQuery);
                });

                searchDropdown.innerHTML = '';
                if (filtered.length === 0) {
                    searchDropdown.innerHTML = '<div class="cbt-search-no-results">No blogs found. Try another keyword.</div>';
                } else {
                    filtered.forEach(blog => {
                        const itemHTML = `
                            <a href="blog-details/index.html?id=${blog.id}" class="cbt-search-item">
                                <img src="${blog.thumbnail}" class="cbt-search-item-img" alt="${blog.title}">
                                <div class="cbt-search-item-info">
                                    <h4>${blog.title}</h4>
                                    <div class="cbt-search-item-meta">
                                        <span style="color:var(--primary);">${blog.category}</span>
                                        <span><i class="fa-regular fa-clock"></i> ${blog.readTime}</span>
                                    </div>
                                    <p class="cbt-search-item-desc">${blog.shortDesc}</p>
                                </div>
                            </a>
                        `;
                        searchDropdown.innerHTML += itemHTML;
                    });
                }
                searchDropdown.style.display = 'block';
            });

            // Keyboard Events
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    searchDropdown.style.display = 'none';
                }
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchDropdown.style.display = 'none';
                    document.getElementById('all-blogs').scrollIntoView({ behavior: 'smooth' });
                }
            });

            // Click outside to close
            document.addEventListener('click', (e) => {
                if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                    searchDropdown.style.display = 'none';
                }
            });
        }

        function renderGrid() {
            blogGrid.innerHTML = '';
            
            const filtered = BLOG_POSTS.filter(blog => {
                const matchCategory = currentCategory === 'All' || blog.category === currentCategory;
                const matchTag = currentTag === '' || blog.tags.includes(currentTag);
                const matchSearch = blog.title.toLowerCase().includes(searchQuery) || 
                                    blog.shortDesc.toLowerCase().includes(searchQuery) ||
                                    blog.tags.some(t => t.toLowerCase().includes(searchQuery));
                return matchCategory && matchTag && matchSearch;
            });

            if (filtered.length === 0) {
                blogGrid.innerHTML = '<div class="cbt-no-results">No blogs found matching your criteria. Try adjusting filters or search.</div>';
                return;
            }

            filtered.forEach(blog => {
                const card = document.createElement('div');
                card.className = 'cbt-blog-card';
                card.innerHTML = `
                    <div class="cbt-blog-card-img-wrapper">
                        <span class="cbt-blog-category-badge">${blog.category}</span>
                        <img src="${blog.thumbnail}" alt="${blog.title}" class="cbt-blog-card-img" loading="lazy">
                    </div>
                    <div class="cbt-blog-card-content">
                        <div class="cbt-blog-card-meta">
                            <span><i class="fa-regular fa-calendar"></i> ${blog.date}</span>
                            <span><i class="fa-regular fa-clock"></i> ${blog.readTime}</span>
                        </div>
                        <a href="blog-details/index.html?id=${blog.id}" style="text-decoration:none;">
                            <h3 class="cbt-blog-card-title">${blog.title}</h3>
                        </a>
                        <p class="cbt-blog-card-desc">${blog.shortDesc}</p>
                        <div class="cbt-blog-card-footer">
                            <div class="cbt-blog-card-author">
                                <div class="cbt-blog-author-img" style="background:var(--primary); display:flex; justify-content:center; align-items:center; color:#111; font-weight:bold;">T</div>
                                <span class="cbt-blog-author-name">${blog.author}</span>
                            </div>
                            <a href="blog-details/index.html?id=${blog.id}" class="cbt-btn-read-more">Read Article <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                `;
                blogGrid.appendChild(card);
            });
        }

        renderGrid();
    }

    // === SINGLE BLOG LOGIC ===
    if (blogId) {
        const blog = getBlogById(blogId);
        if (blog) {
            document.title = `${blog.title} | CodeByTushu Blogs`;
            
            const titleEl = document.getElementById('b-title');
            const metaEl = document.getElementById('b-meta');
            const bannerEl = document.getElementById('b-banner');
            const contentEl = document.getElementById('b-content');
            const tagsEl = document.getElementById('b-tags');

            if(titleEl) titleEl.textContent = blog.title;
            if(bannerEl) bannerEl.src = blog.thumbnail;
            if(contentEl) contentEl.innerHTML = blog.content;
            
            if(metaEl) {
                metaEl.innerHTML = `
                    <div class="author"><div style="width:30px; height:30px; border-radius:50%; background:var(--primary); display:flex; justify-content:center; align-items:center; color:#111;">T</div> ${blog.author}</div>
                    <span><i class="fa-regular fa-calendar"></i> ${blog.date}</span>
                    <span><i class="fa-regular fa-clock"></i> ${blog.readTime}</span>
                    <span><i class="fa-solid fa-folder-open"></i> ${blog.category}</span>
                `;
            }

            if(tagsEl && blog.tags) {
                blog.tags.forEach(tag => {
                    tagsEl.innerHTML += `<a href="../index.html" class="cbt-article-tag">#${tag}</a>`;
                });
            }

        } else {
            // Not found
            const layout = document.querySelector('.cbt-blog-layout');
            if(layout) {
                layout.innerHTML = '<div style="text-align:center; padding:100px; width:100%;"><h1 style="color:#fff;">Blog not found</h1><a href="../index.html" class="cbt-btn-primary" style="margin-top:20px; display:inline-block; padding:10px 20px;">Go Back</a></div>';
            }
        }
    }

    // Recent Posts Sidebar Injection
    const recentWidget = document.getElementById('b-recent-posts');
    if (recentWidget) {
        const recents = getRecentBlogs(3);
        recents.forEach(b => {
            recentWidget.innerHTML += `
                <div class="cbt-recent-post">
                    <img src="${b.thumbnail}" alt="${b.title}">
                    <div class="cbt-recent-post-info">
                        <h4><a href="index.html?id=${b.id}">${b.title}</a></h4>
                        <span>${b.date}</span>
                    </div>
                </div>
            `;
        });
    }

    // Comment Form Logic (Frontend Only)
    const commentForm = document.getElementById('commentForm');
    const commentList = document.getElementById('commentList');
    if (commentForm && commentList) {
        commentForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const name = document.getElementById('cName').value;
            const comment = document.getElementById('cMessage').value;
            
            const date = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            const initial = name.charAt(0).toUpperCase();

            const html = `
                <div class="cbt-comment" style="animation: fadeIn 0.5s;">
                    <div class="cbt-comment-avatar">${initial}</div>
                    <div class="cbt-comment-body">
                        <h4>${name}</h4>
                        <span>${date}</span>
                        <p>${comment}</p>
                    </div>
                </div>
            `;
            commentList.insertAdjacentHTML('afterbegin', html);
            commentForm.reset();
        });
    }

    // Hamburger Menu
    const ham = document.getElementById('cbt-hamburger-btn');
    const nav = document.getElementById('cbt-center-nav');
    if(ham && nav) {
        ham.addEventListener('click', () => {
            nav.classList.toggle('show');
        });
    }

    // Scroll Spy & Navigation
    const sections = document.querySelectorAll('header#home, section.cbt-blog-container');
    const navLinks = document.querySelectorAll('.cbt-center-nav a');
    
    // Smooth scrolling for links
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').replace('#', '');
            if (targetId) {
                const targetSec = document.getElementById(targetId);
                if (targetSec) {
                    const offset = 80; // height of fixed navbar
                    const elementPosition = targetSec.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - offset;
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                    history.pushState(null, null, '#' + targetId);
                }
            }
        });
    });

    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (pageYOffset >= (sectionTop - 150)) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').includes(current) && current !== '') {
                link.classList.add('active');
            }
        });
        
        if(current && current !== 'home') {
            history.replaceState(null, null, '#' + current);
        } else if (current === 'home') {
            history.replaceState(null, null, window.location.pathname);
        }
    });
    
    // Check hash on load
    if (window.location.hash) {
        const targetId = window.location.hash.substring(1);
        const targetSec = document.getElementById(targetId);
        if (targetSec) {
            setTimeout(() => {
                const offset = 80;
                const elementPosition = targetSec.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - offset;
                window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
            }, 100);
        }
    }
});

// Global function for "All Tags" button
window.resetTags = function() {
    document.querySelectorAll('#tagFilters .cbt-filter-chip').forEach(b => b.classList.remove('active'));
    const allBtn = document.getElementById('btn-all-tags');
    if(allBtn) allBtn.classList.add('active');
    
    // We need to trigger renderGrid but since it's scoped, we dispatch a custom event
    window.dispatchEvent(new CustomEvent('reset-tags-event'));
};
