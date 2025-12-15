/* ==========================================================================
   MC.Yigit - JavaScript
   Production Ready - Performance Optimized
   ========================================================================== */

(function() {
    'use strict';

    // DOM Elements
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navMenu = document.querySelector('.nav-menu');
    const backToTopBtn = document.querySelector('.back-to-top');
    const header = document.querySelector('.site-header');
    const navLinks = document.querySelectorAll('.nav-link');

    // State
    let lastScrollY = 0;
    let ticking = false;

    // ==========================================================================
    // Mobile Menu
    // ==========================================================================

    function toggleMobileMenu() {
        const isExpanded = mobileMenuBtn.getAttribute('aria-expanded') === 'true';
        mobileMenuBtn.setAttribute('aria-expanded', !isExpanded);
        navMenu.classList.toggle('active');
        document.body.style.overflow = isExpanded ? '' : 'hidden';
    }

    function closeMobileMenu() {
        mobileMenuBtn.setAttribute('aria-expanded', 'false');
        navMenu.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
    }

    // Close menu when clicking nav links
    navLinks.forEach(link => {
        link.addEventListener('click', closeMobileMenu);
    });

    // Close menu on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && navMenu.classList.contains('active')) {
            closeMobileMenu();
        }
    });

    // ==========================================================================
    // Scroll Handling
    // ==========================================================================

    function handleScroll() {
        lastScrollY = window.scrollY;

        if (!ticking) {
            window.requestAnimationFrame(() => {
                updateOnScroll();
                ticking = false;
            });
            ticking = true;
        }
    }

    function updateOnScroll() {
        // Back to top button visibility
        if (backToTopBtn) {
            if (lastScrollY > 500) {
                backToTopBtn.removeAttribute('hidden');
            } else {
                backToTopBtn.setAttribute('hidden', '');
            }
        }

        // Header background opacity based on scroll
        if (header) {
            const opacity = Math.min(lastScrollY / 100, 0.95);
            header.style.backgroundColor = `rgba(10, 10, 11, ${0.85 + opacity * 0.1})`;
        }
    }

    window.addEventListener('scroll', handleScroll, { passive: true });

    // ==========================================================================
    // Smooth Scroll for Anchor Links
    // ==========================================================================

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                e.preventDefault();
                
                const headerHeight = header ? header.offsetHeight : 0;
                const targetPosition = targetElement.getBoundingClientRect().top + window.scrollY - headerHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    // ==========================================================================
    // Intersection Observer for Animations
    // ==========================================================================

    function setupIntersectionObserver() {
        const observerOptions = {
            root: null,
            rootMargin: '0px 0px -50px 0px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe article cards
        document.querySelectorAll('.article-card, .featured-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    }

    // Add visible state styles
    const style = document.createElement('style');
    style.textContent = `
        .article-card.visible,
        .featured-card.visible {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
    `;
    document.head.appendChild(style);

    // ==========================================================================
    // Newsletter Form
    // ==========================================================================

    const newsletterForm = document.querySelector('.newsletter-form');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const emailInput = this.querySelector('input[type="email"]');
            const submitBtn = this.querySelector('.submit-btn');
            const email = emailInput.value.trim();
            
            if (!isValidEmail(email)) {
                showFormMessage(this, 'Lütfen geçerli bir e-posta adresi girin.', 'error');
                return;
            }
            
            // Disable button during submission
            submitBtn.disabled = true;
            submitBtn.textContent = 'Gönderiliyor...';
            
            // Simulate form submission (replace with actual API call)
            setTimeout(() => {
                showFormMessage(this, 'Başarıyla abone oldunuz!', 'success');
                emailInput.value = '';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Abone Ol';
            }, 1000);
        });
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function showFormMessage(form, message, type) {
        // Remove existing message
        const existingMessage = form.parentElement.querySelector('.form-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Create message element
        const messageEl = document.createElement('p');
        messageEl.className = `form-message form-message--${type}`;
        messageEl.textContent = message;
        messageEl.style.cssText = `
            margin-top: 1rem;
            font-size: 0.875rem;
            color: ${type === 'error' ? '#ef4444' : '#22c55e'};
        `;
        
        form.insertAdjacentElement('afterend', messageEl);
        
        // Remove message after 5 seconds
        setTimeout(() => {
            messageEl.remove();
        }, 5000);
    }

    // ==========================================================================
    // Active Navigation Link
    // ==========================================================================

    function updateActiveNavLink() {
        const sections = document.querySelectorAll('section[id]');
        const scrollPosition = window.scrollY + 100;
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            const sectionId = section.getAttribute('id');
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${sectionId}`) {
                        link.classList.add('active');
                    }
                });
            }
        });
    }

    window.addEventListener('scroll', updateActiveNavLink, { passive: true });

    // ==========================================================================
    // Lazy Loading Enhancement
    // ==========================================================================

    function setupLazyLoading() {
        if ('loading' in HTMLImageElement.prototype) {
            // Browser supports native lazy loading
            return;
        }
        
        // Fallback for browsers that don't support native lazy loading
        const lazyImages = document.querySelectorAll('img[loading="lazy"]');
        
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    }

    // ==========================================================================
    // Performance: Preload Links on Hover
    // ==========================================================================

    function setupLinkPreloading() {
        const links = document.querySelectorAll('a[href^="/"], a[href^="./"]');
        
        links.forEach(link => {
            link.addEventListener('mouseenter', () => {
                const href = link.getAttribute('href');
                
                if (!document.querySelector(`link[rel="prefetch"][href="${href}"]`)) {
                    const prefetch = document.createElement('link');
                    prefetch.rel = 'prefetch';
                    prefetch.href = href;
                    document.head.appendChild(prefetch);
                }
            }, { once: true });
        });
    }

    // ==========================================================================
    // Initialize
    // ==========================================================================

    function init() {
        setupIntersectionObserver();
        setupLazyLoading();
        setupLinkPreloading();
        updateActiveNavLink();
        
        // Remove initial loading state if any
        document.body.classList.add('loaded');
    }

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
