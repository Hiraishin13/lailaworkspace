<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/AIService.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../../auth/login.php');
    exit();
}

$ai_service = new AIService();
$suggestions = [];
$market_trends = null;
$error_message = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'generate_suggestions':
                try {
                    // Récupérer tous les projets
                    $stmt = $pdo->prepare("
                        SELECT p.*, u.first_name, u.last_name 
                        FROM projects p 
                        JOIN users u ON p.user_id = u.id 
                        WHERE p.status = 'active'
                    ");
                    $stmt->execute();
                    $all_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Générer des suggestions pour chaque projet
                    foreach ($all_projects as $project) {
                        $project_data = getProjectDataForAI($pdo, $project['id']);
                        if ($project_data) {
                            $ai_suggestions = $ai_service->generatePartnershipSuggestions($project_data, $all_projects);
                            foreach ($ai_suggestions as $suggestion) {
                                $suggestions[] = [
                                    'source_project' => $project,
                                    'suggestion' => $suggestion
                                ];
                            }
                        }
                    }
                } catch (Exception $e) {
                    $error_message = "Erreur lors de la génération des suggestions : " . $e->getMessage();
                }
                break;
                
            case 'analyze_trends':
                try {
                    // Récupérer les données des projets pour l'analyse des tendances
                    $stmt = $pdo->prepare("
                        SELECT p.*, 
                               (SELECT COUNT(*) FROM hypotheses WHERE project_id = p.id AND status = 'validated') as validated_hypotheses,
                               (SELECT profit_margin FROM financial_plans WHERE project_id = p.id LIMIT 1) as profit_margin
                        FROM projects p 
                        WHERE p.status = 'active'
                    ");
                    $stmt->execute();
                    $projects_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $market_trends = $ai_service->analyzeMarketTrends($projects_data);
                } catch (Exception $e) {
                    $error_message = "Erreur lors de l'analyse des tendances : " . $e->getMessage();
                }
                break;
        }
    }
}

// Fonction pour récupérer les données d'un projet pour l'IA
function getProjectDataForAI($pdo, $project_id) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) return null;
    
    // Récupérer les blocs BMC
    $stmt = $pdo->prepare("SELECT block_name, content FROM bmc WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $bmc_blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $bmc_data = [];
    foreach ($bmc_blocks as $block) {
        $bmc_data[$block['block_name']] = $block['content'];
    }
    
    return [
        'name' => $project['name'],
        'description' => $project['description'],
        'target_market' => $project['target_market'],
        'segments' => $bmc_data['Segments de clientèle'] ?? 'Non défini',
        'value_proposition' => $bmc_data['Proposition de valeur'] ?? 'Non défini'
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suggestions IA - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <style>
        .ai-card {
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
        }
        .synergy-score {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
        }
        .trend-card {
            border-left: 4px solid #28a745;
            margin-bottom: 15px;
        }
        .opportunity-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'template_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'template_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-robot text-primary"></i>
                        Suggestions IA de Partenariats
                    </h1>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Actions IA -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-magic"></i>
                                    Génération de Suggestions
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">L'IA analysera tous les projets actifs et générera des suggestions de partenariats intelligentes.</p>
                                <form method="POST">
                                    <input type="hidden" name="action" value="generate_suggestions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-brain"></i>
                                        Générer les Suggestions IA
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line"></i>
                                    Analyse des Tendances
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">L'IA analysera les tendances du marché basées sur les projets existants.</p>
                                <form method="POST">
                                    <input type="hidden" name="action" value="analyze_trends">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-chart-bar"></i>
                                        Analyser les Tendances
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Suggestions IA -->
                <?php if (!empty($suggestions)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-lightbulb text-warning"></i>
                                    Suggestions de Partenariats IA
                                    <span class="badge bg-primary ms-2"><?php echo count($suggestions); ?> suggestions</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($suggestions as $item): ?>
                                    <div class="card ai-card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <h6 class="card-title">
                                                        <i class="fas fa-handshake text-primary"></i>
                                                        <?php echo htmlspecialchars($item['source_project']['name']); ?> 
                                                        <i class="fas fa-arrow-right text-muted mx-2"></i>
                                                        <?php echo htmlspecialchars($item['suggestion']['project_name']); ?>
                                                    </h6>
                                                    
                                                    <div class="mb-2">
                                                        <span class="synergy-score"><?php echo $item['suggestion']['synergy_score']; ?>%</span>
                                                        <span class="text-muted">de synergie</span>
                                                    </div>
                                                    
                                                    <div class="mb-2">
                                                        <strong>Raisons de la synergie :</strong>
                                                        <ul class="list-unstyled ms-3">
                                                            <?php foreach ($item['suggestion']['synergy_reasons'] as $reason): ?>
                                                                <li><i class="fas fa-check text-success me-2"></i><?php echo htmlspecialchars($reason); ?></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                    
                                                    <div class="mb-2">
                                                        <strong>Bénéfices potentiels :</strong>
                                                        <ul class="list-unstyled ms-3">
                                                            <?php foreach ($item['suggestion']['potential_benefits'] as $benefit): ?>
                                                                <li><i class="fas fa-star text-warning me-2"></i><?php echo htmlspecialchars($benefit); ?></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                    
                                                    <div class="mb-2">
                                                        <strong>Stratégie d'implémentation :</strong>
                                                        <p class="text-muted ms-3"><?php echo htmlspecialchars($item['suggestion']['implementation_strategy']); ?></p>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <div class="d-grid gap-2">
                                                        <a href="partnership_details.php?project1=<?php echo $item['source_project']['id']; ?>&project2=<?php echo $item['suggestion']['project_id']; ?>" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                            Voir les Détails
                                                        </a>
                                                        <a href="partnerships.php?action=create&project1=<?php echo $item['source_project']['id']; ?>&project2=<?php echo $item['suggestion']['project_id']; ?>" class="btn btn-outline-success btn-sm">
                                                            <i class="fas fa-plus"></i>
                                                            Créer le Partenariat
                                                        </a>
                                                        <a href="send_notifications.php?type=partnership&project1=<?php echo $item['source_project']['id']; ?>&project2=<?php echo $item['suggestion']['project_id']; ?>" class="btn btn-outline-info btn-sm">
                                                            <i class="fas fa-share"></i>
                                                            Notifier les Utilisateurs
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Analyse des Tendances -->
                <?php if ($market_trends): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line text-success"></i>
                                    Analyse des Tendances du Marché
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Tendances du marché -->
                                <?php if (isset($market_trends['market_trends'])): ?>
                                <div class="mb-4">
                                    <h6><i class="fas fa-trending-up"></i> Tendances Identifiées</h6>
                                    <?php foreach ($market_trends['market_trends'] as $trend): ?>
                                        <div class="card trend-card">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($trend['trend']); ?></h6>
                                                <p class="text-muted"><?php echo htmlspecialchars($trend['description']); ?></p>
                                                <p><strong>Impact :</strong> <?php echo htmlspecialchars($trend['impact']); ?></p>
                                                <div>
                                                    <strong>Opportunités :</strong>
                                                    <?php foreach ($trend['opportunities'] as $opportunity): ?>
                                                        <span class="badge opportunity-badge me-1"><?php echo htmlspecialchars($opportunity); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <!-- Analyse des secteurs -->
                                <?php if (isset($market_trends['sector_analysis'])): ?>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-fire text-danger"></i> Secteurs en Vogue</h6>
                                        <div class="card">
                                            <div class="card-body">
                                                <?php foreach ($market_trends['sector_analysis']['hot_sectors'] as $sector): ?>
                                                    <span class="badge bg-danger me-1 mb-1"><?php echo htmlspecialchars($sector); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-seedling text-success"></i> Opportunités Émergentes</h6>
                                        <div class="card">
                                            <div class="card-body">
                                                <?php foreach ($market_trends['sector_analysis']['emerging_opportunities'] as $opportunity): ?>
                                                    <span class="badge bg-success me-1 mb-1"><?php echo htmlspecialchars($opportunity); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Recommandations -->
                                <?php if (isset($market_trends['recommendations'])): ?>
                                <div class="mb-4">
                                    <h6><i class="fas fa-lightbulb text-warning"></i> Recommandations Stratégiques</h6>
                                    <div class="card">
                                        <div class="card-body">
                                            <ul class="list-unstyled">
                                                <?php foreach ($market_trends['recommendations'] as $recommendation): ?>
                                                    <li class="mb-2">
                                                        <i class="fas fa-arrow-right text-primary me-2"></i>
                                                        <?php echo htmlspecialchars($recommendation); ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <?php include 'template_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 