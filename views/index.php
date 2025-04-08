<?php
require_once '../includes/db_connect.php';
require_once '../views/layouts/navbar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
</head>
<body>
    <!-- Section Hero -->
    <div class="hero-section">
        <div class="container text-center py-5">
            <h1 class="display-4 fw-bold text-white">
                <i class="bi bi-rocket-takeoff-fill me-2"></i>Laila Workspace
            </h1>
            <p class="lead text-white mb-4">
                Transformez vos idées en Business Model Canvas avec notre générateur IA !
            </p>
            <a href="<?= BASE_URL ?>/views/bmc/generate_bmc.php" class="btn btn-light rounded-3 px-4">
                <i class="bi bi-rocket-takeoff me-2"></i>Commencer maintenant
            </a>
        </div>
    </div>

    <!-- Section Présentation -->
    <div class="container my-5">
        <h2 class="text-center text-primary mb-5 fw-bold">Pourquoi Laila Workspace ?</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card text-center p-4">
                    <i class="bi bi-grid display-5 text-primary mb-3"></i>
                    <h5 class="fw-semibold">Génération de BMP</h5>
                    <p class="text-muted">Créez un Business Model Canvas en quelques clics grâce à notre IA avancée.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center p-4">
                    <i class="bi bi-lightbulb display-5 text-primary mb-3"></i>
                    <h5 class="fw-semibold">Gestion des hypothèses</h5>
                    <p class="text-muted">Suivez et validez vos hypothèses pour affiner votre stratégie.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center p-4">
                    <i class="bi bi-bar-chart display-5 text-primary mb-3"></i>
                    <h5 class="fw-semibold">Analyse et insights</h5>
                    <p class="text-muted">Obtenez des analyses pour prendre des décisions éclairées.</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Section Notre Équipe -->
    <div class="container my-5">
        <h2 class="text-center text-primary mb-5 fw-bold">Notre Équipe</h2>
        <div class="row g-4">
            <!-- Membre 1 : David Ndizeye -->
            <div class="col-md-4">
                <div class="team-card text-center p-4">
                    <div class="user-avatar mb-3 mx-auto">DN</div>
                    <h6 class="fw-semibold">David Ndizeye</h6>
                    <p class="text-muted small mb-2">Fondateur de la Startup</p>
                    <p class="text-muted">
                        David est le visionnaire derrière Laila Workspace, passionné par l'innovation et l'entrepreneuriat.
                    </p>
                </div>
            </div>
            <!-- Membre 2 : Gad Lelo -->
            <div class="col-md-4">
                <div class="team-card text-center p-4">
                    <div class="user-avatar mb-3 mx-auto">GL</div>
                    <h6 class="fw-semibold">Gad Lelo</h6>
                    <p class="text-muted small mb-2">Développeur</p>
                    <p class="text-muted">
                        Gad est un développeur talentueux qui a contribué à la création de l'interface intuitive de Laila Workspace.
                    </p>
                </div>
            </div>
            <!-- Membre 3 : Eliel D. -->
            <div class="col-md-4">
                <div class="team-card text-center p-4">
                    <div class="user-avatar mb-3 mx-auto">ED</div>
                    <h6 class="fw-semibold">Eliel D.</h6>
                    <p class="text-muted small mb-2">Développeur</p>
                    <p class="text-muted">
                        Eliel a travaillé sur les fonctionnalités avancées de l'IA pour rendre Laila Workspace plus intelligent.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include '../views/layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>