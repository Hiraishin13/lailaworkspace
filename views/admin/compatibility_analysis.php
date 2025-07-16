<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

// Récupérer les IDs des projets
$project1_id = $_GET['project1'] ?? null;
$project2_id = $_GET['project2'] ?? null;

if (!$project1_id || !$project2_id) {
    header('Location: partnership_suggestions.php');
    exit();
}

// Récupérer les données des projets
try {
    // Projet 1
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            u.first_name as user_first_name,
            u.last_name as user_last_name,
            u.email as user_email
        FROM projects p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$project1_id]);
    $project1 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Projet 2
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            u.first_name as user_first_name,
            u.last_name as user_last_name,
            u.email as user_email
        FROM projects p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$project2_id]);
    $project2 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project1 || !$project2) {
        header('Location: partnership_suggestions.php');
        exit();
    }
    
    // BMC du projet 1
    $stmt = $pdo->prepare("SELECT block_name, content FROM bmc WHERE project_id = ?");
    $stmt->execute([$project1_id]);
    $bmc1 = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // BMC du projet 2
    $stmt = $pdo->prepare("SELECT block_name, content FROM bmc WHERE project_id = ?");
    $stmt->execute([$project2_id]);
    $bmc2 = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Hypothèses du projet 1
    $stmt = $pdo->prepare("SELECT * FROM hypotheses WHERE project_id = ? ORDER BY created_at DESC");
    $stmt->execute([$project1_id]);
    $hypotheses1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hypothèses du projet 2
    $stmt = $pdo->prepare("SELECT * FROM hypotheses WHERE project_id = ? ORDER BY created_at DESC");
    $stmt->execute([$project2_id]);
    $hypotheses2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Plan financier du projet 1
    $stmt = $pdo->prepare("SELECT * FROM financial_plans WHERE project_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$project1_id]);
    $financial1 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Plan financier du projet 2
    $stmt = $pdo->prepare("SELECT * FROM financial_plans WHERE project_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$project2_id]);
    $financial2 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculer les scores de compatibilité
    $compatibility_scores = array();
    
    // Score par secteur (si identique)
    if ($project1['target_market'] && $project2['target_market'] && 
        strtolower($project1['target_market']) === strtolower($project2['target_market'])) {
        $compatibility_scores['sector'] = array(
            'score' => 90,
            'reason' => 'Secteurs identiques',
            'details' => 'Les deux projets ciblent le même marché : ' . $project1['target_market']
        );
    } elseif ($project1['target_market'] && $project2['target_market']) {
        $compatibility_scores['sector'] = array(
            'score' => 40,
            'reason' => 'Secteurs différents',
            'details' => 'Marchés différents : ' . $project1['target_market'] . ' vs ' . $project2['target_market']
        );
    } else {
        $compatibility_scores['sector'] = array(
            'score' => 20,
            'reason' => 'Secteur non défini',
            'details' => 'Un ou les deux projets n\'ont pas de secteur défini'
        );
    }
    
    // Score par partenaires clés
    if (isset($bmc1['partenaires_cles']) && isset($bmc2['partenaires_cles']) && 
        $bmc1['partenaires_cles'] && $bmc2['partenaires_cles'] && 
        $bmc1['partenaires_cles'] !== 'Non spécifié' && $bmc2['partenaires_cles'] !== 'Non spécifié') {
        
        $partners1 = explode(',', $bmc1['partenaires_cles']);
        $partners2 = explode(',', $bmc2['partenaires_cles']);
        $common_partners = array_intersect($partners1, $partners2);
        
        if (count($common_partners) > 0) {
            $compatibility_scores['partners'] = array(
                'score' => 85,
                'reason' => 'Partenaires communs',
                'details' => count($common_partners) . ' partenaire(s) commun(s) : ' . implode(', ', $common_partners)
            );
        } else {
            $compatibility_scores['partners'] = array(
                'score' => 30,
                'reason' => 'Partenaires différents',
                'details' => 'Aucun partenaire commun identifié'
            );
        }
    } else {
        $compatibility_scores['partners'] = array(
            'score' => 15,
            'reason' => 'Partenaires non définis',
            'details' => 'Un ou les deux projets n\'ont pas de partenaires définis'
        );
    }
    
    // Score par ressources vs canaux (complémentarité)
    if (isset($bmc1['ressources_cles']) && isset($bmc2['canaux']) && 
        $bmc1['ressources_cles'] && $bmc2['canaux'] && 
        $bmc1['ressources_cles'] !== 'Non spécifié' && $bmc2['canaux'] !== 'Non spécifié') {
        $compatibility_scores['complementarity'] = array(
            'score' => 75,
            'reason' => 'Complémentarité potentielle',
            'details' => 'Ressources du projet 1 peuvent compléter les canaux du projet 2'
        );
    } elseif (isset($bmc2['ressources_cles']) && isset($bmc1['canaux']) && 
              $bmc2['ressources_cles'] && $bmc1['canaux'] && 
              $bmc2['ressources_cles'] !== 'Non spécifié' && $bmc1['canaux'] !== 'Non spécifié') {
        $compatibility_scores['complementarity'] = array(
            'score' => 75,
            'reason' => 'Complémentarité potentielle',
            'details' => 'Ressources du projet 2 peuvent compléter les canaux du projet 1'
        );
    } else {
        $compatibility_scores['complementarity'] = array(
            'score' => 25,
            'reason' => 'Pas de complémentarité évidente',
            'details' => 'Aucune complémentarité ressources/canaux identifiée'
        );
    }
    
    // Score par stade de développement
    $stage1 = count($hypotheses1) + (isset($financial1) ? 1 : 0);
    $stage2 = count($hypotheses2) + (isset($financial2) ? 1 : 0);
    $stage_diff = abs($stage1 - $stage2);
    
    if ($stage_diff <= 1) {
        $compatibility_scores['stage'] = array(
            'score' => 80,
            'reason' => 'Stades similaires',
            'details' => 'Les deux projets sont à des stades de développement similaires'
        );
    } elseif ($stage_diff <= 3) {
        $compatibility_scores['stage'] = array(
            'score' => 60,
            'reason' => 'Stades modérément différents',
            'details' => 'Différence modérée dans les stades de développement'
        );
    } else {
        $compatibility_scores['stage'] = array(
            'score' => 30,
            'reason' => 'Stades très différents',
            'details' => 'Grande différence dans les stades de développement'
        );
    }
    
    // Score global
    $total_score = array_sum(array_column($compatibility_scores, 'score')) / count($compatibility_scores);
    
} catch (PDOException $e) {
    error_log('Erreur analyse compatibilité : ' . $e->getMessage());
    header('Location: partnership_suggestions.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back-office Laila Workspace - Analyse de Compatibilité</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <!-- Navbar Admin -->
    <nav class="navbar navbar-expand-lg navbar-dark" role="navigation" aria-label="Navigation principale">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php" title="Retour au dashboard principal">
                <i class="bi bi-shield-lock fs-4 me-2" aria-hidden="true"></i>
                <span class="fw-bold">Laila Workspace</span>
                <small class="ms-2 text-success">Admin</small>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin" 
                    aria-controls="navbarAdmin" aria-expanded="false" aria-label="Basculer la navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarAdmin">
                <ul class="navbar-nav me-auto" role="menubar">
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="dashboard.php" role="menuitem">
                            <i class="bi bi-speedometer2" aria-hidden="true"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="users.php" role="menuitem">
                            <i class="bi bi-people" aria-hidden="true"></i> Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="projects.php" role="menuitem">
                            <i class="bi bi-kanban" aria-hidden="true"></i> Projets
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="analytics.php" role="menuitem">
                            <i class="bi bi-graph-up" aria-hidden="true"></i> Analytics
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="partnerships.php" role="menuitem">
                            <i class="bi bi-handshake" aria-hidden="true"></i> Partenariats B2B
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="partnership_suggestions.php" role="menuitem">
                            <i class="bi bi-lightbulb" aria-hidden="true"></i> Suggestions
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link" href="audit.php" role="menuitem">
                            <i class="bi bi-shield-check" aria-hidden="true"></i> Audit
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav" role="menubar">
                    <li class="nav-item dropdown" role="none">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle" aria-hidden="true"></i>
                            <span class="ms-1 d-none d-lg-inline"><?= htmlspecialchars($_SESSION['user_role']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" role="menu">
                            <li role="none"><a class="dropdown-item" href="profile.php" role="menuitem">Mon Profil</a></li>
                            <li role="none"><a class="dropdown-item" href="settings.php" role="menuitem">Paramètres</a></li>
                            <li role="none"><hr class="dropdown-divider"></li>
                            <li role="none"><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/views/auth/logout.php" role="menuitem">Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 style="color: var(--primary); margin: 0;">
                    <i class="bi bi-graph-up"></i> Analyse de Compatibilité
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="partnership_suggestions.php">Suggestions</a></li>
                        <li class="breadcrumb-item active">Analyse Compatibilité</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <a href="partnership_suggestions.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
                <button class="btn btn-success" onclick="createPartnership()">
                    <i class="bi bi-plus-circle"></i> Créer Partenariat
                </button>
            </div>
        </div>

        <!-- Score global -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="admin-card">
                    <div class="card-body text-center">
                        <h3>Score de Compatibilité Global</h3>
                        <div class="compatibility-score">
                            <div class="score-circle <?= $total_score >= 70 ? 'high' : ($total_score >= 50 ? 'medium' : 'low') ?>">
                                <span class="score-number"><?= round($total_score) ?>%</span>
                                <span class="score-label">Compatibilité</span>
                            </div>
                        </div>
                        <p class="mt-3">
                            <?php if ($total_score >= 70): ?>
                                <span class="badge bg-success fs-6">Excellente compatibilité</span>
                            <?php elseif ($total_score >= 50): ?>
                                <span class="badge bg-warning fs-6">Compatibilité modérée</span>
                            <?php else: ?>
                                <span class="badge bg-danger fs-6">Compatibilité faible</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Projet 1 -->
            <div class="col-md-6">
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-kanban"></i> Projet 1</h5>
                    </div>
                    <div class="card-body">
                        <h6><?= htmlspecialchars($project1['name']) ?></h6>
                        <p class="text-muted"><?= htmlspecialchars($project1['description']) ?></p>
                        
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Créé par</small><br>
                                <strong><?= htmlspecialchars(($project1['user_first_name'] ? $project1['user_first_name'] . ' ' : '') . ($project1['user_last_name'] ? $project1['user_last_name'] : 'Utilisateur ' . $project1['user_id'])) ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Secteur</small><br>
                                <strong><?= htmlspecialchars($project1['target_market'] ?? 'Non défini') ?></strong>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-6">
                                <small class="text-muted">Date création</small><br>
                                <strong><?= date('d/m/Y', strtotime($project1['created_at'])) ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Statut</small><br>
                                <span class="badge bg-<?= ($project1['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($project1['status'] ?? 'active') ?>
                                </span>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6>BMC - Points clés</h6>
                        <div class="bmc-summary">
                            <?php 
                            $key_blocks = ['proposition_valeur', 'segments_clientele', 'partenaires_cles', 'ressources_cles'];
                            foreach ($key_blocks as $block):
                                $content = $bmc1[$block] ?? 'Non spécifié';
                                if ($content && $content !== 'Non spécifié'):
                            ?>
                            <div class="bmc-item">
                                <strong><?= ucfirst(str_replace('_', ' ', $block)) ?>:</strong>
                                <span><?= htmlspecialchars(substr($content, 0, 100)) ?>...</span>
                            </div>
                            <?php endif; endforeach; ?>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-lightbulb"></i> <?= count($hypotheses1) ?> hypothèse(s) |
                                <i class="bi bi-calculator"></i> <?= isset($financial1) ? 'Plan financier' : 'Aucun plan' ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Projet 2 -->
            <div class="col-md-6">
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-kanban"></i> Projet 2</h5>
                    </div>
                    <div class="card-body">
                        <h6><?= htmlspecialchars($project2['name']) ?></h6>
                        <p class="text-muted"><?= htmlspecialchars($project2['description']) ?></p>
                        
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Créé par</small><br>
                                <strong><?= htmlspecialchars(($project2['user_first_name'] ? $project2['user_first_name'] . ' ' : '') . ($project2['user_last_name'] ? $project2['user_last_name'] : 'Utilisateur ' . $project2['user_id'])) ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Secteur</small><br>
                                <strong><?= htmlspecialchars($project2['target_market'] ?? 'Non défini') ?></strong>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-6">
                                <small class="text-muted">Date création</small><br>
                                <strong><?= date('d/m/Y', strtotime($project2['created_at'])) ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Statut</small><br>
                                <span class="badge bg-<?= ($project2['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($project2['status'] ?? 'active') ?>
                                </span>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6>BMC - Points clés</h6>
                        <div class="bmc-summary">
                            <?php 
                            foreach ($key_blocks as $block):
                                $content = $bmc2[$block] ?? 'Non spécifié';
                                if ($content && $content !== 'Non spécifié'):
                            ?>
                            <div class="bmc-item">
                                <strong><?= ucfirst(str_replace('_', ' ', $block)) ?>:</strong>
                                <span><?= htmlspecialchars(substr($content, 0, 100)) ?>...</span>
                            </div>
                            <?php endif; endforeach; ?>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-lightbulb"></i> <?= count($hypotheses2) ?> hypothèse(s) |
                                <i class="bi bi-calculator"></i> <?= isset($financial2) ? 'Plan financier' : 'Aucun plan' ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Détail des scores -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-bar-chart"></i> Détail des Critères de Compatibilité</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($compatibility_scores as $criterion => $data): ?>
                            <div class="col-md-6 mb-3">
                                <div class="compatibility-criterion">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6><?= ucfirst($criterion) ?></h6>
                                        <span class="badge bg-<?= $data['score'] >= 70 ? 'success' : ($data['score'] >= 50 ? 'warning' : 'danger') ?>">
                                            <?= $data['score'] ?>%
                                        </span>
                                    </div>
                                    <div class="progress mb-2" style="height: 8px;">
                                        <div class="progress-bar bg-<?= $data['score'] >= 70 ? 'success' : ($data['score'] >= 50 ? 'warning' : 'danger') ?>" 
                                             style="width: <?= $data['score'] ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?= $data['reason'] ?></small>
                                    <p class="mb-0 small"><?= $data['details'] ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommandations -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-lightbulb"></i> Recommandations</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($total_score >= 70): ?>
                        <div class="alert alert-success">
                            <h6><i class="bi bi-check-circle"></i> Partenariat Recommandé</h6>
                            <p>Ces deux projets présentent une excellente compatibilité. Un partenariat pourrait être très bénéfique pour les deux parties.</p>
                            <ul>
                                <li>Organiser une réunion entre les porteurs de projet</li>
                                <li>Définir les modalités de collaboration</li>
                                <li>Établir un plan d'action commun</li>
                            </ul>
                        </div>
                        <?php elseif ($total_score >= 50): ?>
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-exclamation-triangle"></i> Partenariat Possible</h6>
                            <p>La compatibilité est modérée. Un partenariat pourrait être envisagé avec des ajustements.</p>
                            <ul>
                                <li>Analyser les points de divergence</li>
                                <li>Identifier les synergies potentielles</li>
                                <li>Évaluer les compromis nécessaires</li>
                            </ul>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-danger">
                            <h6><i class="bi bi-x-circle"></i> Partenariat Non Recommandé</h6>
                            <p>La compatibilité est faible. Un partenariat pourrait être difficile à mettre en place.</p>
                            <ul>
                                <li>Les projets sont trop différents</li>
                                <li>Peu de synergies identifiées</li>
                                <li>Risque de conflits d'intérêts</li>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'template_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function createPartnership() {
            if (confirm('Créer un partenariat entre ces deux projets ?')) {
                // Logique de création de partenariat
                alert('Partenariat créé avec succès !');
                window.location.href = 'partnerships.php';
            }
        }
    </script>

    <style>
        .compatibility-score {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .score-circle.high {
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        
        .score-circle.medium {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
        }
        
        .score-circle.low {
            background: linear-gradient(45deg, #dc3545, #e83e8c);
        }
        
        .score-number {
            font-size: 2rem;
            line-height: 1;
        }
        
        .score-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        .bmc-summary {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .bmc-item {
            margin-bottom: 10px;
            padding: 8px;
            background: var(--light);
            border-radius: 4px;
        }
        
        .compatibility-criterion {
            padding: 15px;
            border: 1px solid var(--light);
            border-radius: 8px;
            background: #f8f9fa;
        }
    </style>
</body>
</html> 