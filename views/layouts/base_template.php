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
    
    <!-- Scripts supplémentaires spécifiques à la page -->
    <?php if ($additional_js): ?>
        <?= $additional_js ?>
    <?php endif; ?>

    <!-- Script global pour la cohérence -->
    <script>
        // Masquer le loader initial après le chargement
        document.addEventListener('DOMContentLoaded', function() {
            // Masquer le loader global après un court délai
            setTimeout(() => {
                const globalLoader = document.getElementById('global-loader');
                if (globalLoader) {
                    globalLoader.classList.add('hidden');
                }
                
                // S'assurer que le loader JavaScript se masque aussi
                if (window.lailaLoader) {
                    window.lailaLoader.hideLoader();
                }
            }, 1000);
            
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
    </script>
</body>
</html> 