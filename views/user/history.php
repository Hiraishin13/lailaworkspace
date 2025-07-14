<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Charger tous les BMC générés par l'utilisateur
$projects = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.id, 
            p.name, 
            p.created_at,
            COUNT(h.id) as hypotheses_count,
            COUNT(CASE WHEN h.status = 'validated' THEN 1 END) as validated_hypotheses
        FROM projects p 
        LEFT JOIN hypotheses h ON p.id = h.project_id 
        WHERE p.user_id = :user_id 
        GROUP BY p.id, p.name, p.created_at
        ORDER BY p.created_at DESC
    ");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération de l'historique des projets : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            flex: 1 0 auto;
            padding-bottom: 60px;
        }
        .footer-modern {
            flex-shrink: 0;
            width: 100%;
            margin-top: auto;
        }
        .user-info-box {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2);
        }
        .user-info-box i {
            font-size: 1.5rem;
            margin-top: 0.2rem;
        }
        .section-title {
            color: #007bff;
            font-weight: 700;
            margin-bottom: 2rem;
        }
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
        }
        .card-title {
            font-weight: 600;
            color: #007bff;
        }
        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            background-color: #007bff !important;
        }
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        .text-primary {
            color: #007bff !important;
        }
        .text-success {
            color: #28a745 !important;
        }
        @media (max-width: 768px) {
            .user-info-box {
                flex-direction: column;
                text-align: center;
            }
            .user-info-box i {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../layouts/navbar.php'; ?>
    
    <div class="container my-5">
        <!-- Section info/conseil utilisateur -->
        <div class="user-info-box mb-4">
            <i class="bi bi-info-circle"></i>
            <div>
                <strong>Historique de vos BMC générés</strong><br>
                Retrouvez ici tous vos Business Model Canvas créés, avec leurs statistiques et dates de création.<br>
                <span class="text-muted">Astuce : utilisez les actions pour consulter, modifier ou analyser vos BMC.</span>
            </div>
        </div>

        <!-- Afficher les messages d'erreur -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <h2 class="section-title text-center mb-5">
            <i class="bi bi-clock-history me-2"></i>Historique des BMC Générés
        </h2>

        <?php if (empty($projects)): ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                </div>
                <h4 class="text-muted mb-3">Aucun BMC créé pour le moment</h4>
                <p class="text-muted mb-4">Commencez par créer votre premier Business Model Canvas pour voir apparaître votre historique ici.</p>
                <a href="../bmc/generate_bmc.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Créer mon premier BMC
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($projects as $project): ?>
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title text-primary mb-0">
                                        <i class="bi bi-briefcase me-2"></i><?= htmlspecialchars($project['name']) ?>
                                    </h5>
                                    <span class="badge bg-primary">#<?= $project['id'] ?></span>
                                </div>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <div class="border-end">
                                            <div class="h4 text-primary mb-0"><?= $project['hypotheses_count'] ?></div>
                                            <small class="text-muted">Hypothèses</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="h4 text-success mb-0"><?= $project['validated_hypotheses'] ?></div>
                                        <small class="text-muted">Validées</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar-plus me-1"></i>Créé le <?= date('d/m/Y à H:i', strtotime($project['created_at'])) ?>
                                    </small>
                                </div>

                                <div class="d-grid gap-2">
                                    <a href="../bmc/visualisation.php?project_id=<?= $project['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-eye me-1"></i>Voir le BMC
                                    </a>
                                    <a href="../bmc/hypotheses.php?project_id=<?= $project['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-list-check me-1"></i>Gérer les hypothèses
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-5">
                <div class="alert alert-info">
                    <i class="bi bi-lightbulb me-2"></i>
                    <strong>Conseil :</strong> Gardez vos BMC à jour en validant régulièrement vos hypothèses et en ajustant votre modèle selon les retours du marché.
                </div>
                <a href="../bmc/generate_bmc.php" class="btn btn-success">
                    <i class="bi bi-plus-circle me-2"></i>Créer un nouveau BMC
                </a>
            </div>
        <?php endif; ?>
    </div>
    <?php include '../layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 