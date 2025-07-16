<?php
session_start(); // Ajout de session_start() pour gérer les sessions
require_once '../../includes/db_connect.php';
require_once '../layouts/navbar.php';

// Message de débogage pour confirmer que la page est atteinte
error_log("Utilisateur atteint generate_bmc.php - Session user_id : " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'non connecté'));

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
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/loader.css">
    <style>
        #submit-btn.btn {
            background: linear-gradient(90deg, #007bff 0%, #00c4b4 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            box-shadow: 0 0 0 0 #00c4b4, 0 4px 24px 0 #00c4b480;
            padding: 0.6rem 2.2rem;
            transition: 
                background 0.3s,
                box-shadow 0.3s,
                transform 0.18s;
            position: relative;
            overflow: hidden;
        }
        #submit-btn.btn:hover, #submit-btn.btn:focus {
            background: linear-gradient(90deg, #00c4b4 0%, #007bff 100%);
            box-shadow: 0 0 16px 2px #00c4b480, 0 8px 32px 0 #007bff80;
            transform: translateY(-2px) scale(1.04);
            color: #fff;
        }
        #submit-btn .bi {
            transition: transform 0.4s cubic-bezier(.4,2,.6,1);
        }
        #submit-btn:hover .bi, #submit-btn:focus .bi {
            transform: rotate(-20deg) scale(1.2);
        }
        #submit-btn.btn .bi {
            font-size: 1rem;
            margin-right: 0.5rem;
            vertical-align: -0.1em;
        }
        #submit-btn.btn {
            padding: 0.5rem 1.7rem;
            max-width: 240px;
            font-size: 0.98rem;
            transition: all 0.3s ease;
        }
        
        #submit-btn.btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background: #6c757d !important;
            border-color: #6c757d !important;
        }
        
        #submit-btn.btn:not(:disabled):hover {
            transform: translateY(-2px) scale(1.04);
        }
        /* Amélioration de la zone de texte */
        #prompt.form-control {
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            background: #f8fafc;
            font-size: 1.08rem;
            color: #2d3436;
            min-height: 110px;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        #prompt.form-control:focus {
            border-color: #00c4b4;
            box-shadow: 0 0 0 2px #00c4b455;
            background: #fff;
            color: #222;
        }
        #prompt::placeholder {
            color: #adb5bd;
            opacity: 1;
            font-size: 1rem;
        }
        .bmc-container .row {
            margin-bottom: 2.2rem !important;
            gap: 1.5rem 0 !important;
        }
        .bmc-card {
            margin-bottom: 1.2rem;
            min-height: 200px;
            height: auto;
        }
        .bmc-card h5 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        .bmc-card p {
            font-size: 0.9rem;
            line-height: 1.4;
        }
        .bmc-card .bi {
            font-size: 2.5rem !important;
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
            <div class="col-md-12">
                <div class="card p-4 shadow-sm">
                    <h4 class="text-primary fw-bold mb-4">Décrivez votre projet</h4>
                    <form action="visualisation.php" method="POST" id="bmc-form">
                        <div class="mb-3 position-relative">
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-lightbulb text-primary"></i>
                                </span>
                                <textarea class="form-control" id="prompt" name="prompt" rows="5" required
                                          placeholder="Exemple : Une application similaire à Uber Eats, mais qui distribue avec des conducteurs de motos personnelles…"
                                          aria-label="Description de votre projet"></textarea>
                            </div>
                        </div>
                        <center>
                            <button type="submit" class="btn" id="submit-btn" disabled>
                                Générer mon BMC
                            </button>
                        </center>
                    </form>
                </div>
            </div>
        </div>

        <!-- Section : Conseils pour un bon prompt -->
        <div class="row justify-content-center mb-5">
            <div class="col-md-12">
                <h3 class="text-center text-primary fw-bold mb-4 section-title">Tips pour constituer un bon prompt afin de générer le meilleur BMC pour votre activité</h3>
                <p class="text-center text-muted mb-4">
                    Suivez ces conseils pour optimiser la génération de votre Business Model Canvas :
                </p>
                <div class="card p-4 shadow-sm">
                    <h5 class="fw-bold text-primary">Clarté :</h5>
                    <p class="text-muted">
                        Formulez votre idée de la manière la plus détaillée possible : Nommez le produit ou service, sa nature, le marché cible, le persona, précisez si votre idée ressemble à un business existant, etc.<br>
                    </p>
                </div>
            </div>
        </div>

        <!-- Section exemple : Exemple de Business Model Canvas -->
        <div class="row justify-content-center">
            <div class="col-md-12">
                <h3 class="text-center text-primary fw-bold mb-4 section-title">Exemple de Business Model Canvas</h3>
                <p class="text-center text-muted mb-4">
                    Voici un exemple concret d'un BMC pour une application de livraison de repas utilisant des conducteurs de motos personnelles, similaire à Uber Eats.
                </p>
                <div class="bmc-container">
                    <!-- Ligne 1 -->
                    <div class="row g-2 mb-4">
                        <!-- Partenaires clés -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-people-fill text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Partenaires clés</h5>
                                <p class="text-muted">Restaurants locaux, plateformes de paiement, garages moto pour entretien, assurances pour conducteurs.</p>
                            </div>
                        </div>
                        <!-- Ressources clés -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-tools text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Ressources clés</h5>
                                <p class="text-muted">Application mobile, flotte de conducteurs avec motos personnelles, algorithmes d'optimisation des trajets.</p>
                            </div>
                        </div>
                        <!-- Segments de clientèle -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-person-fill text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Segments de clientèle</h5>
                                <p class="text-muted">Urbains pressés (18-35 ans), étudiants, professionnels cherchant des livraisons rapides en zones congestionnées.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ligne 2 -->
                    <div class="row g-2 mb-4">
                        <!-- Activités clés -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-gear-fill text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Activités clés</h5>
                                <p class="text-muted">Développement de l'application, recrutement et formation des conducteurs moto, marketing digital.</p>
                            </div>
                        </div>
                        <!-- Proposition de valeur -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-star-fill text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Proposition de valeur</h5>
                                <p class="text-muted">Livraisons ultra-rapides en ville grâce à des motos, coûts réduits, accessibilité dans les zones encombrées.</p>
                            </div>
                        </div>
                        <!-- Canaux -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-megaphone-fill text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Canaux</h5>
                                <p class="text-muted">Application mobile, réseaux sociaux (Instagram, TikTok), partenariats avec restaurants populaires.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ligne 3 -->
                    <div class="row g-2 mb-4">
                        <!-- Structure des coûts -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-currency-dollar text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Structure des coûts</h5>
                                <p class="text-muted">Développement de l'application, rémunération des conducteurs, assurance motos, frais de marketing.</p>
                            </div>
                        </div>
                        <!-- Sources de revenus -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-cash-stack text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Sources de revenus</h5>
                                <p class="text-muted">Frais de livraison, commissions sur les commandes, abonnements premium pour clients.</p>
                            </div>
                        </div>
                        <!-- Relations clients -->
                        <div class="col-md-4">
                            <div class="card p-4 shadow-sm text-center bmc-card">
                                <i class="bi bi-heart-fill text-primary mb-3" style="font-size: 2.5rem;"></i>
                                <h5 class="fw-bold">Relations clients</h5>
                                <p class="text-muted">Support via chat dans l'application, programme de fidélité, promotions personnalisées.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/loader.js"></script>
    <script>
        // Gérer l'activation/désactivation du bouton selon le contenu du champ
        const promptField = document.getElementById('prompt');
        const submitBtn = document.getElementById('submit-btn');
        
        function updateButtonState() {
            const hasContent = promptField.value.trim().length > 0;
            submitBtn.disabled = !hasContent;
            
            if (hasContent) {
                submitBtn.classList.remove('btn-secondary');
                submitBtn.classList.add('btn-primary');
            } else {
                submitBtn.classList.remove('btn-primary');
                submitBtn.classList.add('btn-secondary');
            }
        }
        
        // Écouter les changements dans le champ de texte
        promptField.addEventListener('input', updateButtonState);
        promptField.addEventListener('keyup', updateButtonState);
        
        // Initialiser l'état du bouton
        updateButtonState();
        
        // Fonction pour restaurer l'état initial du bouton
        function resetButtonState() {
            submitBtn.textContent = 'Générer mon BMC';
            submitBtn.disabled = promptField.value.trim().length === 0;
            updateButtonState();
        }
        
        // Restaurer l'état initial quand on revient en arrière
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // Page chargée depuis le cache (retour arrière)
                resetButtonState();
            }
        });
        
        // Restaurer l'état initial au chargement de la page
        window.addEventListener('load', function() {
            resetButtonState();
        });
        
        // Gérer la soumission du formulaire
        document.getElementById('bmc-form').addEventListener('submit', function (e) {
            // Vérifier que le champ n'est pas vide
            if (promptField.value.trim().length === 0) {
                e.preventDefault();
                return;
            }
            
            // Afficher le loader global avec un message personnalisé
            if (window.lailaLoader) {
                window.lailaLoader.showLoader('Génération de votre BMC...', 'IA en cours de traitement');
            }
            
            // Changer le texte du bouton
            submitBtn.textContent = 'Génération en cours...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>