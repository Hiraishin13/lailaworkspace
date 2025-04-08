<?php
require_once '../../includes/db_connect.php';
require_once '../layouts/navbar.php';

// Vérifier si l'utilisateur est connecté (cette vérification est déjà dans navbar.php, mais on peut la renforcer ici si nécessaire)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Vérifier si un message de bienvenue ou de connexion réussie doit être affiché
$show_welcome = isset($_GET['welcome']) && $_GET['welcome'] === 'true';
$show_login_success = isset($_GET['login_success']) && $_GET['login_success'] === 'true';

// Vérifier si un message d'erreur est présent dans la session
$show_error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
unset($_SESSION['error']); // Effacer le message d'erreur après l'affichage
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générer un Business Model Canvas - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
    <style>
        /* Style pour le loader */
        .loader-container {
            display: none; /* Caché par défaut */
            text-align: center;
            margin-top: 10px;
        }
        .loader-container.show {
            display: block; /* Affiché lorsque la classe "show" est ajoutée */
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <!-- Titre principal -->
        <h2 class="text-center text-primary mb-5 fw-bold">Générer un Business Model Canvas</h2>

        <!-- Message de bienvenue ou de connexion réussie -->
        <?php if ($show_welcome): ?>
            <div class="alert alert-success text-center" role="alert">
                Bienvenue sur Laila Workspace ! Vous pouvez maintenant créer votre Business Model Canvas.
            </div>
        <?php elseif ($show_login_success): ?>
            <div class="alert alert-success text-center" role="alert">
                Connexion réussie ! Vous êtes prêt à créer votre Business Model Canvas.
            </div>
        <?php endif; ?>

        <!-- Message d'erreur -->
        <?php if ($show_error): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?= htmlspecialchars($show_error) ?>
            </div>
        <?php endif; ?>

        <!-- Espace de texte pour le prompt -->
        <div class="row justify-content-center mb-5">
            <div class="col-md-8">
                <div class="card p-4 shadow-sm">
                    <h4 class="text-primary fw-bold mb-4">Décrivez votre projet</h4>
                    <form action="visualisation.php" method="POST" id="bmc-form">
                        <div class="mb-3 position-relative">
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-lightbulb text-primary"></i>
                                </span>
                                <textarea class="form-control" id="prompt" name="prompt" rows="5" required
                                          placeholder="Exemple : Je veux créer une application de livraison de repas pour les étudiants dans les grandes villes..."
                                          aria-label="Description de votre projet"></textarea>
                            </div>
                            <small class="form-text text-muted">
                                Décrivez votre idée ou projet en quelques phrases. Soyez aussi précis que possible pour obtenir un BMC adapté.
                            </small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="submit-btn">Générer mon Business Model Canvas</button>
                        <!-- Loader -->
                        <div class="loader-container" id="loader">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="text-muted mt-2">Génération en cours, veuillez patienter...</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Section exemple : Exemple de Business Model Canvas -->
        <div class="row justify-content-center">
            <div class="col-md-12">
                <h3 class="text-center text-primary fw-bold mb-4 section-title">Exemple de Business Model Canvas</h3>
                <p class="text-center text-muted mb-4">
                    Voici un exemple concret d'un BMC pour une application de livraison de repas pour étudiants.
                </p>
                <div class="bmc-container">
                    <!-- Ligne 1 -->
                    <div class="row g-2 mb-2">
                        <!-- Partenaires clés -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-people-fill text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Partenaires clés</h5>
                                <p class="text-muted">Restaurants locaux, plateformes de paiement, universités pour promotions.</p>
                            </div>
                        </div>
                        <!-- Ressources clés -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-tools text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Ressources clés</h5>
                                <p class="text-muted">Application mobile, réseau de livreurs, partenariats avec restaurants locaux.</p>
                            </div>
                        </div>
                        <!-- Segments de clientèle -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-person-fill text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Segments de clientèle</h5>
                                <p class="text-muted">Étudiants dans les grandes villes, âgés de 18 à 25 ans, cherchant des repas abordables et rapides.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ligne 2 -->
                    <div class="row g-2 mb-2">
                        <!-- Activités clés -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-gear-fill text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Activités clés</h5>
                                <p class="text-muted">Développement et maintenance de l'application, gestion des livraisons, marketing ciblé.</p>
                            </div>
                        </div>
                        <!-- Proposition de valeur -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-star-fill text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Proposition de valeur</h5>
                                <p class="text-muted">Livraison rapide et économique de repas adaptés aux budgets et goûts des étudiants.</p>
                            </div>
                        </div>
                        <!-- Canaux -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-megaphone-fill text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Canaux</h5>
                                <p class="text-muted">Application mobile, réseaux sociaux (Instagram, TikTok), partenariats avec universités.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ligne 3 -->
                    <div class="row g-2">
                        <!-- Structure des coûts -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-currency-dollar text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Structure des coûts</h5>
                                <p class="text-muted">Développement de l'application, salaires des livreurs, frais de marketing, frais opérationnels.</p>
                            </div>
                        </div>
                        <!-- Sources de revenus -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-cash-stack text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Sources de revenus</h5>
                                <p class="text-muted">Frais de livraison, commissions sur les commandes, partenariats publicitaires.</p>
                            </div>
                        </div>
                        <!-- Relations clients -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-heart-fill text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Relations clients</h5>
                                <p class="text-muted">Support client via chat, promotions régulières, programme de fidélité.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion de l'affichage du loader lors de la soumission du formulaire
        document.getElementById('bmc-form').addEventListener('submit', function (e) {
            // Afficher le loader
            document.getElementById('loader').classList.add('show');
            // Désactiver le bouton pour éviter les soumissions multiples
            document.getElementById('submit-btn').disabled = true;
            document.getElementById('submit-btn').innerText = 'Génération en cours...';
        });
    </script>
</body>
</html>