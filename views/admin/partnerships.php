<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

// Fonction IA pour analyser la complémentarité des BMC
function analyzeBMCSynergy($bmc1_data, $bmc2_data) {
    $synergy_score = 0;
    $synergy_reasons = array();
    
    // Extraire les données par bloc
    $bmc1_blocks = array();
    $bmc2_blocks = array();
    
    foreach ($bmc1_data as $block) {
        $bmc1_blocks[$block['block_name']] = $block['content'];
    }
    
    foreach ($bmc2_data as $block) {
        $bmc2_blocks[$block['block_name']] = $block['content'];
    }
    
    // Analyse des segments clients (complémentarité)
    if (isset($bmc1_blocks['Segments de clientèle']) && isset($bmc2_blocks['Segments de clientèle'])) {
        $segments1 = explode(',', $bmc1_blocks['Segments de clientèle']);
        $segments2 = explode(',', $bmc2_blocks['Segments de clientèle']);
        
        // Vérifier si les segments se complètent
        $complementary_segments = array_diff($segments1, $segments2);
        if (count($complementary_segments) > 0) {
            $synergy_score += 20;
            $synergy_reasons[] = "Segments clients complémentaires";
        }
    }
    
    // Analyse des ressources clés (partage possible)
    if (isset($bmc1_blocks['Ressources clés']) && isset($bmc2_blocks['Ressources clés'])) {
        $resources1 = explode(',', $bmc1_blocks['Ressources clés']);
        $resources2 = explode(',', $bmc2_blocks['Ressources clés']);
        
        $shared_resources = array_intersect($resources1, $resources2);
        if (count($shared_resources) > 0) {
            $synergy_score += 15;
            $synergy_reasons[] = "Ressources partagées identifiées";
        }
    }
    
    // Analyse des canaux de distribution
    if (isset($bmc1_blocks['Canaux']) && isset($bmc2_blocks['Canaux'])) {
        $channels1 = explode(',', $bmc1_blocks['Canaux']);
        $channels2 = explode(',', $bmc2_blocks['Canaux']);
        
        $complementary_channels = array_diff($channels1, $channels2);
        if (count($complementary_channels) > 0) {
            $synergy_score += 15;
            $synergy_reasons[] = "Canaux de distribution complémentaires";
        }
    }
    
    // Analyse des partenaires clés
    if (isset($bmc1_blocks['Partenaires clés']) && isset($bmc2_blocks['Partenaires clés'])) {
        $partners1 = explode(',', $bmc1_blocks['Partenaires clés']);
        $partners2 = explode(',', $bmc2_blocks['Partenaires clés']);
        
        $shared_partners = array_intersect($partners1, $partners2);
        if (count($shared_partners) > 0) {
            $synergy_score += 10;
            $synergy_reasons[] = "Partenaires communs";
        }
    }
    
    // Analyse des propositions de valeur
    if (isset($bmc1_blocks['Proposition de valeur']) && isset($bmc2_blocks['Proposition de valeur'])) {
        $value1 = strtolower($bmc1_blocks['Proposition de valeur']);
        $value2 = strtolower($bmc2_blocks['Proposition de valeur']);
        
        // Vérifier si les propositions se complètent
        if (strpos($value1, 'technologie') !== false && strpos($value2, 'distribution') !== false) {
            $synergy_score += 20;
            $synergy_reasons[] = "Propositions de valeur complémentaires";
        }
    }
    
    // Analyse des marchés cibles (target_market)
    if (isset($bmc1_blocks['Segments de clientèle']) && isset($bmc2_blocks['Segments de clientèle'])) {
        if ($bmc1_blocks['Segments de clientèle'] !== $bmc2_blocks['Segments de clientèle']) {
            $synergy_score += 10;
            $synergy_reasons[] = "Marchés cibles différents mais compatibles";
        }
    }
    
    return array(
        'score' => min(100, $synergy_score),
        'reasons' => $synergy_reasons
    );
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'generate_suggestions':
                // Générer les suggestions de partenariats
                try {
                    // Créer la table partnership_suggestions si elle n'existe pas
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS partnership_suggestions (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            project1_id INT NOT NULL,
                            project2_id INT NOT NULL,
                            synergy_score INT NOT NULL,
                            synergy_reasons TEXT,
                            status ENUM('pending', 'notified', 'active', 'rejected') DEFAULT 'pending',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            notified_at TIMESTAMP NULL,
                            FOREIGN KEY (project1_id) REFERENCES projects(id),
                            FOREIGN KEY (project2_id) REFERENCES projects(id)
                        )
                    ");
                    
                    // Récupérer tous les projets avec BMC complétés
                    $stmt = $pdo->query("
                        SELECT DISTINCT p.id, p.name, p.target_market, p.description
                        FROM projects p
                        INNER JOIN bmc b ON p.id = b.project_id
                        WHERE b.content IS NOT NULL
                        AND b.content != ''
                        AND b.content != 'Non spécifié'
                        ORDER BY p.created_at DESC
                    ");
                    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $suggestions = array();
                    
                    // Analyser chaque paire de projets
                    for ($i = 0; $i < count($projects); $i++) {
                        for ($j = $i + 1; $j < count($projects); $j++) {
                            // Récupérer les données BMC pour chaque projet
                            $stmt1 = $pdo->prepare("SELECT block_name, content FROM bmc WHERE project_id = ?");
                            $stmt1->execute([$projects[$i]['id']]);
                            $bmc1_data = $stmt1->fetchAll(PDO::FETCH_ASSOC);
                            
                            $stmt2 = $pdo->prepare("SELECT block_name, content FROM bmc WHERE project_id = ?");
                            $stmt2->execute([$projects[$j]['id']]);
                            $bmc2_data = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                            
                            $synergy = analyzeBMCSynergy($bmc1_data, $bmc2_data);
                            
                            if ($synergy['score'] >= 30) { // Seuil minimum de compatibilité
                                $suggestions[] = array(
                                    'project1' => $projects[$i],
                                    'project2' => $projects[$j],
                                    'synergy_score' => $synergy['score'],
                                    'synergy_reasons' => $synergy['reasons'],
                                    'created_at' => date('Y-m-d H:i:s')
                                );
                            }
                        }
                    }
                    
                    // Trier par score de synergie décroissant
                    usort($suggestions, function($a, $b) {
                        return $b['synergy_score'] - $a['synergy_score'];
                    });
                    
                    // Sauvegarder les suggestions
                    $stmt = $pdo->prepare("
                        INSERT INTO partnership_suggestions 
                        (project1_id, project2_id, synergy_score, synergy_reasons, status, created_at)
                        VALUES (?, ?, ?, ?, 'pending', ?)
                    ");
                    
                    foreach ($suggestions as $suggestion) {
                        $stmt->execute([
                            $suggestion['project1']['id'],
                            $suggestion['project2']['id'],
                            $suggestion['synergy_score'],
                            json_encode($suggestion['synergy_reasons']),
                            $suggestion['created_at']
                        ]);
                    }
                    
                    $success_message = count($suggestions) . " suggestions de partenariats générées avec succès !";
                    
                } catch (PDOException $e) {
                    $error_message = "Erreur lors de la génération des suggestions : " . $e->getMessage();
                }
                break;
                
            case 'send_notification':
                // Envoyer une notification aux utilisateurs
                $suggestion_id = $_POST['suggestion_id'];
                try {
                    $stmt = $pdo->prepare("
                        UPDATE partnership_suggestions 
                        SET status = 'notified', notified_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$suggestion_id]);
                    $success_message = "Notification envoyée avec succès !";
                } catch (PDOException $e) {
                    $error_message = "Erreur lors de l'envoi de la notification : " . $e->getMessage();
                }
                break;
        }
    }
}

// Récupérer les données
try {
    // Statistiques des partenariats
    $stats = array();
    
    // Nombre total de projets avec BMC complétés
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT p.id) as total 
        FROM projects p 
        JOIN bmc b ON p.id = b.project_id 
        WHERE b.content IS NOT NULL AND b.content != '' AND b.content != 'Non spécifié'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_consent'] = $result['total'] ?? 0;
    
    // Nombre de suggestions générées
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM partnership_suggestions");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_suggestions'] = $result['total'] ?? 0;
    
    // Nombre de partenariats actifs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM partnership_suggestions WHERE status = 'active'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['active_partnerships'] = $result['total'] ?? 0;
    
    // Suggestions récentes
    $stmt = $pdo->query("
        SELECT 
            ps.*,
            p1.name as project1_name,
            p1.target_market as project1_target_market,
            p2.name as project2_name,
            p2.target_market as project2_target_market
        FROM partnership_suggestions ps
        JOIN projects p1 ON ps.project1_id = p1.id
        JOIN projects p2 ON ps.project2_id = p2.id
        ORDER BY ps.created_at DESC
        LIMIT 20
    ");
    $recent_suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('Erreur partnerships : ' . $e->getMessage());
    $stats = array_fill_keys(['total_consent', 'total_suggestions', 'active_partnerships'], 0);
    $recent_suggestions = array();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partenariats B2B - Laila Workspace Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
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
                        <a class="nav-link active" href="partnerships.php" role="menuitem" aria-current="page">
                            <i class="bi bi-handshake" aria-hidden="true"></i> Partenariats B2B
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
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="sidebar">
                    <h6>Partenariats B2B</h6>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="partnerships.php">
                                <i class="bi bi-handshake"></i> Matching IA
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="partnership_suggestions.php">
                                <i class="bi bi-lightbulb"></i> Suggestions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="consent_management.php">
                                <i class="bi bi-shield-check"></i> Consentements
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="col-md-9 col-lg-10">
                <!-- En-tête -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 style="color: var(--primary); margin: 0;">
                        <i class="bi bi-handshake"></i> Matching Partenariats B2B
                    </h1>
                    <div class="d-flex gap-2">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="generate_suggestions">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-magic"></i> Générer Suggestions IA
                            </button>
                        </form>
                        <button class="btn btn-outline-secondary" onclick="refreshData()">
                            <i class="bi bi-arrow-clockwise"></i> Actualiser
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- KPIs -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-primary">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div class="kpi-content">
                                    <h3><?= number_format($stats['total_consent']) ?></h3>
                                    <p>Avec Consentement</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-info">
                                    <i class="bi bi-lightbulb"></i>
                                </div>
                                <div class="kpi-content">
                                    <h3><?= number_format($stats['total_suggestions']) ?></h3>
                                    <p>Suggestions</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-success">
                                    <i class="bi bi-handshake"></i>
                                </div>
                                <div class="kpi-content">
                                    <h3><?= number_format($stats['active_partnerships']) ?></h3>
                                    <p>Actifs</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Algorithme IA -->
                <div class="admin-card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-cpu"></i> Algorithme IA</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Critères d'Analyse :</h6>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-check-circle text-success"></i> Segments clients</li>
                                    <li><i class="bi bi-check-circle text-success"></i> Ressources partagées</li>
                                    <li><i class="bi bi-check-circle text-success"></i> Canaux de distribution</li>
                                    <li><i class="bi bi-check-circle text-success"></i> Partenaires communs</li>
                                    <li><i class="bi bi-check-circle text-success"></i> Propositions de valeur</li>
                                    <li><i class="bi bi-check-circle text-success"></i> Marchés cibles</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Seuils de Compatibilité :</h6>
                                <div class="progress mb-2" style="height: 25px;">
                                    <div class="progress-bar bg-danger" style="width: 30%">30% - Minimum</div>
                                </div>
                                <div class="progress mb-2" style="height: 25px;">
                                    <div class="progress-bar bg-warning" style="width: 50%">50% - Bon</div>
                                </div>
                                <div class="progress mb-2" style="height: 25px;">
                                    <div class="progress-bar bg-success" style="width: 70%">70% - Excellent</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Suggestions récentes -->
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-list-ul"></i> Suggestions Récentes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_suggestions)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-lightbulb display-1 text-muted"></i>
                            <p class="text-muted">Aucune suggestion générée</p>
                            <p class="text-muted">Cliquez sur "Générer Suggestions IA" pour commencer</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Projet 1</th>
                                        <th>Projet 2</th>
                                        <th>Score</th>
                                        <th>Raisons</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_suggestions as $suggestion): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($suggestion['project1_name'] ?? '') ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($suggestion['project1_target_market'] ?? 'Non spécifié') ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($suggestion['project2_name'] ?? '') ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($suggestion['project2_target_market'] ?? 'Non spécifié') ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar <?= $suggestion['synergy_score'] >= 70 ? 'bg-success' : ($suggestion['synergy_score'] >= 50 ? 'bg-warning' : 'bg-danger') ?>" 
                                                     style="width: <?= $suggestion['synergy_score'] ?>%">
                                                    <?= $suggestion['synergy_score'] ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $reasons = null;
                                            if (!empty($suggestion['synergy_reasons'])) {
                                                $reasons = json_decode($suggestion['synergy_reasons'], true);
                                            }
                                            if ($reasons && is_array($reasons)): ?>
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach (array_slice($reasons, 0, 2) as $reason): ?>
                                                <li><small><?= htmlspecialchars($reason ?? '') ?></small></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php else: ?>
                                            <small class="text-muted">Aucune raison spécifiée</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            switch ($suggestion['status']) {
                                                case 'pending':
                                                    $status_class = 'bg-warning';
                                                    $status_text = 'En attente';
                                                    break;
                                                case 'notified':
                                                    $status_class = 'bg-info';
                                                    $status_text = 'Notifié';
                                                    break;
                                                case 'active':
                                                    $status_class = 'bg-success';
                                                    $status_text = 'Actif';
                                                    break;
                                                default:
                                                    $status_class = 'bg-secondary';
                                                    $status_text = 'Inconnu';
                                            }
                                            ?>
                                            <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($suggestion['created_at'])) ?></td>
                                        <td>
                                            <?php if ($suggestion['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="send_notification">
                                                <input type="hidden" name="suggestion_id" value="<?= $suggestion['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-bell"></i> Notifier
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="viewDetails(<?= $suggestion['id'] ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mb-2">
                        <i class="bi bi-shield-lock"></i> Laila Workspace - Back-office
                    </h6>
                    <p class="mb-0 text-muted small">
                        Plateforme de gestion Business Model Canvas - Version 2.0
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex flex-column flex-md-row justify-content-md-end align-items-md-center">
                        <div class="me-md-3 mb-2 mb-md-0">
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i> IA Actif
                            </span>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-clock"></i> Dernière mise à jour : <?= date('d/m/Y H:i') ?>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            © 2024 Laila Workspace - Tous droits réservés
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function refreshData() {
            location.reload();
        }

        function viewDetails(suggestionId) {
            // Ouvrir les détails de la suggestion dans l'espace admin
            window.open('partnership_details.php?id=' + suggestionId, '_blank');
        }
    </script>
</body>
</html> 