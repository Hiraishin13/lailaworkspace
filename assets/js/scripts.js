/**
 * Laila Workspace - Scripts d'amélioration UX
 * Améliore l'expérience utilisateur avec des animations et interactions modernes
 */

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================================================
    // Animations au scroll
    // ==========================================================================
    function initScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, observerOptions);

        // Observer tous les éléments avec la classe animate-on-scroll
        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            observer.observe(el);
        });
    }

    // ==========================================================================
    // Amélioration des formulaires
    // ==========================================================================
    function enhanceForms() {
        // Ajouter des effets de focus améliorés
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });

            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });

            // Animation de validation en temps réel
            input.addEventListener('input', function() {
                if (this.checkValidity()) {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                } else {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                }
            });
        });

        // Amélioration des boutons de soumission
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                    
                    // Simuler un délai de chargement (à retirer en production)
                    setTimeout(() => {
                        submitBtn.classList.remove('loading');
                        submitBtn.disabled = false;
                    }, 2000);
                }
            });
        });
    }

    // ==========================================================================
    // Amélioration des cartes et éléments interactifs
    // ==========================================================================
    function enhanceCards() {
        // Effet de parallaxe sur les cartes
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;
                
                this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(10px)`;
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateZ(0)';
            });
        });

        // Animation des icônes au survol
        document.querySelectorAll('.nav-link i, .btn i').forEach(icon => {
            icon.addEventListener('mouseenter', function() {
                this.style.animation = 'bounce 0.6s ease';
            });

            icon.addEventListener('animationend', function() {
                this.style.animation = '';
            });
        });
    }

    // ==========================================================================
    // Amélioration de la navigation
    // ==========================================================================
    function enhanceNavigation() {
        // Effet de scroll sur la navbar
        let lastScrollTop = 0;
        const navbar = document.querySelector('.navbar');
        
        if (navbar) {
            window.addEventListener('scroll', function() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                if (scrollTop > lastScrollTop && scrollTop > 100) {
                    // Scroll vers le bas
                    navbar.style.transform = 'translateY(-100%)';
                } else {
                    // Scroll vers le haut
                    navbar.style.transform = 'translateY(0)';
                }
                
                lastScrollTop = scrollTop;
            });
        }

        // Amélioration du menu mobile
        const navbarToggler = document.querySelector('.navbar-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse');
        
        if (navbarToggler && navbarCollapse) {
            navbarToggler.addEventListener('click', function() {
                navbarCollapse.classList.toggle('show');
                
                // Animation des liens du menu
                const navLinks = navbarCollapse.querySelectorAll('.nav-link');
                navLinks.forEach((link, index) => {
                    if (navbarCollapse.classList.contains('show')) {
                        link.style.animation = `slideInLeft 0.3s ease ${index * 0.1}s both`;
                    } else {
                        link.style.animation = '';
                    }
                });
            });
        }
    }

    // ==========================================================================
    // Amélioration des modales
    // ==========================================================================
    function enhanceModals() {
        // Animation d'entrée pour les modales
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('show.bs.modal', function() {
                const modalContent = this.querySelector('.modal-content');
                modalContent.style.transform = 'scale(0.7)';
                modalContent.style.opacity = '0';
                
                setTimeout(() => {
                    modalContent.style.transform = 'scale(1)';
                    modalContent.style.opacity = '1';
                }, 10);
            });
        });
    }

    // ==========================================================================
    // Amélioration des alertes
    // ==========================================================================
    function enhanceAlerts() {
        // Animation d'entrée pour les alertes
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.animation = 'slideInRight 0.5s ease';
            
            // Auto-fermeture des alertes de succès
            if (alert.classList.contains('alert-success')) {
                setTimeout(() => {
                    alert.style.animation = 'slideOutRight 0.5s ease';
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 5000);
            }
        });
    }

    // ==========================================================================
    // Amélioration des boutons
    // ==========================================================================
    function enhanceButtons() {
        // Effet de ripple sur les boutons
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    }

    // ==========================================================================
    // Amélioration des liens
    // ==========================================================================
    function enhanceLinks() {
        // Animation des liens au survol
        document.querySelectorAll('a').forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.transition = 'all 0.3s ease';
            });
        });
    }

    // ==========================================================================
    // Amélioration des images
    // ==========================================================================
    function enhanceImages() {
        // Lazy loading pour les images
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    }

    // ==========================================================================
    // Amélioration de la performance
    // ==========================================================================
    function enhancePerformance() {
        // Debounce pour les événements de scroll
        let scrollTimeout;
        window.addEventListener('scroll', function() {
            if (scrollTimeout) {
                clearTimeout(scrollTimeout);
            }
            scrollTimeout = setTimeout(() => {
                // Actions à effectuer après l'arrêt du scroll
            }, 100);
        });

        // Throttle pour les événements de resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            if (resizeTimeout) {
                clearTimeout(resizeTimeout);
            }
            resizeTimeout = setTimeout(() => {
                // Actions à effectuer après le redimensionnement
            }, 250);
        });
    }

    // ==========================================================================
    // Amélioration de l'accessibilité
    // ==========================================================================
    function enhanceAccessibility() {
        // Amélioration de la navigation au clavier
        document.addEventListener('keydown', function(e) {
            // Échap pour fermer les modales
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    const closeBtn = openModal.querySelector('.btn-close');
                    if (closeBtn) closeBtn.click();
                }
            }
        });

        // Amélioration des focus
        document.querySelectorAll('button, a, input, textarea, select').forEach(element => {
            element.addEventListener('focus', function() {
                this.style.outline = '2px solid var(--primary-color)';
                this.style.outlineOffset = '2px';
            });

            element.addEventListener('blur', function() {
                this.style.outline = '';
                this.style.outlineOffset = '';
            });
        });
    }

    // ==========================================================================
    // Initialisation
    // ==========================================================================
    function init() {
        console.log('🚀 Laila Workspace - Scripts d\'amélioration UX chargés');
        
        // Initialiser toutes les améliorations
        initScrollAnimations();
        enhanceForms();
        enhanceCards();
        enhanceNavigation();
        enhanceModals();
        enhanceAlerts();
        enhanceButtons();
        enhanceLinks();
        enhanceImages();
        enhancePerformance();
        enhanceAccessibility();
        
        // Ajouter des classes d'animation aux éléments existants
        document.querySelectorAll('.card, .btn, .nav-link').forEach(el => {
            el.classList.add('animate-on-scroll');
        });
    }

    // Démarrer l'initialisation
    init();
});

// ==========================================================================
// Styles CSS pour les animations
// ==========================================================================
const additionalStyles = `
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }

    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    .slideOutRight {
        animation: slideOutRight 0.5s ease;
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    .focused .form-label {
        color: var(--primary-color);
        transform: translateY(-5px);
    }

    .is-valid {
        border-color: var(--success-color) !important;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
    }

    .is-invalid {
        border-color: var(--danger-color) !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }

    .loading {
        position: relative;
        color: transparent !important;
    }

    .loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 20px;
        height: 20px;
        margin: -10px 0 0 -10px;
        border: 2px solid transparent;
        border-top: 2px solid currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .navbar {
        transition: transform 0.3s ease;
    }

    .navbar-collapse .nav-link {
        opacity: 0;
        transform: translateX(-20px);
    }

    .navbar-collapse.show .nav-link {
        opacity: 1;
        transform: translateX(0);
    }

    .lazy {
        opacity: 0;
        transition: opacity 0.3s;
    }

    .lazy.loaded {
        opacity: 1;
    }
`;

// Injecter les styles supplémentaires
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);