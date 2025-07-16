// Loader global pour Laila Workspace - DÉSACTIVÉ POUR ÉVITER LES PROBLÈMES
class LailaLoader {
    constructor() {
        this.loaderOverlay = null;
        this.isLoading = false;
        // Désactiver complètement le loader
        this.disabled = true;
        this.init();
    }

    init() {
        // Ne rien faire si désactivé
        if (this.disabled) {
            this.hideAllLoaders();
            return;
        }
        
        // Créer le loader overlay
        this.createLoaderOverlay();
        
        // Écouter les événements de navigation
        this.setupNavigationListeners();
        
        // Masquer le loader après le chargement initial avec un délai
        setTimeout(() => {
            this.hideLoader();
        }, 1000);
    }

    hideAllLoaders() {
        // Masquer tous les loaders immédiatement
        const allLoaders = document.querySelectorAll('.loader-overlay, #global-loader, #js-loader');
        allLoaders.forEach(loader => {
            if (loader) {
                loader.style.display = 'none';
                loader.style.opacity = '0';
                loader.classList.add('hidden');
            }
        });
    }

    createLoaderOverlay() {
        // Ne rien faire si désactivé
        if (this.disabled) return;
        
        // Utiliser le loader HTML existant s'il existe
        this.loaderOverlay = document.getElementById('js-loader') || document.getElementById('global-loader');
        
        // Si aucun loader n'existe, en créer un nouveau
        if (!this.loaderOverlay) {
            this.loaderOverlay = document.createElement('div');
            this.loaderOverlay.className = 'loader-overlay';
            this.loaderOverlay.innerHTML = `
                <div class="loader-container">
                    <div class="loader-spinner"></div>
                    <div class="loader-text">Chargement...</div>
                    <div class="loader-subtext">Laila Workspace</div>
                </div>
            `;
            document.body.appendChild(this.loaderOverlay);
        }
    }

    showLoader(message = 'Chargement...', subtext = 'Laila Workspace') {
        // Ne rien faire si désactivé
        if (this.disabled) return;
        
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.loaderOverlay.style.display = 'flex';
        this.loaderOverlay.classList.remove('hidden');
        
        // Mettre à jour les messages si fournis
        const loaderText = this.loaderOverlay.querySelector('.loader-text');
        const loaderSubtext = this.loaderOverlay.querySelector('.loader-subtext');
        
        if (loaderText) loaderText.textContent = message;
        if (loaderSubtext) loaderSubtext.textContent = subtext;
    }

    hideLoader() {
        // Masquer tous les loaders immédiatement
        this.hideAllLoaders();
        
        if (this.disabled) return;
        
        this.isLoading = false;
        
        // Masquer tous les loaders possibles
        const allLoaders = document.querySelectorAll('.loader-overlay, #global-loader, #js-loader');
        allLoaders.forEach(loader => {
            if (loader) {
                loader.classList.add('hidden');
                loader.style.opacity = '0';
                
                // Masquer complètement après l'animation
                setTimeout(() => {
                    if (loader && loader.parentNode) {
                        loader.style.display = 'none';
                    }
                }, 300);
            }
        });
        
        // Masquer spécifiquement le loader principal
        if (this.loaderOverlay) {
            this.loaderOverlay.classList.add('hidden');
            this.loaderOverlay.style.opacity = '0';
            
            setTimeout(() => {
                if (this.loaderOverlay) {
                    this.loaderOverlay.style.display = 'none';
                }
            }, 300);
        }
    }

    setupNavigationListeners() {
        // Ne rien faire si désactivé
        if (this.disabled) return;
        
        // Intercepter les clics sur les liens
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link && this.shouldShowLoader(link)) {
                this.showLoader('Navigation...', 'Chargement de la page');
            }
        });

        // Intercepter les soumissions de formulaires
        document.addEventListener('submit', (e) => {
            if (e.target.tagName === 'FORM') {
                this.showLoader('Traitement...', 'Envoi en cours');
            }
        });

        // Gérer les boutons avec classe btn-loading
        document.addEventListener('click', (e) => {
            const button = e.target.closest('button');
            if (button && button.classList.contains('btn-loading')) {
                this.showButtonLoader(button);
            }
        });
    }

    shouldShowLoader(link) {
        // Ne jamais afficher le loader si désactivé
        if (this.disabled) return false;
        
        // Ne pas afficher le loader pour les liens externes ou les ancres
        const href = link.getAttribute('href');
        if (!href) return false;
        
        // Ne pas afficher pour les liens externes
        if (href.startsWith('http') && !href.includes(window.location.hostname)) {
            return false;
        }
        
        // Ne pas afficher pour les ancres
        if (href.startsWith('#')) {
            return false;
        }
        
        // Ne pas afficher pour les liens de téléchargement
        if (href.includes('download') || href.includes('.pdf') || href.includes('.doc')) {
            return false;
        }
        
        // Ne pas afficher pour les liens de navigation principale
        if (link.classList.contains('navbar-brand') || 
            link.closest('.navbar-brand') ||
            href.includes('index.php') ||
            href.includes('views/index.php') ||
            href.endsWith('/') ||
            href.endsWith('/views/') ||
            href.endsWith('/lailaworkspace/') ||
            href.endsWith('/lailaworkspace/views/')) {
            return false;
        }
        
        // Ne pas afficher pour les liens de la navbar principale
        const navLinks = ['Accueil', 'Home', 'index.php', 'dashboard.php'];
        const linkText = link.textContent.trim();
        if (navLinks.some(navLink => linkText.includes(navLink) || href.includes(navLink))) {
            return false;
        }
        
        return true;
    }

    showButtonLoader(button) {
        // Ne rien faire si désactivé
        if (this.disabled) return;
        
        const originalText = button.innerHTML;
        button.classList.add('btn-loading');
        button.innerHTML = `<span class="btn-text">${originalText}</span>`;
        
        // Restaurer le bouton après un délai (ou après la réponse AJAX)
        setTimeout(() => {
            button.classList.remove('btn-loading');
            button.innerHTML = originalText;
        }, 3000);
    }

    // Méthode pour les requêtes AJAX
    showAjaxLoader(message = 'Chargement des données...') {
        // Ne rien faire si désactivé
        if (this.disabled) return;
        
        this.showLoader(message, 'Traitement en cours');
    }

    hideAjaxLoader() {
        this.hideLoader();
    }
}

// Initialiser le loader quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    window.lailaLoader = new LailaLoader();
    
    // Masquer immédiatement tous les loaders
    if (window.lailaLoader) {
        window.lailaLoader.hideAllLoaders();
    }
    
    // Ajouter la classe d'animation au contenu principal
    const mainContent = document.querySelector('main, .container, .content');
    if (mainContent) {
        mainContent.classList.add('content-fade-in');
    }
});

// Gérer les événements de navigation du navigateur
window.addEventListener('beforeunload', () => {
    // Ne rien faire si désactivé
    if (window.lailaLoader && !window.lailaLoader.disabled) {
        window.lailaLoader.showLoader('Navigation...', 'Chargement de la page');
    }
});

// Fonctions utilitaires globales
window.showLoader = (message, subtext) => {
    if (window.lailaLoader && !window.lailaLoader.disabled) {
        window.lailaLoader.showLoader(message, subtext);
    }
};

window.hideLoader = () => {
    if (window.lailaLoader) {
        window.lailaLoader.hideLoader();
    }
};

window.showButtonLoader = (button) => {
    if (window.lailaLoader && !window.lailaLoader.disabled) {
        window.lailaLoader.showButtonLoader(button);
    }
};

// Fonction de sécurité pour forcer le masquage du loader
window.forceHideLoader = () => {
    // Masquer tous les loaders possibles
    const loaders = document.querySelectorAll('.loader-overlay, #global-loader, #js-loader');
    loaders.forEach(loader => {
        if (loader) {
            loader.classList.add('hidden');
            loader.style.opacity = '0';
            loader.style.display = 'none';
        }
    });
    
    // Masquer le loader JavaScript
    if (window.lailaLoader) {
        window.lailaLoader.hideLoader();
    }
    
    // Forcer le masquage de tous les éléments de loader
    const loaderElements = document.querySelectorAll('[class*="loader"]');
    loaderElements.forEach(element => {
        if (element.style) {
            element.style.display = 'none';
            element.style.opacity = '0';
        }
    });
};

// Forcer le masquage immédiatement
window.forceHideLoader();

// Forcer le masquage après 1 seconde maximum
setTimeout(() => {
    window.forceHideLoader();
}, 1000); 