/**
 * Main JavaScript functionality
 * Handles mobile menu, scroll effects, lazy loading, form validation
 */

(function() {
    'use strict';

    // ==============================================
    // MOBILE MENU TOGGLE
    // ==============================================
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            // Animate hamburger
            const spans = hamburger.querySelectorAll('span');
            if (navLinks.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
        
        // Close menu on link click
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                const spans = hamburger.querySelectorAll('span');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            });
        });
    }

    // ==============================================
    // SCROLL TO TOP BUTTON
    // ==============================================
    const scrollBtn = document.querySelector('.scroll-top');
    
    if (scrollBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 500) {
                scrollBtn.classList.add('show');
            } else {
                scrollBtn.classList.remove('show');
            }
        });
        
        scrollBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // ==============================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // ==============================================
    document.querySelectorAll('a[href^="#"]:not([href="#"])').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // ==============================================
    // ACTIVE LINK HIGHLIGHTING
    // ==============================================
    function setActiveLink() {
        const currentPath = window.location.pathname;
        const links = document.querySelectorAll('.nav-links a');
        
        links.forEach(link => {
            const linkPath = link.getAttribute('href');
            if (linkPath === currentPath || 
                (linkPath !== '/' && currentPath.startsWith(linkPath))) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }
    setActiveLink();

    // ==============================================
    // LAZY LOADING IMAGES (Native + fallback)
    // ==============================================
    if ('loading' in HTMLImageElement.prototype) {
        // Native lazy loading supported
        const images = document.querySelectorAll('img[loading="lazy"]');
        images.forEach(img => {
            img.setAttribute('loading', 'lazy');
        });
    } else {
        // Fallback using Intersection Observer
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            lazyImages.forEach(img => imageObserver.observe(img));
        }
    }

    // ==============================================
    // FORM VALIDATION
    // ==============================================
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const inputs = form.querySelectorAll('input[required], textarea[required]');
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    showError(input, 'This field is required');
                } else if (input.type === 'email' && !isValidEmail(input.value)) {
                    isValid = false;
                    showError(input, 'Please enter a valid email address');
                } else if (input.type === 'url' && input.value && !isValidUrl(input.value)) {
                    isValid = false;
                    showError(input, 'Please enter a valid URL');
                } else {
                    clearError(input);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    
    function showError(input, message) {
        const formGroup = input.closest('.form-group');
        if (formGroup) {
            let error = formGroup.querySelector('.error-message');
            if (!error) {
                error = document.createElement('span');
                error.className = 'error-message';
                error.style.color = '#e00';
                error.style.fontSize = '12px';
                error.style.marginTop = '4px';
                formGroup.appendChild(error);
            }
            error.textContent = message;
            input.style.borderColor = '#e00';
        }
    }
    
    function clearError(input) {
        const formGroup = input.closest('.form-group');
        if (formGroup) {
            const error = formGroup.querySelector('.error-message');
            if (error) error.remove();
            input.style.borderColor = '';
        }
    }
    
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    // ==============================================
    // COUNTER ANIMATION FOR STATS
    // ==============================================
    const counters = document.querySelectorAll('.stat-number[data-count]');
    
    if ('IntersectionObserver' in window && counters.length) {
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = parseInt(counter.getAttribute('data-count'));
                    const duration = 2000;
                    const step = Math.ceil(target / (duration / 16));
                    let current = 0;
                    
                    const updateCounter = () => {
                        current += step;
                        if (current >= target) {
                            counter.textContent = target.toLocaleString();
                            return;
                        }
                        counter.textContent = current.toLocaleString();
                        requestAnimationFrame(updateCounter);
                    };
                    
                    updateCounter();
                    counterObserver.unobserve(counter);
                }
            });
        }, { threshold: 0.5 });
        
        counters.forEach(counter => counterObserver.observe(counter));
    }

    // ==============================================
    // TABLE OF CONTENTS GENERATION
    // ==============================================
    function generateTOC() {
        const content = document.querySelector('.post-content, .article-content');
        const tocContainer = document.querySelector('.toc');
        
        if (!content || !tocContainer) return;
        
        const headings = content.querySelectorAll('h2, h3');
        if (headings.length < 2) {
            tocContainer.style.display = 'none';
            return;
        }
        
        const tocList = document.createElement('ul');
        headings.forEach((heading, index) => {
            const id = `heading-${index}`;
            heading.id = id;
            
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = `#${id}`;
            a.textContent = heading.textContent;
            if (heading.tagName === 'H3') {
                li.style.marginLeft = '20px';
            }
            li.appendChild(a);
            tocList.appendChild(li);
        });
        
        tocContainer.innerHTML = '';
        tocContainer.appendChild(tocList);
    }
    
    if (document.querySelector('.post-content')) {
        generateTOC();
    }

})();