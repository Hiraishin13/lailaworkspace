<?php
session_start();

// Activer la gestion des sorties pour éviter les erreurs de redirection
ob_start();

// Définir le chemin de base pour les inclusions
define('BASE_DIR', dirname(__DIR__, 2)); // Remonte de views/bmc/ à la racine du projet (laila_workspace)

require_once BASE_DIR . '/includes/db_connect.php';
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/vendor/autoload.php';

// Message de débogage pour confirmer que la page est atteinte
error_log("Utilisateur atteint visualisation.php - Session user_id : " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'non connecté'));

// Initialiser les variables
$prompt = '';
$generated_bmc = [];
$project_id = null;
$project_name = '';

// Cas 1 : Vérifier si un project_id est fourni via GET (visualisation d'un BMC existant)
if (isset($_GET['project_id']) && !empty($_GET['project_id']) && is_numeric($_GET['project_id'])) {
    $project_id = (int)$_GET['project_id'];

    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/views/auth/login.php');
        exit();
    }

    // Vérifier si le projet appartient à l'utilisateur
    try {
        $stmt = $pdo->prepare("SELECT name, description FROM projects WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $project_id, 'user_id' => $_SESSION['user_id']]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) {
            $_SESSION['error'] = "Projet non trouvé ou accès non autorisé.";
            header('Location: ' . BASE_URL . '/views/visualisation.php');
            exit();
        }

        $prompt = $project['description'];
        $project_name = $project['name'];

        // Récupérer les données du BMC depuis la table bmc
        $stmt = $pdo->prepare("SELECT block_name, content FROM bmc WHERE project_id = :project_id");
        $stmt->execute(['project_id' => $project_id]);
        $bmc_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Vérifier si des données BMC existent
        if (empty($bmc_data)) {
            $_SESSION['error'] = "Aucun BMC n'a été généré pour ce projet.";
            header('Location: ' . BASE_URL . '/views/bmc/generate_bmc.php');
            exit();
        }

        // Initialiser $generated_bmc avec les clés attendues
        $generated_bmc = [
            'segments_clientele' => $bmc_data['segments_clientele'] ?? 'Non spécifié',
            'proposition_valeur' => $bmc_data['proposition_valeur'] ?? 'Non spécifié',
            'canaux' => $bmc_data['canaux'] ?? 'Non spécifié',
            'relations_clients' => $bmc_data['relations_clients'] ?? 'Non spécifié',
            'sources_revenus' => $bmc_data['sources_revenus'] ?? 'Non spécifié',
            'ressources_cles' => $bmc_data['ressources_cles'] ?? 'Non spécifié',
            'activites_cles' => $bmc_data['activites_cles'] ?? 'Non spécifié',
            'partenaires_cles' => $bmc_data['partenaires_cles'] ?? 'Non spécifié',
            'structure_couts' => $bmc_data['structure_couts'] ?? 'Non spécifié'
        ];
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du projet ou du BMC : " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de la récupération des données du projet.";
        header('Location: ' . BASE_URL . '/views/dashboard.php');
        exit();
    }
}
// Cas 2 : Vérifier si les données du BMC sont déjà dans la session (cas où l'utilisateur revient après connexion)
elseif (isset($_SESSION['temp_project'])) {
    // Récupérer les données temporaires
    $prompt = $_SESSION['temp_project']['description'];
    $generated_bmc = $_SESSION['temp_project']['generated_bmc'];
    $project_id = isset($_SESSION['temp_project']['project_id']) ? $_SESSION['temp_project']['project_id'] : 'temp';
    $project_name = $_SESSION['temp_project']['name'];

    // Si l'utilisateur est connecté, les données ont déjà été enregistrées dans process_login.php
    // On peut maintenant supprimer les données temporaires
    if (isset($_SESSION['user_id'])) {
        unset($_SESSION['temp_project']);
    }
}
// Cas 3 : Vérifier si un prompt a été soumis via POST (génération d'un nouveau BMC)
elseif (isset($_POST['prompt']) && !empty(trim($_POST['prompt']))) {
    // Nettoyer et valider le prompt de manière sécurisée
    $prompt = trim($_POST['prompt']);
    if (strlen($prompt) > 1000) { // Limite arbitraire pour éviter les abus
        $_SESSION['error'] = "La description du projet est trop longue (maximum 1000 caractères).";
        header('Location: generate_bmc.php');
        exit();
    }
    $prompt = htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8');

    // Configurer le client OpenAI
    try {
        if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
            throw new Exception('Clé API OpenAI non configurée.');
        }
        $client = \OpenAI::client(OPENAI_API_KEY);
    } catch (Exception $e) {
        error_log('Erreur de configuration de l\'API OpenAI : ' . $e->getMessage());
        $_SESSION['error'] = "Erreur de configuration de l'API OpenAI.";
        header('Location: generate_bmc.php');
        exit();
    }

    // Créer le prompt pour l'API avec un format strict
    $api_prompt = <<<EOD
Génère un Business Model Canvas (BMC) pour le projet suivant : "{$prompt}".
Fournis les 9 piliers du BMC dans ce format exact, avec chaque pilier sur une nouvelle ligne et commençant par un tiret, suivi du nom du pilier, suivi de deux points et d'un espace, puis la description :
- Segments de clientèle : [description]
- Proposition de valeur : [description]
- Canaux : [description]
- Relations clients : [description]
- Sources de revenus : [description]
- Ressources clés : [description]
- Activités clés : [description]
- Partenaires clés : [description]
- Structure des coûts : [description]
Assure-toi que chaque pilier est présent et suit exactement ce format.
EOD;

    // Appeler l'API d'OpenAI pour générer le BMC
    try {
        error_log("Utilisation du modèle : gpt-3.5-turbo"); // Log pour confirmer le modèle utilisé
        $response = $client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => $api_prompt],
            ],
            'max_tokens' => 1000,
            'temperature' => 0.7,
        ]);

        // Vérifier si la réponse contient du contenu
        if (!isset($response->choices[0]->message->content)) {
            throw new Exception('Réponse vide ou invalide de l\'API OpenAI');
        }

        $generated_text = $response->choices[0]->message->content;

        // Débogage : Enregistrer la réponse brute de l'API
        $log_dir = BASE_DIR . '/logs';
        $log_file = $log_dir . '/api_response.txt';

        // Créer le répertoire logs s'il n'existe pas
        if (!is_dir($log_dir)) {
            if (!mkdir($log_dir, 0777, true)) {
                error_log("Impossible de créer le répertoire $log_dir");
            }
        }

        // Écrire dans le fichier uniquement si le répertoire existe et est accessible
        if (is_dir($log_dir) && is_writable($log_dir)) {
            if (file_put_contents($log_file, "Réponse API (modèle: gpt-3.5-turbo):\n" . $generated_text) === false) {
                error_log("Impossible d'écrire dans $log_file");
            }
        }

        // Parser la réponse pour extraire les 9 piliers
        $generated_bmc = [
            'segments_clientele' => '',
            'proposition_valeur' => '',
            'canaux' => '',
            'relations_clients' => '',
            'sources_revenus' => '',
            'ressources_cles' => '',
            'activites_cles' => '',
            'partenaires_cles' => '',
            'structure_couts' => ''
        ];

        // Expressions régulières pour extraire les piliers (plus robustes)
        $patterns = [
            'segments_clientele' => '/- Segments de clientèle\s*:\s*(.*?)(?=\n\s*-|$)/is',
            'proposition_valeur' => '/- Proposition de valeur\s*:\s*(.*?)(?=\n\s*-|$)/is',
            'canaux' => '/- Canaux\s*:\s*(.*?)(?=\n\s*-|$)/is',
            'relations_clients' => '/- Relations clients\s*:\s*(.*?)(?=\n\s*-|$)/is',
            'sources_revenus' => '/- Sources de revenus\s*:\s*(.*?)(?=\n\s*-|$)/is',
            'ressources_cles' => '/- Ressources clés\s*:\s*(.*?)(?=\n\s*-|$)/is',
            'activites_cles' => '/- Activités clés\s*:\s*(.*?)(?=\n\s*-|$)/is',
            'partenaires_cles' => '/- Partenaires clés\s*:\s*(.*?)(?=\n\s*-|$)/is',
            'structure_couts' => '/- Structure des coûts\s*:\s*(.*?)(?=\n\s*-|$)/is'
        ];

        // Compteur pour vérifier combien de blocs ont été remplis
        $filled_blocks = 0;

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $generated_text, $matches)) {
                $generated_bmc[$key] = trim($matches[1]);
                $filled_blocks++;
                error_log("Bloc '$key' rempli avec : " . $generated_bmc[$key]);
            } else {
                $generated_bmc[$key] = "Non spécifié";
                error_log("Échec de parsing pour le bloc '$key'");
            }
        }

        // Vérifier si aucun bloc n'a été rempli correctement
        if ($filled_blocks === 0) {
            error_log("Aucun bloc BMC n'a pu être extrait. Réponse API brute : " . $generated_text);
            $_SESSION['error'] = "Erreur lors de l'extraction des blocs BMC. La réponse de l'API est mal formée.";
            header('Location: generate_bmc.php');
            exit();
        }

    } catch (\OpenAI\Exceptions\ErrorException $e) {
        // Capturer les erreurs spécifiques d'OpenAI
        error_log("Erreur OpenAI : " . $e->getMessage() . " | Code : " . $e->getCode());
        $_SESSION['error'] = "Erreur lors de la génération du BMC.";
        header('Location: generate_bmc.php');
        exit();
    } catch (Exception $e) {
        // Capturer toute autre erreur
        error_log("Erreur générale : " . $e->getMessage());
        $_SESSION['error'] = "Erreur inattendue lors de la génération du BMC.";
        header('Location: generate_bmc.php');
        exit();
    }

    // Enregistrer le projet dans la table `projects` si l'utilisateur est connecté
    $project_id = 'temp'; // Par défaut, identifiant temporaire
    if (isset($_SESSION['user_id'])) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO projects (user_id, name, description) VALUES (:user_id, :name, :description)");
            $stmt->execute([
                'user_id' => $_SESSION['user_id'],
                'name' => "BMC généré pour : " . substr($prompt, 0, 50),
                'description' => $prompt
            ]);
            $project_id = $pdo->lastInsertId();
            $project_name = "BMC généré pour : " . substr($prompt, 0, 50);

            // Enregistrer les 9 piliers dans la table `bmc` dans une transaction
            $stmt = $pdo->prepare("INSERT INTO bmc (project_id, block_name, content) VALUES (:project_id, :block_name, :content)");
            $blocks = [
                'partenaires_cles' => $generated_bmc['partenaires_cles'],
                'ressources_cles' => $generated_bmc['ressources_cles'],
                'segments_clientele' => $generated_bmc['segments_clientele'],
                'activites_cles' => $generated_bmc['activites_cles'],
                'proposition_valeur' => $generated_bmc['proposition_valeur'],
                'canaux' => $generated_bmc['canaux'],
                'structure_couts' => $generated_bmc['structure_couts'],
                'sources_revenus' => $generated_bmc['sources_revenus'],
                'relations_clients' => $generated_bmc['relations_clients']
            ];

            foreach ($blocks as $block_name => $content) {
                $stmt->execute([
                    'project_id' => $project_id,
                    'block_name' => $block_name,
                    'content' => $content
                ]);
            }
            $pdo->commit();
            
            // Créer des notifications après la création réussie du projet
            try {
                require_once BASE_DIR . '/models/Notification.php';
                require_once BASE_DIR . '/models/Achievement.php';
                
                $notification = new Notification($pdo);
                $achievement = new Achievement($pdo);
                
                // Vérifier si c'est le premier BMC de l'utilisateur
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM projects WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] == 1) {
                    // Premier BMC - notification de bienvenue
                    $notification->createWelcomeNotification($_SESSION['user_id']);
                    
                    // Débloquer l'achievement
                    $achievement->unlock($_SESSION['user_id'], 'first_bmc', 'Premier BMC', 'Vous avez créé votre premier Business Model Canvas', 'bi-rocket-takeoff', 100);
                }
                
                // Vérifier et débloquer d'autres achievements
                $achievement->checkAndUnlockAchievements($_SESSION['user_id']);
                
            } catch (Exception $e) {
                // Ignorer les erreurs de notifications
                error_log('Erreur notification BMC: ' . $e->getMessage());
            }
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Erreur lors de l\'enregistrement du projet ou des blocs BMC : ' . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de l'enregistrement du projet.";
            header('Location: generate_bmc.php');
            exit();
        }
    } else {
        // Si l'utilisateur n'est pas connecté, stocker les données temporairement dans la session
        $_SESSION['temp_project'] = [
            'name' => "BMC généré pour : " . substr($prompt, 0, 50),
            'description' => $prompt,
            'generated_bmc' => $generated_bmc
        ];
        // Stocker l'URL de redirection pour après la connexion
        $_SESSION['redirect_after_login'] = BASE_URL . '/views/bmc/visualisation.php';
        // Message de débogage pour confirmer que les données temporaires sont stockées
        error_log("Données temporaires stockées dans la session : " . print_r($_SESSION['temp_project'], true));
    }
}
// Cas 4 : Aucun project_id ni prompt ni données temporaires
else {
    $_SESSION['error'] = "Aucune donnée disponible pour afficher le BMC.";
    header('Location: ' . BASE_URL . '/views/dashboard.php');
    exit();
}

// Si l'utilisateur n'est pas connecté et qu'il n'y a pas de données temporaires, rediriger immédiatement
if (!isset($_SESSION['user_id']) && !isset($_SESSION['temp_project'])) {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

// Redirection côté serveur pour les utilisateurs non connectés après 5 secondes
if (!isset($_SESSION['user_id'])) {
    header('Refresh: 5; url=' . BASE_URL . '/views/auth/login.php');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualisation du Business Model Canvas - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL) ?>/assets/css/styles.css">
    <!-- Suppression du style local de fond violet, on garde le fond global -->
</head>
<body>
    <?php include '../layouts/navbar.php'; ?>

    <div class="container my-5">
        <!-- Afficher les messages d'erreur -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Afficher les messages de succès -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Afficher un message pour les utilisateurs non connectés -->
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="alert alert-info text-center" role="alert">
                Vous visualisez un aperçu de votre BMC. Vous serez redirigé vers la page de connexion dans <span id="timer">5</span> secondes pour sauvegarder votre travail.
            </div>
        <?php endif; ?>


        <h2 class="text-center text-primary mb-5 fw-bold"><?= htmlspecialchars($project_name) ? htmlspecialchars($project_name) : 'Votre Business Model Canvas' ?></h2>

        <!-- Afficher le prompt de l'utilisateur -->
        <div class="row justify-content-center mb-5">
            <div class="col-md-10">
                <div class="card p-4 shadow-sm">
                    <h4 class="text-primary fw-bold mb-3">Votre idée</h4>
                    <p class="text-muted"><?= htmlspecialchars($prompt) ?></p>
                </div>
            </div>
        </div>

        <!-- Afficher le BMC généré -->
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="bmc-container">
                    <!-- Ligne 1 -->
                    <div class="row g-3 mb-3">
                        <!-- Proposition de valeur -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div class="card-body">
                                    <i class="bi bi-star-fill text-primary mb-3"></i>
                                    <h5 class="fw-bold">Proposition de valeur</h5>
                                    <p class="text-muted bmc-content" data-block="proposition_valeur"><?= htmlspecialchars($generated_bmc['proposition_valeur']) ?></p>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <div class="edit-btn-container">
                                            <button class="btn btn-primary edit-btn-main edit-block-btn"
                                                    data-block="proposition_valeur"
                                                    data-project-id="<?= htmlspecialchars($project_id) ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editBlockModal">
                                                Éditer
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Segments de clientèle -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div class="card-body">
                                    <i class="bi bi-person-fill text-primary mb-3"></i>
                                    <h5 class="fw-bold">Segments de clientèle</h5>
                                    <p class="text-muted bmc-content" data-block="segments_clientele"><?= htmlspecialchars($generated_bmc['segments_clientele']) ?></p>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <div class="edit-btn-container">
                                            <button class="btn btn-primary edit-btn-main edit-block-btn"
                                                    data-block="segments_clientele"
                                                    data-project-id="<?= htmlspecialchars($project_id) ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editBlockModal">
                                                Éditer
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Canaux -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div class="card-body">
                                    <i class="bi bi-megaphone-fill text-primary mb-3"></i>
                                    <h5 class="fw-bold">Canaux</h5>
                                    <p class="text-muted bmc-content" data-block="canaux"><?= htmlspecialchars($generated_bmc['canaux']) ?></p>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <div class="edit-btn-container">
                                            <button class="btn btn-primary edit-btn-main edit-block-btn"
                                                    data-block="canaux"
                                                    data-project-id="<?= htmlspecialchars($project_id) ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editBlockModal">
                                                Éditer
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ligne 2 -->
                    <div class="row g-3 mb-3">
                        <!-- Relations clients -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div class="card-body">
                                    <i class="bi bi-heart-fill text-primary mb-3"></i>
                                    <h5 class="fw-bold">Relations clients</h5>
                                    <p class="text-muted bmc-content" data-block="relations_clients"><?= htmlspecialchars($generated_bmc['relations_clients']) ?></p>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <div class="edit-btn-container">
                                            <button class="btn btn-primary edit-btn-main edit-block-btn"
                                                    data-block="relations_clients"
                                                    data-project-id="<?= htmlspecialchars($project_id) ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editBlockModal">
                                                Éditer
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Activités clés -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div class="card-body">
                                    <i class="bi bi-gear-fill text-primary mb-3"></i>
                                    <h5 class="fw-bold">Activités clés</h5>
                                    <p class="text-muted bmc-content" data-block="activites_cles"><?= htmlspecialchars($generated_bmc['activites_cles']) ?></p>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <div class="edit-btn-container">
                                            <button class="btn btn-primary edit-btn-main edit-block-btn"
                                                    data-block="activites_cles"
                                                    data-project-id="<?= htmlspecialchars($project_id) ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editBlockModal">
                                                Éditer
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Ressources clés -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div class="card-body">
                                    <i class="bi bi-tools text-primary mb-3"></i>
                                    <h5 class="fw-bold">Ressources clés</h5>
                                    <p class="text-muted bmc-content" data-block="ressources_cles"><?= htmlspecialchars($generated_bmc['ressources_cles']) ?></p>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <div class="edit-btn-container">
                                            <button class="btn btn-primary edit-btn-main edit-block-btn"
                                                    data-block="ressources_cles"
                                                    data-project-id="<?= htmlspecialchars($project_id) ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editBlockModal">
                                                Éditer
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ligne 3 -->
                    <div class="row g-3">
                        <!-- Partenaires clés -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div class="card-body">
                                    <i class="bi bi-people-fill text-primary mb-3"></i>
                                    <h5 class="fw-bold">Partenaires clés</h5>
                                    <p class="text-muted bmc-content" data-block="partenaires_cles"><?= htmlspecialchars($generated_bmc['partenaires_cles']) ?></p>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <div class="edit-btn-container">
                                            <button class="btn btn-primary edit-btn-main edit-block-btn"
                                                    data-block="partenaires_cles"
                                                    data-project-id="<?= htmlspecialchars($project_id) ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editBlockModal">
                                                Éditer
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Structure des coûts -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div class="card-body">
                                    <i class="bi bi-currency-dollar text-primary mb-3"></i>
                                    <h5 class="fw-bold">Structure des coûts</h5>
                                    <p class="text-muted bmc-content" data-block="structure_couts"><?= htmlspecialchars($generated_bmc['structure_couts']) ?></p>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <div class="edit-btn-container">
                                            <button class="btn btn-primary edit-btn-main edit-block-btn"
                                                    data-block="structure_couts"
                                                    data-project-id="<?= htmlspecialchars($project_id) ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editBlockModal">
                                                Éditer
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Sources de revenus -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div class="card-body">
                                    <i class="bi bi-cash-stack text-primary mb-3"></i>
                                    <h5 class="fw-bold">Sources de revenus</h5>
                                    <p class="text-muted bmc-content" data-block="sources_revenus"><?= htmlspecialchars($generated_bmc['sources_revenus']) ?></p>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <div class="edit-btn-container">
                                            <button class="btn btn-primary edit-btn-main edit-block-btn"
                                                    data-block="sources_revenus"
                                                    data-project-id="<?= htmlspecialchars($project_id) ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editBlockModal">
                                                Éditer
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="visualisation-actions mt-4">
                    <a href="download_pdf.php?project_id=<?= htmlspecialchars($project_id) ?>"
                       class="btn btn-primary <?= !isset($_SESSION['user_id']) ? 'disabled' : '' ?>"
                       <?= !isset($_SESSION['user_id']) ? 'disabled' : '' ?>>Télécharger en PDF</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="hypotheses.php?project_id=<?= htmlspecialchars($project_id) ?>&generate=1"
                           class="btn btn-primary generate-hypotheses-btn">Analyser mon BMC</a>
                    <?php else: ?>
                        <a href="#" class="btn btn-primary disabled" disabled>Générer des hypothèses</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale pour éditer un bloc du BMC (uniquement pour les utilisateurs connectés) -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="modal fade" id="editBlockModal" tabindex="-1" aria-labelledby="editBlockModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editBlockModalLabel">Éditer un bloc</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editBlockForm" action="<?= htmlspecialchars(BASE_URL) ?>/views/bmc/update_bmc_block.php" method="post">
                            <input type="hidden" id="project_id" name="project_id">
                            <input type="hidden" id="block_name" name="block_name">
                            <div class="mb-3">
                                <label for="block_content" class="form-label">Contenu du bloc</label>
                                <textarea class="form-control" id="block_content" name="block_content" rows="5" maxlength="2000" required></textarea>
                                <small class="form-text text-muted">Maximum 2000 caractères.</small>
                            </div>
                            <div id="error-message" class="text-danger mb-3" style="display: none;"></div>
                            <div class="d-flex justify-content-between gap-2">
                                <button type="button" class="btn btn-secondary w-50" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary w-50" id="save-block-btn">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modale pour l'animation de chargement -->
        <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-sm">
                    <div class="modal-body text-center">
                        <div class="spinner-container">
                            <div class="spinner-custom"></div>
                            <p class="loading-text" id="loading-text">Enregistrement en cours...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php include '../layouts/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        console.log('Document prêt - jQuery chargé');

        // Timer de redirection pour les utilisateurs non connectés
        <?php if (!isset($_SESSION['user_id'])): ?>
            console.log('Utilisateur non connecté - Début du timer de redirection');
            let timeLeft = 5;
            const timerElement = document.getElementById('timer');
            const timer = setInterval(() => {
                timeLeft--;
                timerElement.textContent = timeLeft;
                console.log('Temps restant : ' + timeLeft + ' secondes');
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    console.log('Redirection vers login.php');
                    window.location.href = '<?= htmlspecialchars(BASE_URL) ?>/views/auth/login.php';
                }
            }, 1000);
        <?php endif; ?>

        // Gestion de l'édition des blocs du BMC (uniquement pour les utilisateurs connectés)
        <?php if (isset($_SESSION['user_id'])): ?>
            $('.edit-block-btn').on('click', function() {
                const blockName = $(this).data('block');
                const projectId = $(this).data('project-id');
                const blockContent = $(this).closest('.bmc-card').find('.bmc-content').text().trim();

                console.log('Ouverture de la modale pour éditer :', blockName, 'Project ID:', projectId, 'Contenu actuel:', blockContent);

                $('#editBlockModalLabel').text('Éditer : ' + blockName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()));
                $('#project_id').val(projectId);
                $('#block_name').val(blockName);
                $('#block_content').val(blockContent);
                $('#error-message').hide(); // Cacher les messages d'erreur précédents
            });

            // Validation côté client et animation de chargement pour la soumission du formulaire
            $('#editBlockForm').on('submit', function(e) {
                const blockContent = $('#block_content').val().trim();
                if (!blockContent) {
                    e.preventDefault();
                    $('#error-message').text('Le contenu du bloc ne peut pas être vide.').show();
                    return false;
                }
                if (blockContent.length > 2000) {
                    e.preventDefault();
                    $('#error-message').text('Le contenu est trop long (maximum 2000 caractères).').show();
                    return false;
                }

                // Cacher la modale d'édition
                $('#editBlockModal').modal('hide');

                // Mettre à jour le texte de l'animation
                $('#loading-text').text('Enregistrement en cours...');

                // Afficher l'animation de chargement
                $('#loadingModal').modal('show');

                // Ajouter un délai artificiel pour que l'animation soit visible
                setTimeout(() => {
                    // Soumettre le formulaire après le délai
                    this.submit();
                }, 1500); // 1,5 seconde pour l'animation

                // Empêcher la soumission immédiate
                return false;
            });

            // Animation de chargement pour le bouton "Générer des hypothèses"
            $('.generate-hypotheses-btn').on('click', function(e) {
                e.preventDefault(); // Empêcher la redirection immédiate
                const href = $(this).attr('href');

                // Mettre à jour le texte de l'animation
                $('#loading-text').text('Génération en cours...');

                // Afficher l'animation de chargement
                $('#loadingModal').modal('show');

                // Rediriger après un délai pour que l'animation soit visible
                setTimeout(() => {
                    window.location.href = href;
                }, 1500); // 1,5 seconde pour l'animation
            });
        <?php endif; ?>
    });
    </script>
</body>
</html>

<?php
// Libérer le buffer de sortie
ob_end_flush();
?>