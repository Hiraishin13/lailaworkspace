<?php
require_once '../../includes/db_connect.php';
require_once '../../views/layouts/navbar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guide d'utilisation - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="mb-4 fw-bold text-dark">
                Guide de <span class="text-primary">Laila Workspace</span>
            </h1>
            <p class="lead text-muted">
                Découvrez comment utiliser chaque bouton et chaque fonctionnalité de la plateforme pour tirer le meilleur parti de Laila Workspace.
            </p>
        </div>

        <!-- Navigation détaillée -->
        <div class="card p-4 mb-5">
            <h3 class="text-primary mb-4"><i class="bi bi-compass me-2"></i>Navigation de la plateforme</h3>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="d-flex align-items-start mb-3">
                        <i class="bi bi-house-door fs-2 text-primary me-3"></i>
                        <div>
                            <strong>Accueil</strong><br>
                            Accédez à la page d'accueil pour voir la présentation générale de la plateforme et les dernières nouveautés.
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <i class="bi bi-grid fs-2 text-primary me-3"></i>
                        <div>
                            <strong>Business Model</strong><br>
                            Lancez la création ou la gestion de vos Business Model Canvas (BMC) grâce à l'IA.
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <i class="bi bi-book fs-2 text-primary me-3"></i>
                        <div>
                            <strong>Guide</strong><br>
                            Accédez à cette page d'aide détaillée pour comprendre chaque fonctionnalité de la plateforme.
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <i class="bi bi-file-text fs-2 text-primary me-3"></i>
                        <div>
                            <strong>Conditions</strong><br>
                            Consultez les conditions d'utilisation et la politique de confidentialité.
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="d-flex align-items-start mb-3">
                        <i class="bi bi-person-circle fs-2 text-primary me-3"></i>
                        <div>
                            <strong>Profil</strong><br>
                            Gérez vos informations personnelles et vos paramètres de compte.
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <i class="bi bi-plus-circle fs-2 text-primary me-3"></i>
                        <div>
                            <strong>Nouveau BMC</strong><br>
                            Créez un nouveau Business Model Canvas pour un projet.
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <i class="bi bi-box-arrow-right fs-2 text-primary me-3"></i>
                        <div>
                            <strong>Déconnexion</strong><br>
                            Déconnectez-vous de votre compte en toute sécurité.
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="d-flex align-items-start mb-3">
                        <i class="bi bi-box-arrow-in-right fs-2 text-primary me-3"></i>
                        <div>
                            <strong>Connexion</strong><br>
                            Accédez à votre compte pour vous authentifier.
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <i class="bi bi-person-plus fs-2 text-primary me-3"></i>
                        <div>
                            <strong>Inscription</strong><br>
                            Créez un nouveau compte pour profiter de toutes les fonctionnalités.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fonctionnalités principales -->
        <div class="card p-4 mb-5">
            <h3 class="text-primary mb-4"><i class="bi bi-stars me-2"></i>Fonctionnalités principales</h3>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <strong>Génération automatique de Business Model Canvas (BMC)</strong> : Saisissez votre idée, laissez l'IA générer un BMC structuré et modifiez chaque pilier selon vos besoins.
                </li>
                <li class="list-group-item">
                    <strong>Gestion des hypothèses</strong> : Ajoutez, modifiez et validez les hypothèses clés de votre projet pour affiner votre stratégie.
                </li>
                <li class="list-group-item">
                    <strong>Analyse de compatibilité et suggestions de partenariats</strong> : Découvrez des synergies potentielles avec d'autres projets.
                </li>
                <li class="list-group-item">
                    <strong>Export PDF</strong> : Exportez votre BMC ou vos analyses en PDF professionnel pour les partager ou les présenter.
                </li>
                <li class="list-group-item">
                    <strong>Notifications intelligentes</strong> : Recevez des alertes sur les changements importants, suggestions ou actions à réaliser.
                </li>
                <li class="list-group-item">
                    <strong>Gestion de profil</strong> : Mettez à jour vos informations personnelles et suivez l'historique de vos projets.
                </li>
            </ul>
        </div>

        <!-- Tutoriel vidéo -->
        <div class="card p-4 mb-5">
            <h3 class="text-primary mb-3">Tutoriel vidéo</h3>
            <div class="ratio ratio-16x9">
                <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Tutoriel Laila Workspace" allowfullscreen></iframe>
            </div>
        </div>

        <!-- Étapes détaillées -->
        <div class="mb-5">
            <h3 class="text-primary mb-3">Étapes pour créer un BMP</h3>
            <ol class="list-group list-group-numbered">
                <li class="list-group-item">Rendez-vous sur la page de création et entrez votre idée ou projet.</li>
                <li class="list-group-item">Laissez notre IA générer un BMP structuré.</li>
                <li class="list-group-item">Modifiez les piliers pour personnaliser votre BMP.</li>
                <li class="list-group-item">Gérez les hypothèses pour valider votre stratégie.</li>
                <li class="list-group-item">Exportez votre BMP final en PDF ou partagez-le.</li>
            </ol>
        </div>

        <!-- FAQ -->
        <div class="mb-5">
            <h3 class="text-primary mb-3">Foire aux questions</h3>
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading1">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1">
                            Comment fonctionne l'IA ?
                        </button>
                    </h2>
                    <div id="faqCollapse1" class="accordion-collapse collapse show" aria-labelledby="faqHeading1" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Notre IA analyse votre idée ou projet et génère un BMP en identifiant les 9 piliers clés d'un Business Model Canvas.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                            Puis-je modifier mon BMP ?
                        </button>
                    </h2>
                    <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Oui, après la génération, vous pouvez modifier chaque pilier pour personnaliser votre BMP.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="<?= BASE_URL ?>/views/index.php" class="btn btn-primary px-4">
                <i class="bi bi-rocket-takeoff me-2"></i>Commencer maintenant
            </a>
        </div>
    </div>

    <?php include '../../views/layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>