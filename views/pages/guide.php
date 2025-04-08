<?php
require_once '../../includes/db_connect.php';
require_once '../../views/layouts/navbar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guide - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="mb-4 fw-bold text-dark">
                Guide de <span class="text-primary">Laila Workspace</span>
            </h1>
            <p class="lead text-muted">
                Apprenez à utiliser notre application étape par étape.
            </p>
        </div>

        <!-- Vidéo ou texte explicatif -->
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
                <li class="list-group-item">Rendez-vous sur la page de création et entrez votre idée ou chanson.</li>
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
                            Notre IA analyse votre idée ou chanson et génère un BMP en identifiant les 9 piliers clés d'un Business Model Canvas.
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
            <a href="<?= BASE_URL ?>/views/index.php" class="btn btn-primary rounded-3 px-4">
                <i class="bi bi-rocket-takeoff me-2"></i>Commencer maintenant
            </a>
        </div>
    </div>

    <?php include '../../views/layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>