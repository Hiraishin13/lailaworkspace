/**
 * Script pour corriger automatiquement les URLs avec des chemins Windows
 * Laila Workspace - URL Fixer
 */

(function() {
    'use strict';

    // Fonction pour corriger une URL
    function fixUrl(url) {
        if (!url) return url;
        
        // Remplacer les chemins Windows par le chemin correct
        let fixedUrl = url.replace(/\/C:\/wamp64\/www\//g, '/lailaworkspace/');
        fixedUrl = fixedUrl.replace(/\/C:\\wamp64\\www\\/g, '/lailaworkspace/');
        
        return fixedUrl;
    }

    // Corriger les liens au chargement de la page
    function fixAllLinks() {
        const links = document.querySelectorAll('a[href]');
        
        links.forEach(link => {
            const originalHref = link.getAttribute('href');
            const fixedHref = fixUrl(originalHref);
            
            if (originalHref !== fixedHref) {
                console.log('URL corrigée:', originalHref, '→', fixedHref);
                link.setAttribute('href', fixedHref);
            }
        });

        // Corriger aussi les formulaires
        const forms = document.querySelectorAll('form[action]');
        forms.forEach(form => {
            const originalAction = form.getAttribute('action');
            const fixedAction = fixUrl(originalAction);
            
            if (originalAction !== fixedAction) {
                console.log('Action de formulaire corrigée:', originalAction, '→', fixedAction);
                form.setAttribute('action', fixedAction);
            }
        });
    }

    // Corriger les redirections JavaScript
    function fixJavaScriptRedirects() {
        // Intercepter window.location.href
        const originalLocationHref = Object.getOwnPropertyDescriptor(window.location, 'href');
        
        Object.defineProperty(window.location, 'href', {
            set: function(url) {
                const fixedUrl = fixUrl(url);
                if (url !== fixedUrl) {
                    console.log('Redirection JavaScript corrigée:', url, '→', fixedUrl);
                }
                return originalLocationHref.set.call(this, fixedUrl);
            },
            get: originalLocationHref.get
        });

        // Intercepter window.location.assign
        const originalAssign = window.location.assign;
        window.location.assign = function(url) {
            const fixedUrl = fixUrl(url);
            if (url !== fixedUrl) {
                console.log('Location.assign corrigée:', url, '→', fixedUrl);
            }
            return originalAssign.call(this, fixedUrl);
        };

        // Intercepter window.location.replace
        const originalReplace = window.location.replace;
        window.location.replace = function(url) {
            const fixedUrl = fixUrl(url);
            if (url !== fixedUrl) {
                console.log('Location.replace corrigée:', url, '→', fixedUrl);
            }
            return originalReplace.call(this, fixedUrl);
        };
    }

    // Corriger les clics sur les liens
    function fixLinkClicks() {
        document.addEventListener('click', function(e) {
            if (e.target.tagName === 'A' || e.target.closest('a')) {
                const link = e.target.tagName === 'A' ? e.target : e.target.closest('a');
                const href = link.getAttribute('href');
                
                if (href && (href.includes('C:/wamp64/www/') || href.includes('C:\\wamp64\\www\\'))) {
                    e.preventDefault();
                    const fixedHref = fixUrl(href);
                    console.log('Clic sur lien corrigé:', href, '→', fixedHref);
                    window.location.href = fixedHref;
                }
            }
        });
    }

    // Fonction pour corriger l'URL actuelle si nécessaire
    function fixCurrentUrl() {
        const currentUrl = window.location.href;
        const fixedUrl = fixUrl(currentUrl);
        
        if (currentUrl !== fixedUrl) {
            console.log('URL actuelle corrigée:', currentUrl, '→', fixedUrl);
            window.location.replace(fixedUrl);
            return;
        }
    }

    // Initialisation
    function init() {
        console.log('🔧 Laila Workspace - URL Fixer initialisé');
        
        // Corriger l'URL actuelle
        fixCurrentUrl();
        
        // Corriger tous les liens
        fixAllLinks();
        
        // Corriger les redirections JavaScript
        fixJavaScriptRedirects();
        
        // Corriger les clics sur les liens
        fixLinkClicks();
        
        // Observer les changements dans le DOM pour corriger les nouveaux liens
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        fixAllLinks();
                    }
                });
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }

    // Attendre que le DOM soit chargé
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Exposer la fonction globalement pour utilisation manuelle
    window.LailaUrlFixer = {
        fixUrl: fixUrl,
        fixAllLinks: fixAllLinks
    };

})(); 