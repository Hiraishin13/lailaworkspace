// Loader global pour Laila Workspace
class LailaLoader {
    constructor() {
        this.loaderOverlay = null;
        this.isLoading = false;
        this.init();
    }

    init() {
        // Créer le loader overlay
        this.createLoaderOverlay();
        
        // Écouter les événements de navigation
        this.setupNavigationListeners();
        
        // Masquer le loader après le chargement initial avec un délai
        setTimeout(() => {
            this.hideLoader();
        }, 1000);
    }

    createLoaderOverlay() {
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

    showLoader(message = 'Chargement...', subtext = 'Laila Workspace') {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.loaderOverlay.classList.remove('hidden');
        
        // Mettre à jour les messages si fournis
        const loaderText = this.loaderOverlay.querySelector('.loader-text');
        const loaderSubtext = this.loaderOverlay.querySelector('.loader-subtext');
        
        if (loaderText) loaderText.textContent = message;
        if (loaderSubtext) loaderSubtext.textContent = subtext;
    }

    hideLoader() {
        if (!this.isLoading && !this.loaderOverlay) return;
        
        this.isLoading = false;
        
        if (this.loaderOverlay) {
            this.loaderOverlay.classList.add('hidden');
            
            // Supprimer complètement après l'animation
            setTimeout(() => {
                if (this.loaderOverlay && this.loaderOverlay.parentNode) {
                    this.loaderOverlay.parentNode.removeChild(this.loaderOverlay);
                    this.loaderOverlay = null;
                }
            }, 300);
        }
    }

    setupNavigationListeners() {
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
        
        return true;
    }

    showButtonLoader(button) {
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
        this.showLoader(message, 'Traitement en cours');
    }

    hideAjaxLoader() {
        this.hideLoader();
    }
}

// Initialiser le loader quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    window.lailaLoader = new LailaLoader();
    
    // Masquer le loader après un délai pour s'assurer que tout est chargé
    setTimeout(() => {
        if (window.lailaLoader) {
            window.lailaLoader.hideLoader();
        }
    }, 1500);
    
    // Ajouter la classe d'animation au contenu principal
    const mainContent = document.querySelector('main, .container, .content');
    if (mainContent) {
        mainContent.classList.add('content-fade-in');
    }
});

// Gérer les événements de navigation du navigateur
window.addEventListener('beforeunload', () => {
    if (window.lailaLoader) {
        window.lailaLoader.showLoader('Navigation...', 'Chargement de la page');
    }
});

// Fonctions utilitaires globales
window.showLoader = (message, subtext) => {
    if (window.lailaLoader) {
        window.lailaLoader.showLoader(message, subtext);
    }
};

window.hideLoader = () => {
    if (window.lailaLoader) {
        window.lailaLoader.hideLoader();
    }
};

window.showButtonLoader = (button) => {
    if (window.lailaLoader) {
        window.lailaLoader.showButtonLoader(button);
    }
};

// Fonction de sécurité pour forcer le masquage du loader
window.forceHideLoader = () => {
    // Masquer tous les loaders possibles
    const loaders = document.querySelectorAll('.loader-overlay');
    loaders.forEach(loader => {
        loader.classList.add('hidden');
        setTimeout(() => {
            if (loader.parentNode) {
                loader.parentNode.removeChild(loader);
            }
        }, 300);
    });
    
    // Masquer le loader JavaScript
    if (window.lailaLoader) {
        window.lailaLoader.hideLoader();
    }
};

// Forcer le masquage après 3 secondes maximum
setTimeout(() => {
    window.forceHideLoader();
}, 3000); 