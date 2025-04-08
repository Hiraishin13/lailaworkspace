<?php
session_start();

// Définir le chemin de base pour les inclusions
define('BASE_DIR', dirname(__DIR__, 2)); // Remonte de views/bmc/ à la racine du projet (laila_workspace)

require_once BASE_DIR . '/includes/db_connect.php';
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/vendor/autoload.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

// Vérifier si un project_id est fourni
if (!isset($_GET['project_id']) || !is_numeric($_GET['project_id'])) {
    $_SESSION['error'] = "Projet non spécifié.";
    header('Location: ../dashboard.php');
    exit();
}

$project_id = (int)$_GET['project_id'];

// Vérifier si le projet appartient à l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT name, description FROM projects WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $project_id, 'user_id' => $_SESSION['user_id']]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        $_SESSION['error'] = "Projet non trouvé ou accès non autorisé.";
        header('Location: ../dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération du projet : " . $e->getMessage();
    header('Location: ../dashboard.php');
    exit();
}

// Génération des hypothèses si le paramètre "generate" est présent
if (isset($_GET['generate']) && $_GET['generate'] == 1) {
    // Configurer le client OpenAI
    try {
        if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
            throw new Exception('Clé API OpenAI non configurée.');
        }
        $client = \OpenAI::client(OPENAI_API_KEY);
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur de configuration de l'API OpenAI : " . $e->getMessage();
        header("Location: hypotheses.php?project_id=$project_id");
        exit();
    }

    // Créer le prompt pour générer des hypothèses
    $api_prompt = "Génère 4 à 5 hypothèses testables pour le Business Model Canvas du projet suivant : " . $project['description'] . ". Chaque hypothèse doit être concise, claire et commencer par 'Nous supposons que'. Par exemple : 'Nous supposons que les clients seront prêts à payer 10€ par mois pour ce service.' Fournis les hypothèses sous forme de liste, une par ligne.";

    try {
        $response = $client->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'Tu es un expert en création de Business Model Canvas et en génération d\'hypothèses testables.'],
                ['role' => 'user', 'content' => $api_prompt],
            ],
            'max_tokens' => 500,
            'temperature' => 0.7,
        ]);

        $generated_text = $response->choices[0]->message->content;
        $hypotheses = array_filter(array_map('trim', explode("\n", $generated_text)));

        // Enregistrer les hypothèses dans la base de données
        $stmt = $pdo->prepare("INSERT INTO hypotheses (project_id, hypothesis_text, status) VALUES (:project_id, :hypothesis_text, 'pending')");
        foreach ($hypotheses as $hypothesis) {
            if (!empty($hypothesis)) {
                $stmt->execute([
                    'project_id' => $project_id,
                    'hypothesis_text' => $hypothesis
                ]);
            }
        }

        // Rediriger pour éviter de regénérer à chaque rechargement
        header("Location: hypotheses.php?project_id=$project_id");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la génération des hypothèses : " . $e->getMessage();
        header("Location: hypotheses.php?project_id=$project_id");
        exit();
    }
}

// Ajout manuel d'une hypothèse (non utilisé car géré via AJAX maintenant)
if (isset($_POST['add_hypothesis']) && !empty(trim($_POST['hypothesis_text']))) {
    $hypothesis_text = htmlspecialchars(trim($_POST['hypothesis_text']), ENT_QUOTES, 'UTF-8');
    try {
        $stmt = $pdo->prepare("INSERT INTO hypotheses (project_id, hypothesis_text, status) VALUES (:project_id, :hypothesis_text, 'pending')");
        $stmt->execute([
            'project_id' => $project_id,
            'hypothesis_text' => $hypothesis_text
        ]);
        $_SESSION['success'] = "Hypothèse ajoutée avec succès !";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout de l'hypothèse : " . $e->getMessage();
    }
    header("Location: hypotheses.php?project_id=$project_id");
    exit();
}

// Charger les hypothèses existantes
$hypotheses = [];
try {
    $stmt = $pdo->prepare("SELECT id, hypothesis_text, status, test_plan FROM hypotheses WHERE project_id = :project_id");
    $stmt->execute(['project_id' => $project_id]);
    $hypotheses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculer la progression
    $total_hypotheses = count($hypotheses);
    $confirmed_hypotheses = count(array_filter($hypotheses, function($h) {
        return $h['status'] === 'confirmed';
    }));
    $progress_percentage = $total_hypotheses > 0 ? ($confirmed_hypotheses / $total_hypotheses) * 100 : 0;
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des hypothèses : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Hypothèses - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
    <style>
        /* Style pour le loader épuré */
        #loadingModal .modal-dialog {
            max-width: 300px; /* Réduire la taille de la modale */
        }

        #loadingModal .modal-content {
            background: rgba(0, 0, 0, 0.5) !important; /* Fond semi-transparent sombre */
            border-radius: 15px; /* Coins arrondis */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); /* Ombre douce */
        }

        .spinner-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .spinner-custom {
            width: 3rem;
            height: 3rem;
            border-width: 4px; /* Épaisseur de la bordure */
            border-color: #007bff transparent #007bff transparent; /* Couleur avec effet de rotation */
            animation: spin 1s ease-in-out infinite;
        }

        .loading-text {
            font-size: 1rem;
            font-weight: 500;
            color: #ffffff; /* Texte blanc pour contraste */
            opacity: 0.9;
            animation: fadeInOut 2s ease-in-out infinite; /* Animation de pulsation */
        }

        /* Animation de rotation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Animation de pulsation pour le texte */
        @keyframes fadeInOut {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
    </style>
</head>
<body>
    <?php include '../layouts/navbar.php'; ?>

    <div class="container my-5">
        <!-- Afficher les messages d'erreur ou de succès -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <h2 class="section-title text-center mb-5">Gestion des Hypothèses - <?= htmlspecialchars($project['name']) ?></h2>

        <!-- Guide pour comprendre et gérer les hypothèses -->
        <div class="guide-section">
            <h4 class="text-primary"><i class="bi bi-info-circle me-2"></i> Guide : Comprendre et Gérer vos Hypothèses</h4>
            <p class="text-muted">
                Les hypothèses sont des suppositions clés que vous faites sur votre modèle d'affaires. Elles doivent être testées pour valider ou invalider votre Business Model Canvas (BMC). Voici quelques conseils pour bien gérer vos hypothèses :
            </p>
            <ul class="text-muted">
                <li><strong>Clarté :</strong> Formulez vos hypothèses de manière claire et testable (ex. : "Les clients seront prêts à payer 10€ par mois pour ce service").</li>
                <li><strong>Priorisation :</strong> Identifiez les hypothèses les plus critiques pour votre projet et testez-les en premier.</li>
                <li><strong>Tests :</strong> Utilisez des méthodes comme des enquêtes, des interviews ou des tests A/B pour valider vos hypothèses.</li>
                <li><strong>Adaptation :</strong> Si une hypothèse est invalidée, ajustez votre BMC en conséquence.</li>
            </ul>
            <p class="text-muted">
                Utilisez les boutons ci-dessous pour gérer vos hypothèses.
            </p>
        </div>

        <!-- Barre de progression -->
        <div class="progress-section mb-4">
            <h5 class="text-center mb-3">Progression de la Validation</h5>
            <div class="progress" style="height: 25px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progress_percentage ?>%;" aria-valuenow="<?= $progress_percentage ?>" aria-valuemin="0" aria-valuemax="100">
                    <?= round($progress_percentage) ?>%
                </div>
            </div>
            <p class="text-center text-muted mt-2">
                <?= $confirmed_hypotheses ?> hypothèse(s) validée(s) sur <?= $total_hypotheses ?>
            </p>
        </div>

        <!-- Liste des hypothèses -->
        <div class="row justify-content-center">
            <div class="col-md-10">
                <h3 class="section-title text-center mb-4">Vos Hypothèses</h3>
                <div id="hypotheses-list">
                    <?php if (empty($hypotheses)): ?>
                        <p class="text-center text-muted">Aucune hypothèse pour ce projet. Générez ou ajoutez-en une ci-dessous !</p>
                    <?php else: ?>
                        <?php foreach ($hypotheses as $index => $hypothesis): ?>
                            <div class="hypothesis-card" data-id="<?= $hypothesis['id'] ?>">
                                <div class="hypothesis-number"><?= $index + 1 ?></div>
                                <p>
                                    <?= htmlspecialchars($hypothesis['hypothesis_text']) ?>
                                    <span class="status-badge ms-2 <?= $hypothesis['status'] ?>">
                                        <?= ucfirst($hypothesis['status'] === 'pending' ? 'En attente' : ($hypothesis['status'] === 'confirmed' ? 'Confirmée' : 'Rejetée')) ?>
                                    </span>
                                </p>
                                <div class="hypothesis-actions">
                                    <button class="btn review-btn review-hypothesis-btn" data-id="<?= $hypothesis['id'] ?>" data-index="<?= $index ?>" data-text="<?= htmlspecialchars($hypothesis['hypothesis_text']) ?>" data-status="<?= $hypothesis['status'] ?>" data-test-plan="<?= htmlspecialchars($hypothesis['test_plan'] ?? '') ?>" data-bs-toggle="modal" data-bs-target="#reviewHypothesisModal">
                                        <i class="bi bi-eye"></i> Passer en revue
                                    </button>
                                    <button class="btn edit-btn edit-hypothesis-btn" data-id="<?= $hypothesis['id'] ?>" data-text="<?= htmlspecialchars($hypothesis['hypothesis_text']) ?>" data-bs-toggle="modal" data-bs-target="#editHypothesisModal">
                                        <i class="bi bi-pencil"></i> Modifier
                                    </button>
                                    <button class="btn delete-btn delete-hypothesis-btn" data-id="<?= $hypothesis['id'] ?>">
                                        <i class="bi bi-trash"></i> Supprimer
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Boutons d'action en bas -->
                <div class="action-buttons">
                    <a href="hypotheses.php?project_id=<?= $project_id ?>&generate=1" class="btn btn-primary" id="generate-hypotheses-btn">
                        <i class="bi bi-magic"></i> Générer des Hypothèses
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHypothesisModal">
                        <i class="bi bi-plus-circle"></i> Ajouter une Hypothèse
                    </button>
                    <a href="download_hypotheses_pdf.php?project_id=<?= $project_id ?>" class="btn btn-primary">
                        <i class="bi bi-file-earmark-pdf"></i> Télécharger en PDF
                    </a>
                    <a href="visualisation.php?project_id=<?= $project_id ?>" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> Retour au BMC
                    </a>
                    <?php if ($progress_percentage == 100): ?>
                        <a href="financial_plan.php?project_id=<?= $project_id ?>" class="btn btn-success">
                            <i class="bi bi-calculator"></i> Passer au Plan Financier
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale pour ajouter une hypothèse -->
    <div class="modal fade" id="addHypothesisModal" tabindex="-1" aria-labelledby="addHypothesisModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addHypothesisModalLabel">Ajouter une Hypothèse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addHypothesisForm">
                        <div class="mb-3">
                            <label for="hypothesis_text" class="form-label">Texte de l'Hypothèse</label>
                            <textarea class="form-control" id="hypothesis_text" name="hypothesis_text" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale pour confirmer l'ajout d'une hypothèse -->
    <div class="modal fade" id="confirmAddModal" tabindex="-1" aria-labelledby="confirmAddModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmAddModalLabel">Confirmer l'Ajout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir ajouter cette hypothèse ?</p>
                    <p class="text-muted" id="confirm-add-text"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="confirm-add-btn">Confirmer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale pour modifier une hypothèse -->
    <div class="modal fade" id="editHypothesisModal" tabindex="-1" aria-labelledby="editHypothesisModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editHypothesisModalLabel">Modifier une Hypothèse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editHypothesisForm">
                        <input type="hidden" id="edit_hypothesis_id" name="hypothesis_id">
                        <input type="hidden" name="project_id" value="<?= $project_id ?>">
                        <div class="mb-3">
                            <label for="edit_hypothesis_text" class="form-label">Texte de l'Hypothèse</label>
                            <textarea class="form-control" id="edit_hypothesis_text" name="hypothesis_text" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enregistrer les Modifications</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale pour passer en revue une hypothèse -->
    <div class="modal fade" id="reviewHypothesisModal" tabindex="-1" aria-labelledby="reviewHypothesisModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewHypothesisModalLabel">Passer en Revue l'Hypothèse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="review-navigation mb-3 d-flex justify-content-between">
                        <button class="btn btn-outline-secondary" id="prev-hypothesis-btn" disabled><i class="bi bi-arrow-left"></i> Précédent</button>
                        <button class="btn btn-outline-secondary" id="next-hypothesis-btn"><i class="bi bi-arrow-right"></i> Suivant</button>
                    </div>
                    <div id="review-content">
                        <input type="hidden" id="review_hypothesis_id">
                        <input type="hidden" id="review_hypothesis_index">
                        <h6 class="mb-3">Hypothèse :</h6>
                        <p id="review_hypothesis_text" class="border p-3 rounded bg-light"></p>
                        <h6 class="mt-4 mb-3">Pertinence :</h6>
                        <p id="review_hypothesis_relevance" class="text-muted">
                            Cette hypothèse est pertinente car elle aborde une supposition clé de votre modèle d'affaires. Elle doit être testée pour confirmer sa validité. Par exemple, vous pourriez utiliser une enquête auprès de vos clients cibles ou un test MVP pour valider cette hypothèse.
                        </p>
                        <h6 class="mt-4 mb-3">Statut Actuel :</h6>
                        <p id="review_hypothesis_status" class="status-badge"></p>
                        <div class="validation-actions mt-4">
                            <h6>Valider l'Hypothèse :</h6>
                            <div class="d-flex gap-3 mb-3">
                                <button class="btn btn-success confirm-btn" id="confirm-hypothesis-btn"><i class="bi bi-check-circle"></i> Confirmer</button>
                                <button class="btn btn-danger reject-btn" id="reject-hypothesis-btn"><i class="bi bi-x-circle"></i> Infirmer</button>
                            </div>
                            <div id="test-plan-section" style="display: none;">
                                <h6 class="mb-3">Plan de Test (si infirmée) :</h6>
                                <textarea class="form-control mb-3" id="test_plan" rows="3" placeholder="Exemple : Réaliser un MVP, effectuer des ventes test, ou mener une enquête..."></textarea>
                                <button class="btn btn-primary" id="save-test-plan-btn">Enregistrer le Plan de Test</button>
                            </div>
                            <div id="bmc-modify-section" style="display: none;" class="mt-3">
                                <h6 class="mb-3">Modifier le BMC :</h6>
                                <p class="text-muted">Cette hypothèse a été infirmée. Vous devriez peut-être ajuster votre BMC.</p>
                                <a href="visualisation.php?project_id=<?= $project_id ?>§ion=customer_segments" class="btn btn-outline-primary"><i class="bi bi-arrow-right-circle"></i> Modifier le BMC</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale de confirmation de suppression -->
    <div class="modal fade" id="deleteHypothesisModal" tabindex="-1" aria-labelledby="deleteHypothesisModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteHypothesisModalLabel">Confirmer la Suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer cette hypothèse ? Cette action est irréversible.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="confirm-delete-btn">Supprimer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale de confirmation d'enregistrement -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Succès</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-center">L'hypothèse a été enregistrée avec succès !</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale pour confirmer l'action de confirmation/infirmation -->
    <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmActionModalLabel">Confirmer l'Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="confirm-action-message"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="confirm-action-btn">Confirmer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale pour la confirmation de l'action réussie -->
    <div class="modal fade" id="actionSuccessModal" tabindex="-1" aria-labelledby="actionSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionSuccessModalLabel">Action Réussie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-center" id="action-success-message"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale pour la confirmation de suppression réussie -->
    <div class="modal fade" id="deleteSuccessModal" tabindex="-1" aria-labelledby="deleteSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSuccessModalLabel">Suppression Réussie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-center">L'hypothèse a été supprimée avec succès !</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale pour les erreurs -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">Erreur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-center" id="error-message"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale pour le loader épuré -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm" style="background: transparent;">
                <div class="modal-body text-center">
                    <div class="spinner-container">
                        <div class="spinner-border text-primary spinner-custom" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-3 text-light loading-text">Génération des hypothèses en cours...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Variable pour stocker le texte de l'hypothèse à ajouter
        let hypothesisTextToAdd = null;

        // Ajouter une nouvelle hypothèse
        $('#addHypothesisForm').on('submit', function(e) {
            e.preventDefault();
            hypothesisTextToAdd = $('#hypothesis_text').val();
            $('#confirm-add-text').text(hypothesisTextToAdd);
            $('#confirmAddModal').modal('show');
        });

        // Confirmer l'ajout
        $('#confirm-add-btn').on('click', function() {
            if (hypothesisTextToAdd) {
                $.ajax({
                    url: 'add_hypothesis.php',
                    method: 'POST',
                    data: {
                        project_id: <?= $project_id ?>,
                        hypothesis_text: hypothesisTextToAdd
                    },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            $('#confirmAddModal').modal('hide');
                            $('#addHypothesisModal').modal('hide');
                            $('#actionSuccessModal').modal('show').find('#action-success-message').text('Hypothèse ajoutée avec succès !');
                            const index = $('.hypothesis-card').length + 1;
                            const newHypothesisHtml = `
                                <div class="hypothesis-card" data-id="${result.hypothesis_id}">
                                    <div class="hypothesis-number">${index}</div>
                                    <p>
                                        ${result.hypothesis_text}
                                        <span class="status-badge ms-2 ${result.status}">
                                            ${result.status === 'pending' ? 'En attente' : (result.status === 'confirmed' ? 'Confirmée' : 'Rejetée')}
                                        </span>
                                    </p>
                                    <div class="hypothesis-actions">
                                        <button class="btn review-btn review-hypothesis-btn" data-id="${result.hypothesis_id}" data-index="${index - 1}" data-text="${result.hypothesis_text}" data-status="${result.status}" data-test-plan="" data-bs-toggle="modal" data-bs-target="#reviewHypothesisModal">
                                            <i class="bi bi-eye"></i> Passer en revue
                                        </button>
                                        <button class="btn edit-btn edit-hypothesis-btn" data-id="${result.hypothesis_id}" data-text="${result.hypothesis_text}" data-bs-toggle="modal" data-bs-target="#editHypothesisModal">
                                            <i class="bi bi-pencil"></i> Modifier
                                        </button>
                                        <button class="btn delete-btn delete-hypothesis-btn" data-id="${result.hypothesis_id}">
                                            <i class="bi bi-trash"></i> Supprimer
                                        </button>
                                    </div>
                                </div>
                            `;
                            if ($('#hypotheses-list').children().length === 1 && $('#hypotheses-list p.text-center').length) {
                                $('#hypotheses-list').html(newHypothesisHtml);
                            } else {
                                $('#hypotheses-list').append(newHypothesisHtml);
                            }
                            $('#hypothesis_text').val('');
                            updateProgressBar();
                            checkProgressForFinancialPlan();
                            hypothesisTextToAdd = null;
                        } else {
                            $('#error-message').text(result.message);
                            $('#errorModal').modal('show');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#error-message').text('Erreur lors de la communication avec le serveur : ' + error);
                        $('#errorModal').modal('show');
                    }
                });
            }
        });

        // Préparer la modification d'une hypothèse
        $(document).on('click', '.edit-hypothesis-btn', function() {
            const hypothesisId = $(this).data('id');
            const hypothesisText = $(this).data('text');

            $('#edit_hypothesis_id').val(hypothesisId);
            $('#edit_hypothesis_text').val(hypothesisText);
        });

        // Modifier une hypothèse
        $('#editHypothesisForm').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();

            $.ajax({
                url: 'update_hypothesis.php',
                method: 'POST',
                data: formData,
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        $('#editHypothesisModal').modal('hide');
                        $('#actionSuccessModal').modal('show').find('#action-success-message').text('Hypothèse modifiée avec succès !');
                        $(`.hypothesis-card[data-id="${result.hypothesis_id}"] p`).html(`${result.hypothesis_text} <span class="status-badge ms-2 ${result.status}">${result.status === 'pending' ? 'En attente' : (result.status === 'confirmed' ? 'Confirmée' : 'Rejetée')}</span>`);
                        $(`.edit-hypothesis-btn[data-id="${result.hypothesis_id}"]`).data('text', result.hypothesis_text);
                        updateProgressBar();
                    } else {
                        $('#error-message').text('Erreur lors de la mise à jour : ' + result.message);
                        $('#errorModal').modal('show');
                    }
                },
                error: function(xhr, status, error) {
                    $('#error-message').text('Erreur lors de la communication avec le serveur : ' + error);
                    $('#errorModal').modal('show');
                }
            });
        });

        // Préparer la suppression d'une hypothèse
        let hypothesisIdToDelete = null;
        $(document).on('click', '.delete-hypothesis-btn', function() {
            hypothesisIdToDelete = $(this).data('id');
            $('#deleteHypothesisModal').modal('show');
        });

        // Confirmer la suppression
        $('#confirm-delete-btn').on('click', function() {
            if (hypothesisIdToDelete) {
                $.ajax({
                    url: 'delete_hypothesis.php',
                    method: 'POST',
                    data: { hypothesis_id: hypothesisIdToDelete },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            $('#deleteHypothesisModal').modal('hide');
                            $('#deleteSuccessModal').modal('show');
                            $(`.hypothesis-card[data-id="${hypothesisIdToDelete}"]`).fadeOut(300, function() {
                                $(this).remove();
                                if ($('#hypotheses-list').children().length === 0) {
                                    $('#hypotheses-list').html('<p class="text-center text-muted">Aucune hypothèse pour ce projet. Générez ou ajoutez-en une ci-dessous !</p>');
                                }
                                $('.hypothesis-card').each(function(index) {
                                    $(this).find('.hypothesis-number').text(index + 1);
                                    $(this).find('.review-hypothesis-btn').data('index', index);
                                });
                                updateProgressBar();
                                checkProgressForFinancialPlan();
                            });
                            hypothesisIdToDelete = null;
                        } else {
                            $('#error-message').text('Erreur lors de la suppression : ' + result.message);
                            $('#errorModal').modal('show');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#error-message').text('Erreur lors de la communication avec le serveur : ' + error);
                        $('#errorModal').modal('show');
                    }
                });
            }
        });

        // Gestion du loader épuré pour "Générer des hypothèses"
        $('#generate-hypotheses-btn').on('click', function(e) {
            $('#loadingModal').modal('show');
            $(this).addClass('disabled').prop('disabled', true);
        });

        // Gérer la redirection après la génération (fermer le loader)
        <?php if (isset($_GET['generate']) && $_GET['generate'] == 1): ?>
            $(window).on('load', function() {
                $('#loadingModal').modal('hide');
                $('#generate-hypotheses-btn').removeClass('disabled').prop('disabled', false);
            });
        <?php endif; ?>

        // Préparer la révision d'une hypothèse
        $(document).on('click', '.review-hypothesis-btn', function() {
            const hypothesisId = $(this).data('id');
            const hypothesisIndex = $(this).data('index');
            const hypothesisText = $(this).data('text');
            const hypothesisStatus = $(this).data('status');
            const testPlan = $(this).data('test-plan');

            $('#review_hypothesis_id').val(hypothesisId);
            $('#review_hypothesis_index').val(hypothesisIndex);
            $('#review_hypothesis_text').text(hypothesisText);
            $('#review_hypothesis_status').text(hypothesisStatus === 'pending' ? 'En attente' : (hypothesisStatus === 'confirmed' ? 'Confirmée' : 'Rejetée'));
            $('#review_hypothesis_status').removeClass('pending confirmed rejected').addClass(hypothesisStatus);
            $('#test_plan').val(testPlan || '');

            if (hypothesisStatus === 'rejected') {
                $('#test-plan-section').show();
                $('#bmc-modify-section').show();
            } else {
                $('#test-plan-section').hide();
                $('#bmc-modify-section').hide();
            }

            $('#prev-hypothesis-btn').prop('disabled', hypothesisIndex === 0);
            $('#next-hypothesis-btn').prop('disabled', hypothesisIndex === $('.hypothesis-card').length - 1);
        });

        // Navigation entre les hypothèses
        $('#prev-hypothesis-btn').on('click', function() {
            const currentIndex = parseInt($('#review_hypothesis_index').val());
            if (currentIndex > 0) {
                const prevBtn = $(`.review-hypothesis-btn[data-index="${currentIndex - 1}"]`);
                prevBtn.click();
            }
        });

        $('#next-hypothesis-btn').on('click', function() {
            const currentIndex = parseInt($('#review_hypothesis_index').val());
            if (currentIndex < $('.hypothesis-card').length - 1) {
                const nextBtn = $(`.review-hypothesis-btn[data-index="${currentIndex + 1}"]`);
                nextBtn.click();
            }
        });

        // Variables pour gérer les actions de confirmation/infirmation
        let currentAction = null;
        let hypothesisIdToUpdate = null;

        // Confirmer une hypothèse
        $('#confirm-hypothesis-btn').on('click', function() {
            hypothesisIdToUpdate = $('#review_hypothesis_id').val();
            currentAction = 'confirm';
            $('#confirm-action-message').text('Êtes-vous sûr de vouloir confirmer cette hypothèse ?');
            $('#confirmActionModal').modal('show');
        });

        // Infirmer une hypothèse
        $('#reject-hypothesis-btn').on('click', function() {
            hypothesisIdToUpdate = $('#review_hypothesis_id').val();
            currentAction = 'reject';
            $('#confirm-action-message').text('Êtes-vous sûr de vouloir infirmer cette hypothèse ?');
            $('#confirmActionModal').modal('show');
        });

        // Exécuter l'action après confirmation
        $('#confirm-action-btn').on('click', function() {
            if (hypothesisIdToUpdate && currentAction) {
                const status = currentAction === 'confirm' ? 'confirmed' : 'rejected';
                $.ajax({
                    url: 'update_hypothesis_status.php',
                    method: 'POST',
                    data: { hypothesis_id: hypothesisIdToUpdate, status: status, test_plan: '' },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            $('#confirmActionModal').modal('hide');
                            $('#reviewHypothesisModal').modal('hide');
                            $('#action-success-message').text(status === 'confirmed' ? 'L\'hypothèse a été confirmée avec succès !' : 'L\'hypothèse a été infirmée avec succès !');
                            $('#actionSuccessModal').modal('show');
                            $(`.hypothesis-card[data-id="${hypothesisIdToUpdate}"] .status-badge`)
                                .text(status === 'confirmed' ? 'Confirmée' : 'Rejetée')
                                .removeClass('pending confirmed rejected')
                                .addClass(status);
                            $(`.review-hypothesis-btn[data-id="${hypothesisIdToUpdate}"]`).data('status', status);
                            updateProgressBar();
                            checkProgressForFinancialPlan();
                            hypothesisIdToUpdate = null;
                            currentAction = null;
                        } else {
                            $('#error-message').text('Erreur lors de la mise à jour : ' + result.message);
                            $('#errorModal').modal('show');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#error-message').text('Erreur lors de la communication avec le serveur : ' + error);
                        $('#errorModal').modal('show');
                    }
                });
            }
        });

        // Enregistrer le plan de test
        $('#save-test-plan-btn').on('click', function() {
            const hypothesisId = $('#review_hypothesis_id').val();
            const testPlan = $('#test_plan').val();
            $.ajax({
                url: 'update_hypothesis_status.php',
                method: 'POST',
                data: { hypothesis_id: hypothesisId, status: 'rejected', test_plan: testPlan },
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        $(`.review-hypothesis-btn[data-id="${hypothesisId}"]`).data('test-plan', testPlan);
                        $('#action-success-message').text('Plan de test enregistré avec succès !');
                        $('#actionSuccessModal').modal('show');
                    } else {
                        $('#error-message').text('Erreur lors de la mise à jour : ' + result.message);
                        $('#errorModal').modal('show');
                    }
                },
                error: function(xhr, status, error) {
                    $('#error-message').text('Erreur lors de la communication avec le serveur : ' + error);
                    $('#errorModal').modal('show');
                }
            });
        });

        // Fonction pour mettre à jour la barre de progression
        function updateProgressBar() {
            const total = $('.hypothesis-card').length;
            const confirmed = $('.hypothesis-card .status-badge.confirmed').length;
            const percentage = total > 0 ? (confirmed / total) * 100 : 0;
            $('.progress-bar').css('width', percentage + '%').text(Math.round(percentage) + '%');
            $('.progress-section p').text(`${confirmed} hypothèse(s) validée(s) sur ${total}`);
        }

        // Fonction pour vérifier la progression et afficher/masquer le bouton "Passer au Plan Financier"
        function checkProgressForFinancialPlan() {
            const total = $('.hypothesis-card').length;
            const confirmed = $('.hypothesis-card .status-badge.confirmed').length;
            const percentage = total > 0 ? (confirmed / total) * 100 : 0;
            if (percentage === 100) {
                if (!$('.action-buttons .btn-success').length) {
                    $('.action-buttons').append(`
                        <a href="financial_plan.php?project_id=<?= $project_id ?>" class="btn btn-success">
                            <i class="bi bi-calculator"></i> Passer au Plan Financier
                        </a>
                    `);
                }
            } else {
                $('.action-buttons .btn-success').remove();
            }
        }

        // Vérifier la progression au chargement de la page
        checkProgressForFinancialPlan();
    });
    </script>
</body>
</html>