<?php
session_start();

// Activer la gestion des sorties pour éviter les erreurs de redirection
ob_start();

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

// Vérifier si un project_id est fourni et valide
if (!isset($_GET['project_id']) || !is_numeric($_GET['project_id']) || (int)$_GET['project_id'] <= 0) {
    $_SESSION['error'] = "Projet non spécifié ou invalide.";
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
    error_log('Erreur lors de la récupération du projet : ' . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la récupération du projet.";
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
        error_log('Erreur de configuration de l\'API OpenAI : ' . $e->getMessage());
        $_SESSION['error'] = "Erreur de configuration de l'API OpenAI.";
        header("Location: hypotheses.php?project_id=$project_id");
        exit();
    }

    // Créer le prompt pour générer des hypothèses
    $api_prompt = <<<EOD
Tu es un expert en création de Business Model Canvas et en génération d'hypothèses testables.
Génère 4 à 5 hypothèses testables pour le Business Model Canvas du projet suivant : "{$project['description']}".
Chaque hypothèse doit être concise, claire et commencer par "Nous supposons que".
Par exemple : "Nous supposons que les clients seront prêts à payer 10€ par mois pour ce service."
Fournis les hypothèses sous forme de liste, une par ligne, sans numérotation ni puces.
EOD;

    try {
        $response = $client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => $api_prompt],
            ],
            'max_tokens' => 500,
            'temperature' => 0.7,
        ]);

        // Vérifier si la réponse contient du contenu
        if (!isset($response->choices[0]->message->content)) {
            throw new Exception('Réponse vide ou invalide de l\'API OpenAI');
        }

        $generated_text = $response->choices[0]->message->content;
        $hypotheses = array_filter(array_map('trim', explode("\n", $generated_text)));

        // Vérifier si des hypothèses ont été générées
        if (empty($hypotheses)) {
            throw new Exception('Aucune hypothèse générée par l\'API OpenAI');
        }

        // Enregistrer les hypothèses dans la base de données avec une transaction
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO hypotheses (project_id, hypothesis_text, status) VALUES (:project_id, :hypothesis_text, 'pending')");
            foreach ($hypotheses as $hypothesis) {
                if (!empty($hypothesis)) {
                    $stmt->execute([
                        'project_id' => $project_id,
                        'hypothesis_text' => $hypothesis
                    ]);
                }
            }
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw new Exception('Erreur lors de l\'enregistrement des hypothèses : ' . $e->getMessage());
        }

        // Rediriger pour éviter de regénérer à chaque rechargement
        header("Location: hypotheses.php?project_id=$project_id");
        exit();
    } catch (Exception $e) {
        error_log('Erreur lors de la génération des hypothèses : ' . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de la génération des hypothèses.";
        header("Location: hypotheses.php?project_id=$project_id");
        exit();
    }
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
    error_log('Erreur lors de la récupération des hypothèses : ' . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la récupération des hypothèses.";
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
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL) ?>/assets/css/styles.css">
    <!-- Suppression des styles locaux, tout passe par le CSS global -->
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
                Les Hypothèses sont des suppositions clés qui ressortent de votre modèle d’affaire. Elles doivent être testées pour valider ou invalider votre Business Model Canvas (BMC). Voici quelques conseils pour bien gérer vos hypothèses :
            </p>
            <ul class="text-muted">
                <li><strong>Priorisation :</strong> Identifiez les hypothèses les plus critiques pour votre projet et testez-les en premier.</li>
                <li><strong>Tests :</strong> Utilisez des méthodes comme des enquêtes, des interviews ou des tests A/B pour valider vos hypothèses.</li>
                <li><strong>Adaptation :</strong> Si une hypothèse est invalidée, ajustez votre BMC en conséquence.</li>
            </ul>
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
                        <p class="text-center text-muted">Aucune hypothèse pour ce projet. Générez-en une ci-dessous !</p>
                    <?php else: ?>
                        <div class="row g-3">
                        <?php foreach ($hypotheses as $index => $hypothesis): ?>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="hypothesis-card bmc-card h-100 d-flex flex-column" data-id="<?= $hypothesis['id'] ?>">
                                    <div class="hypothesis-number"><?= $index + 1 ?></div>
                                    <div class="bmc-content">
                                        <?= htmlspecialchars($hypothesis['hypothesis_text']) ?>
                                        <span class="status-badge ms-2 <?= htmlspecialchars($hypothesis['status']) ?>">
                                            <?= ucfirst($hypothesis['status'] === 'pending' ? 'En attente' : ($hypothesis['status'] === 'confirmed' ? 'Confirmée' : 'Rejetée')) ?>
                                        </span>
                                    </div>
                                    <div class="hypothesis-actions mt-auto">
                                        <button class="btn review-btn review-hypothesis-btn" data-id="<?= $hypothesis['id'] ?>" data-index="<?= $index ?>" data-text="<?= htmlspecialchars($hypothesis['hypothesis_text']) ?>" data-status="<?= htmlspecialchars($hypothesis['status']) ?>" data-test-plan="<?= htmlspecialchars($hypothesis['test_plan'] ?? '') ?>" data-bs-toggle="modal" data-bs-target="#reviewHypothesisModal">
                                            <i class="bi bi-eye"></i> Voir
                                        </button>
                                        <button class="btn btn-edit edit-hypothesis-btn" data-id="<?= $hypothesis['id'] ?>" data-text="<?= htmlspecialchars($hypothesis['hypothesis_text']) ?>" data-bs-toggle="modal" data-bs-target="#editHypothesisModal">
                                            <i class="bi bi-pencil"></i> Modifier
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Boutons d'action en bas -->
                <div class="visualisation-actions mt-4">
                    <a href="hypotheses.php?project_id=<?= $project_id ?>&generate=1" class="btn btn-primary" id="generate-hypotheses-btn">
                        <i class="bi bi-magic"></i> Générer des Analyses
                    </a>
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
                        <button type="submit" class="btn btn-primary">Enregistrer </button>
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

    <!-- Modale pour confirmer l'action (confirmation/infirmation) -->
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

    <!-- Conteneur pour l'icône de confirmation -->
    <div id="successIcon">
        <i class="bi bi-check-circle"></i>
    </div>

    <?php include '../layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
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
                        // Afficher l'icône de confirmation
                        $('#successIcon').addClass('success-icon-show');
                        setTimeout(function() {
                            $('#successIcon').removeClass('success-icon-show');
                        }, 2000);
                        // Mettre à jour l'affichage
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

        // Confirmer une hypothèse (action immédiate, sans pop-up)
        $('#confirm-hypothesis-btn').on('click', function() {
            const hypothesisId = $('#review_hypothesis_id').val();
            if (hypothesisId) {
                $.ajax({
                    url: 'update_hypothesis_status.php',
                    method: 'POST',
                    data: { hypothesis_id: hypothesisId, status: 'confirmed', test_plan: '' },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            $('#reviewHypothesisModal').modal('hide');
                            // Afficher l'icône de confirmation
                            $('#successIcon').addClass('success-icon-show');
                            setTimeout(function() {
                                $('#successIcon').removeClass('success-icon-show');
                            }, 2000);
                            // Mettre à jour l'affichage
                            $(`.hypothesis-card[data-id="${hypothesisId}"] .status-badge`)
                                .text('Confirmée')
                                .removeClass('pending confirmed rejected')
                                .addClass('confirmed');
                            $(`.review-hypothesis-btn[data-id="${hypothesisId}"]`).data('status', 'confirmed');
                            updateProgressBar();
                            checkProgressForFinancialPlan();
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

        // Infirmer une hypothèse
        $('#reject-hypothesis-btn').on('click', function() {
            hypothesisIdToUpdate = $('#review_hypothesis_id').val();
            currentAction = 'reject';
            $('#confirm-action-message').text('Êtes-vous sûr de vouloir infirmer cette hypothèse ?');
            $('#confirmActionModal').modal('show');
        });

        // Exécuter l'action après confirmation
        $('#confirm-action-btn').on('click', function() {
            const hypothesisId = hypothesisIdToUpdate;

            if (hypothesisId && currentAction) {
                const status = currentAction === 'confirm' ? 'confirmed' : 'rejected';
                $.ajax({
                    url: 'update_hypothesis_status.php',
                    method: 'POST',
                    data: { hypothesis_id: hypothesisId, status: status, test_plan: '' },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            $('#confirmActionModal').modal('hide');
                            $('#reviewHypothesisModal').modal('hide');
                            // Afficher l'icône de confirmation
                            $('#successIcon').addClass('success-icon-show');
                            setTimeout(function() {
                                $('#successIcon').removeClass('success-icon-show');
                            }, 2000);
                            // Mettre à jour l'affichage
                            $(`.hypothesis-card[data-id="${hypothesisId}"] .status-badge`)
                                .text(status === 'confirmed' ? 'Confirmée' : 'Rejetée')
                                .removeClass('pending confirmed rejected')
                                .addClass(status);
                            $(`.review-hypothesis-btn[data-id="${hypothesisId}"]`).data('status', status);
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
            // Réinitialiser les données de l'action
            $(this).data('action', null);
            $(this).data('hypothesis_id', null);
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
                        // Afficher l'icône de confirmation
                        $('#successIcon').addClass('success-icon-show');
                        setTimeout(function() {
                            $('#successIcon').removeClass('success-icon-show');
                        }, 2000);
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
                if (!$('.visualisation-actions .btn-success').length) {
                    $('.visualisation-actions').append(`
                        <a href="financial_plan.php?project_id=<?= $project_id ?>" class="btn btn-success">
                            <i class="bi bi-calculator"></i> Passer au Plan Financier
                        </a>
                    `);
                }
            } else {
                $('.visualisation-actions .btn-success').parent('.action-button-group').remove();
            }
        }

        // Vérifier la progression au chargement de la page
        checkProgressForFinancialPlan();
    });
    </script>
</body>
</html>

<?php
// Libérer le buffer de sortie
ob_end_flush();
?>