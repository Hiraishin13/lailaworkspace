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

// Vérifier si un prompt a été soumis
if (!isset($_POST['prompt']) || empty(trim($_POST['prompt']))) {
    $_SESSION['error'] = "Veuillez entrer une description de votre projet.";
    header('Location: generate_bmc.php');
    exit();
}

// Nettoyer le prompt de manière sécurisée
$prompt = htmlspecialchars(trim($_POST['prompt']), ENT_QUOTES, 'UTF-8');

// Configurer le client OpenAI
try {
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        throw new Exception('Clé API OpenAI non configurée.');
    }
    $client = \OpenAI::client(OPENAI_API_KEY);
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur de configuration de l'API OpenAI : " . $e->getMessage();
    header('Location: generate_bmc.php');
    exit();
}

// Créer le prompt pour l'API avec un format strict
$api_prompt = "Génère un Business Model Canvas (BMC) pour le projet suivant : " . $prompt . ". Fournis les 9 piliers du BMC dans ce format exact, avec chaque pilier sur une nouvelle ligne et commençant par un tiret, suivi du nom du pilier, suivi de deux points et d'un espace, puis la description : \n- Segments de clientèle : [description]\n- Proposition de valeur : [description]\n- Canaux : [description]\n- Relations clients : [description]\n- Sources de revenus : [description]\n- Ressources clés : [description]\n- Activités clés : [description]\n- Partenaires clés : [description]\n- Structure des coûts : [description]\nAssure-toi que chaque pilier est présent et suit exactement ce format.";

// Appeler l'API d'OpenAI pour générer le BMC
try {
    $response = $client->chat()->create([
        'model' => 'gpt-4', // Utiliser GPT-4 (ou gpt-3.5-turbo si gpt-4 n'est pas disponible)
        'messages' => [
            ['role' => 'system', 'content' => 'Tu es un expert en création de Business Model Canvas.'],
            ['role' => 'user', 'content' => $api_prompt],
        ],
        'max_tokens' => 1000,
        'temperature' => 0.7,
    ]);

    // Récupérer la réponse de l'API
    $generated_text = $response->choices[0]->message->content;

    // Débogage : Enregistrer la réponse brute de l'API pour inspection
    $log_dir = BASE_DIR . '/logs';
    $log_file = $log_dir . '/api_response.txt';

    // Créer le répertoire logs s'il n'existe pas
    if (!is_dir($log_dir)) {
        if (!mkdir($log_dir, 0777, true)) {
            error_log("Impossible de créer le répertoire $log_dir", 0);
        }
    }

    // Écrire dans le fichier uniquement si le répertoire existe et est accessible
    if (is_dir($log_dir) && is_writable($log_dir)) {
        if (file_put_contents($log_file, $generated_text) === false) {
            error_log("Impossible d'écrire dans $log_file", 0);
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

    // Expressions régulières pour extraire les piliers
    $patterns = [
        'segments_clientele' => '/- Segments de clientèle\s*:\s*(.*?)(?=\n-|$)/i',
        'proposition_valeur' => '/- Proposition de valeur\s*:\s*(.*?)(?=\n-|$)/i',
        'canaux' => '/- Canaux\s*:\s*(.*?)(?=\n-|$)/i',
        'relations_clients' => '/- Relations clients\s*:\s*(.*?)(?=\n-|$)/i',
        'sources_revenus' => '/- Sources de revenus\s*:\s*(.*?)(?=\n-|$)/i',
        'ressources_cles' => '/- Ressources clés\s*:\s*(.*?)(?=\n-|$)/i',
        'activites_cles' => '/- Activités clés\s*:\s*(.*?)(?=\n-|$)/i',
        'partenaires_cles' => '/- Partenaires clés\s*:\s*(.*?)(?=\n-|$)/i',
        'structure_couts' => '/- Structure des coûts\s*:\s*(.*?)(?=\n-|$)/i'
    ];

    foreach ($patterns as $key => $pattern) {
        if (preg_match($pattern, $generated_text, $matches)) {
            $generated_bmc[$key] = trim($matches[1]);
        } else {
            $generated_bmc[$key] = "Non spécifié";
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur lors de la génération du BMC : " . $e->getMessage();
    header('Location: generate_bmc.php');
    exit();
}

// Enregistrer le projet dans la table `projects`
try {
    $stmt = $pdo->prepare("INSERT INTO projects (user_id, name, description) VALUES (:user_id, :name, :description)");
    $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'name' => "BMC généré pour : " . substr($prompt, 0, 50),
        'description' => $prompt
    ]);
    $project_id = $pdo->lastInsertId();
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de l'enregistrement du projet : " . $e->getMessage();
    header('Location: generate_bmc.php');
    exit();
}

// Enregistrer les 9 piliers dans la table `bmc`
try {
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
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de l'enregistrement des blocs du BMC : " . $e->getMessage();
    header('Location: generate_bmc.php');
    exit();
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
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

        <h2 class="text-center text-primary mb-5 fw-bold">Votre Business Model Canvas</h2>

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
                        <!-- Partenaires clés -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div>
                                    <i class="bi bi-people-fill text-primary mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="fw-bold">Partenaires clés</h5>
                                    <p class="text-muted bmc-content" data-block="partenaires_cles"><?= htmlspecialchars($generated_bmc['partenaires_cles']) ?></p>
                                </div>
                                <div class="edit-btn-container">
                                    <button class="btn btn-sm btn-outline-primary edit-btn edit-block-btn" data-block="partenaires_cles" data-project-id="<?= $project_id ?>" data-bs-toggle="modal" data-bs-target="#editBlockModal">
                                        <i class="bi bi-pencil"></i> Éditer
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Ressources clés -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div>
                                    <i class="bi bi-tools text-primary mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="fw-bold">Ressources clés</h5>
                                    <p class="text-muted bmc-content" data-block="ressources_cles"><?= htmlspecialchars($generated_bmc['ressources_cles']) ?></p>
                                </div>
                                <div class="edit-btn-container">
                                    <button class="btn btn-sm btn-outline-primary edit-btn edit-block-btn" data-block="ressources_cles" data-project-id="<?= $project_id ?>" data-bs-toggle="modal" data-bs-target="#editBlockModal">
                                        <i class="bi bi-pencil"></i> Éditer
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Segments de clientèle -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div>
                                    <i class="bi bi-person-fill text-primary mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="fw-bold">Segments de clientèle</h5>
                                    <p class="text-muted bmc-content" data-block="segments_clientele"><?= htmlspecialchars($generated_bmc['segments_clientele']) ?></p>
                                </div>
                                <div class="edit-btn-container">
                                    <button class="btn btn-sm btn-outline-primary edit-btn edit-block-btn" data-block="segments_clientele" data-project-id="<?= $project_id ?>" data-bs-toggle="modal" data-bs-target="#editBlockModal">
                                        <i class="bi bi-pencil"></i> Éditer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ligne 2 -->
                    <div class="row g-3 mb-3">
                        <!-- Activités clés -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div>
                                    <i class="bi bi-gear-fill text-primary mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="fw-bold">Activités clés</h5>
                                    <p class="text-muted bmc-content" data-block="activites_cles"><?= htmlspecialchars($generated_bmc['activites_cles']) ?></p>
                                </div>
                                <div class="edit-btn-container">
                                    <button class="btn btn-sm btn-outline-primary edit-btn edit-block-btn" data-block="activites_cles" data-project-id="<?= $project_id ?>" data-bs-toggle="modal" data-bs-target="#editBlockModal">
                                        <i class="bi bi-pencil"></i> Éditer
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Proposition de valeur -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div>
                                    <i class="bi bi-star-fill text-primary mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="fw-bold">Proposition de valeur</h5>
                                    <p class="text-muted bmc-content" data-block="proposition_valeur"><?= htmlspecialchars($generated_bmc['proposition_valeur']) ?></p>
                                </div>
                                <div class="edit-btn-container">
                                    <button class="btn btn-sm btn-outline-primary edit-btn edit-block-btn" data-block="proposition_valeur" data-project-id="<?= $project_id ?>" data-bs-toggle="modal" data-bs-target="#editBlockModal">
                                        <i class="bi bi-pencil"></i> Éditer
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Canaux -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div>
                                    <i class="bi bi-megaphone-fill text-primary mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="fw-bold">Canaux</h5>
                                    <p class="text-muted bmc-content" data-block="canaux"><?= htmlspecialchars($generated_bmc['canaux']) ?></p>
                                </div>
                                <div class="edit-btn-container">
                                    <button class="btn btn-sm btn-outline-primary edit-btn edit-block-btn" data-block="canaux" data-project-id="<?= $project_id ?>" data-bs-toggle="modal" data-bs-target="#editBlockModal">
                                        <i class="bi bi-pencil"></i> Éditer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ligne 3 -->
                    <div class="row g-3">
                        <!-- Structure des coûts -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div>
                                    <i class="bi bi-currency-dollar text-primary mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="fw-bold">Structure des coûts</h5>
                                    <p class="text-muted bmc-content" data-block="structure_couts"><?= htmlspecialchars($generated_bmc['structure_couts']) ?></p>
                                </div>
                                <div class="edit-btn-container">
                                    <button class="btn btn-sm btn-outline-primary edit-btn edit-block-btn" data-block="structure_couts" data-project-id="<?= $project_id ?>" data-bs-toggle="modal" data-bs-target="#editBlockModal">
                                        <i class="bi bi-pencil"></i> Éditer
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Sources de revenus -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div>
                                    <i class="bi bi-cash-stack text-primary mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="fw-bold">Sources de revenus</h5>
                                    <p class="text-muted bmc-content" data-block="sources_revenus"><?= htmlspecialchars($generated_bmc['sources_revenus']) ?></p>
                                </div>
                                <div class="edit-btn-container">
                                    <button class="btn btn-sm btn-outline-primary edit-btn edit-block-btn" data-block="sources_revenus" data-project-id="<?= $project_id ?>" data-bs-toggle="modal" data-bs-target="#editBlockModal">
                                        <i class="bi bi-pencil"></i> Éditer
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Relations clients -->
                        <div class="col-md-4">
                            <div class="card shadow-sm bmc-card">
                                <div>
                                    <i class="bi bi-heart-fill text-primary mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="fw-bold">Relations clients</h5>
                                    <p class="text-muted bmc-content" data-block="relations_clients"><?= htmlspecialchars($generated_bmc['relations_clients']) ?></p>
                                </div>
                                <div class="edit-btn-container">
                                    <button class="btn btn-sm btn-outline-primary edit-btn edit-block-btn" data-block="relations_clients" data-project-id="<?= $project_id ?>" data-bs-toggle="modal" data-bs-target="#editBlockModal">
                                        <i class="bi bi-pencil"></i> Éditer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="text-center mt-4">
                    <a href="generate_bmc.php" class="btn btn-primary">Créer un autre BMC</a>
                    <a href="download_pdf.php?project_id=<?= $project_id ?>" class="btn btn-primary ms-2">Télécharger en PDF</a>
                    <a href="hypotheses.php?project_id=<?= $project_id ?>&generate=1" class="btn btn-primary ms-2" id="generate-hypotheses-btn">Générer des hypothèses</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale pour éditer un bloc du BMC -->
    <div class="modal fade" id="editBlockModal" tabindex="-1" aria-labelledby="editBlockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBlockModalLabel">Éditer un bloc</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editBlockForm">
                        <input type="hidden" id="project_id" name="project_id">
                        <input type="hidden" id="block_name" name="block_name">
                        <div class="mb-3">
                            <label for="block_content" class="form-label">Contenu du bloc</label>
                            <textarea class="form-control" id="block_content" name="block_content" rows="5" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" form="editBlockForm">Enregistrer les modifications</button>
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
                    <p class="text-center">La modification a été effectuée avec succès.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale pour le loader -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-3">Veuillez patienter...</p>
                </div>
            </div>
        </div>
    </div>

    <?php include '../layouts/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        console.log('Document prêt - jQuery chargé');

        // Gestion de l'édition des blocs du BMC
        $('.edit-block-btn').on('click', function() {
            const blockName = $(this).data('block');
            const projectId = $(this).data('project-id');
            const blockContent = $(this).closest('.bmc-card').find('.bmc-content').text().trim();

            console.log('Ouverture de la modale pour éditer :', blockName, 'Project ID:', projectId, 'Contenu actuel:', blockContent);

            $('#editBlockModalLabel').text('Éditer : ' + blockName);
            $('#project_id').val(projectId);
            $('#block_name').val(blockName);
            $('#block_content').val(blockContent);
        });

        // Vérifier si le formulaire est bien détecté
        console.log('Formulaire détecté :', $('#editBlockForm').length);

        // Gestion de la soumission du formulaire avec délégation d'événements
        $(document).on('submit', '#editBlockForm', function(e) {
            e.preventDefault();
            console.log('Formulaire soumis - Événement submit déclenché');

            const projectId = $('#project_id').val();
            const blockName = $('#block_name').val();
            const blockContent = $('#block_content').val().trim();

            console.log('Données du formulaire :', {
                project_id: projectId,
                block_name: blockName,
                content: blockContent
            });

            if (!blockContent) {
                console.log('Erreur : Le contenu est vide');
                alert('Le contenu du bloc ne peut pas être vide.');
                return;
            }

            console.log('Envoi de la requête AJAX à update_bmc_block.php');
            $.ajax({
                url: 'update_bmc_block.php',
                method: 'POST',
                data: {
                    project_id: projectId,
                    block_name: blockName,
                    content: blockContent
                },
                success: function(response) {
                    console.log('Réponse de update_bmc_block.php :', response);
                    let result;
                    try {
                        result = JSON.parse(response);
                    } catch (e) {
                        console.error('Erreur lors du parsing de la réponse JSON :', e, 'Réponse brute :', response);
                        alert('Erreur : La réponse du serveur est invalide.');
                        return;
                    }

                    if (result.success) {
                        console.log('Mise à jour réussie pour le bloc :', blockName);
                        // Mettre à jour le contenu dans l'interface
                        const $blockElement = $(`.bmc-content[data-block="${blockName}"]`);
                        $blockElement.html(blockContent.replace(/\n/g, '<br>'));

                        // Ajouter un feedback visuel
                        $blockElement.closest('.bmc-card').addClass('bg-light');
                        setTimeout(() => {
                            $blockElement.closest('.bmc-card').removeClass('bg-light');
                        }, 1000);

                        // Afficher la modale de succès
                        $('#editBlockModal').modal('hide');
                        $('#successModal').modal('show');
                    } else {
                        console.error('Erreur signalée par le serveur :', result.message);
                        alert('Erreur lors de la mise à jour : ' + result.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX :', status, error, 'Détails :', xhr);
                    alert('Erreur lors de la communication avec le serveur : ' + error);
                }
            });
        });

        // Gestion du loader plein écran pour "Générer des hypothèses"
        $('#generate-hypotheses-btn').on('click', function(e) {
            $('#loadingModal').modal('show');
            $(this).addClass('disabled').prop('disabled', true);
        });

        // Vérifier si Bootstrap est chargé correctement
        console.log('Bootstrap Modal disponible :', typeof $.fn.modal !== 'undefined');
    });
    </script>
</body>
</html>