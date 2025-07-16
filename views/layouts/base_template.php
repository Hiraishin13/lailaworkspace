<?php
// Template de base pour toutes les pages de Laila Workspace
// Inclut le loader, la navbar et le footer cohérents

// Vérifier si la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le titre de la page (à définir dans chaque page)
$page_title = $page_title ?? 'Laila Workspace';

// Définir les styles CSS supplémentaires (optionnel)
$additional_css = $additional_css ?? '';

// Définir les scripts JS supplémentaires (optionnel)
$additional_js = $additional_js ?? '';

// Vérifier si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['user_id']);

// Récupérer les informations utilisateur si connecté
$user_info = null;
if ($is_logged_in) {
    try {
        $stmt = $pdo->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Gérer l'erreur silencieusement
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    
    <!-- Meta tags pour SEO -->
    <meta name="description" content="Transformez vos idées en Business Model Canvas avec notre générateur IA. Créez, analysez et affinez vos projets en toute simplicité.">
    <meta name="keywords" content="Business Model Canvas, BMC, générateur IA, entrepreneuriat, business plan">
    <meta name="author" content="Laila Workspace">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/assets/images/favicon.ico">
    
    <!-- CSS Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
    
    <!-- CSS du loader -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/loader.css">
    
    <!-- CSS supplémentaires spécifiques à la page -->
    <?php if ($additional_css): ?>
        <?= $additional_css ?>
    <?php endif; ?>
    
    <!-- Préchargement des ressources critiques -->
    <link rel="preload" href="<?= BASE_URL ?>/assets/js/loader.js" as="script">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" as="script">
</head>
<body>
    <!-- Loader global -->
    <div id="global-loader" class="loader-overlay">
        <div class="loader-container">
            <div class="loader-spinner"></div>
            <div class="loader-text">Chargement...</div>
            <div class="loader-subtext">Laila Workspace</div>
        </div>
    </div>
    
    <!-- Loader JavaScript (sera créé automatiquement) -->
    <div id="js-loader" class="loader-overlay" style="display: none;">
        <div class="loader-container">
            <div class="loader-spinner"></div>
            <div class="loader-text">Chargement...</div>
            <div class="loader-subtext">Laila Workspace</div>
        </div>
    </div>

    <!-- Navbar cohérente -->
    <?php include __DIR__ . '/navbar.php'; ?>

    <!-- Contenu principal de la page -->
    <main class="content-fade-in">
        <?php if (isset($page_content)): ?>
            <?= $page_content ?>
        <?php endif; ?>
    </main>

    <!-- Footer cohérent -->
    <?php include __DIR__ . '/footer.php'; ?>

    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Script du loader -->
    <script src="<?= BASE_URL ?>/assets/js/loader.js"></script>
    
    <!-- Script de correction d'URLs -->
    <script src="<?= BASE_URL ?>/assets/js/url-fix.js"></script>
    
    <!-- Scripts supplémentaires spécifiques à la page -->
    <?php if ($additional_js): ?>
        <?= $additional_js ?>
    <?php endif; ?>

    <!-- Script global pour la cohérence -->
    <script>
        // Masquer le loader initial après le chargement
        document.addEventListener('DOMContentLoaded', function() {
            // Vérifier si l'URL est incorrecte et afficher un message
            const currentUrl = window.location.href;
            if (currentUrl.includes('C:/wamp64/www/') || currentUrl.includes('C:\\wamp64\\www\\')) {
                // Créer un message d'alerte
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-warning alert-dismissible fade show position-fixed';
                alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
                alertDiv.innerHTML = `
                    <strong>URL incorrecte détectée !</strong><br>
                    Utilisez plutôt : <code>http://localhost/lailaworkspace/</code><br>
                    <small class="text-muted">Cette URL sera automatiquement corrigée.</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(alertDiv);
                
                // Supprimer le message après 5 secondes
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            }
            
            // Masquer le loader global après un court délai
            setTimeout(() => {
                const globalLoader = document.getElementById('global-loader');
                if (globalLoader) {
                    globalLoader.classList.add('hidden');
                    globalLoader.style.opacity = '0';
                    setTimeout(() => {
                        globalLoader.style.display = 'none';
                    }, 300);
                }
                
                // S'assurer que le loader JavaScript se masque aussi
                if (window.lailaLoader) {
                    window.lailaLoader.hideLoader();
                }
                
                // Forcer le masquage de tous les loaders
                const allLoaders = document.querySelectorAll('.loader-overlay, #global-loader, #js-loader');
                allLoaders.forEach(loader => {
                    if (loader) {
                        loader.classList.add('hidden');
                        loader.style.opacity = '0';
                        loader.style.display = 'none';
                    }
                });
            }, 800);
            
            // Ajouter des animations d'entrée pour les éléments
            const animatedElements = document.querySelectorAll('.card, .btn, .alert');
            animatedElements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.1}s`;
                element.classList.add('content-fade-in');
            });
        });

        // Gérer les erreurs de chargement
        window.addEventListener('error', function(e) {
            console.error('Erreur de chargement:', e);
            // Masquer le loader en cas d'erreur
            if (window.lailaLoader) {
                window.lailaLoader.hideLoader();
            }
            // Forcer le masquage
            window.forceHideLoader();
        });

        // Améliorer l'expérience utilisateur
        document.addEventListener('DOMContentLoaded', function() {
            // Ajouter des tooltips Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Ajouter des popovers Bootstrap
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        });
        
        // Sécurité supplémentaire : forcer le masquage après 1.5 secondes
        setTimeout(() => {
            if (window.forceHideLoader) {
                window.forceHideLoader();
            }
        }, 1500);
    </script>
</body>
</html> 