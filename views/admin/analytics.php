<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

// Récupérer les données analytics avec anonymisation
try {
    // Statistiques générales
    $stats = array();
    
    // Nombre total de projets
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM projects");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_projects'] = $result['total'] ?? 0;
    
    // Nombre d'utilisateurs actifs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role_id = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['active_users'] = $result['total'] ?? 0;
    
    // Projets avec BMC complétés
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT p.id) as completed 
        FROM projects p 
        WHERE (
            SELECT COUNT(*) 
            FROM bmc b 
            WHERE b.project_id = p.id AND b.content != '' AND b.content != 'Non spécifié'
        ) >= 5
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['completed_bmc'] = $result['completed'] ?? 0;
    
    // Taux de conversion
    $stats['conversion_rate'] = $stats['total_projects'] > 0 ? round(($stats['completed_bmc'] / $stats['total_projects']) * 100, 1) : 0;
    
    // Données pour les graphiques
    $chart_data = array();
    
    // Évolution des projets par mois (6 derniers mois)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM projects 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month 
        ORDER BY month
    ");
    $chart_data['projects_evolution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Répartition par secteur (anonymisé)
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN target_market IS NULL OR target_market = '' THEN 'Non spécifié'
                ELSE target_market 
            END as sector,
            COUNT(*) as count
        FROM projects 
        GROUP BY sector 
        ORDER BY count DESC 
        LIMIT 8
    ");
    $chart_data['sector_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Taux de complétion par étape
    $stmt = $pdo->query("
        SELECT 
            'BMC Créé' as step,
            COUNT(*) as total,
            COUNT(*) as completed
        FROM projects
        UNION ALL
        SELECT 
            'BMC Complété' as step,
            COUNT(*) as total,
            (
                SELECT COUNT(DISTINCT p2.id) 
                FROM projects p2 
                WHERE (
                    SELECT COUNT(*) 
                    FROM bmc b 
                    WHERE b.project_id = p2.id AND b.content != '' AND b.content != 'Non spécifié'
                ) >= 5
            ) as completed
        FROM projects
        UNION ALL
        SELECT 
            'Hypothèses' as step,
            COUNT(*) as total,
            (
                SELECT COUNT(DISTINCT p3.id) 
                FROM projects p3 
                INNER JOIN hypotheses h ON p3.id = h.project_id
            ) as completed
        FROM projects
        UNION ALL
        SELECT 
            'Plan Financier' as step,
            COUNT(*) as total,
            (
                SELECT COUNT(DISTINCT p4.id) 
                FROM projects p4 
                INNER JOIN hypotheses h ON p4.id = h.project_id
                WHERE h.content LIKE '%financier%' OR h.content LIKE '%budget%' OR h.content LIKE '%coût%'
            ) as completed
        FROM projects
    ");
    $chart_data['completion_steps'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top utilisateurs (anonymisés)
    $stmt = $pdo->query("
        SELECT 
            CONCAT('User_', u.id) as user_code,
            COUNT(p.id) as project_count,
            (
                SELECT COUNT(*) 
                FROM projects p2 
                WHERE p2.user_id = u.id 
                AND (
                    SELECT COUNT(*) 
                    FROM bmc b 
                    WHERE b.project_id = p2.id AND b.content != '' AND b.content != 'Non spécifié'
                ) >= 5
            ) as completed_count
        FROM users u
        LEFT JOIN projects p ON u.id = p.user_id
        GROUP BY u.id
        HAVING project_count > 0
        ORDER BY project_count DESC
        LIMIT 10
    ");
    $chart_data['top_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('Erreur analytics admin : ' . $e->getMessage());
    $stats = array_fill_keys(['total_projects', 'active_users', 'completed_bmc', 'conversion_rate'], 0);
    $chart_data = array();
}
?>

<?php include 'template_header_simple.php'; ?>

                <!-- Actions de page -->
                    <h1 style="color: var(--primary); margin: 0;">
                        <i class="bi bi-graph-up"></i> Analytics
                    </h1>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary" onclick="refreshData()">
                            <i class="bi bi-arrow-clockwise"></i> Actualiser
                        </button>
                    </div>
                </div>

                    <!-- Funnel de conversion -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="admin-card">
                                <div class="card-header">
                                    <h5><i class="bi bi-funnel"></i> Funnel de Conversion</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 text-center">
                                            <div class="funnel-step active">
                                                <div class="funnel-number"><?= number_format($stats['total_projects'] ?? 0) ?></div>
                                                <div class="funnel-label">Projets Créés</div>
                                                <div class="funnel-percentage">100%</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <div class="funnel-step <?= ($stats['conversion_rate'] ?? 0) > 50 ? 'active' : 'inactive' ?>">
                                                <div class="funnel-number"><?= number_format($stats['completed_bmc'] ?? 0) ?></div>
                                                <div class="funnel-label">Projets avec BMC</div>
                                                <div class="funnel-percentage"><?= $stats['conversion_rate'] ?? 0 ?>%</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <div class="funnel-step <?= ($stats['conversion_rate'] ?? 0) > 30 ? 'active' : 'inactive' ?>">
                                                <div class="funnel-number"><?= number_format($stats['completed_bmc'] ?? 0) ?></div>
                                                <div class="funnel-label">Projets avec BMC</div>
                                                <div class="funnel-percentage"><?= $stats['conversion_rate'] ?? 0 ?>%</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <div class="funnel-step <?= ($stats['conversion_rate'] ?? 0) > 20 ? 'active' : 'inactive' ?>">
                                                <div class="funnel-number"><?= number_format($stats['completed_bmc'] ?? 0) ?></div>
                                                <div class="funnel-label">Projets avec BMC</div>
                                                <div class="funnel-percentage"><?= $stats['conversion_rate'] ?? 0 ?>%</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Graphiques -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="admin-card">
                                <div class="card-header">
                                    <h5><i class="bi bi-graph-up"></i> Évolution Temporelle</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="timelineChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-card">
                                <div class="card-header">
                                    <h5><i class="bi bi-pie-chart"></i> Répartition par Secteur</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="sectorChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Métriques de performance -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="admin-card">
                                <div class="card-header">
                                    <h5><i class="bi bi-clock"></i> Temps Moyen par Étape</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="metric-card">
                                                <div class="metric-value">
                                                    <?= round(($time_stats['avg_time_to_hypothesis'] ?? 0) / 60, 1) ?>
                                                </div>
                                                <div class="metric-label">Heures jusqu'aux hypothèses</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="metric-card">
                                                <div class="metric-value">
                                                    <?= round(($time_stats['avg_time_completion'] ?? 0) / 60, 1) ?>
                                                </div>
                                                <div class="metric-label">Heures de complétion</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="admin-card">
                                <div class="card-header">
                                    <h5><i class="bi bi-lightbulb"></i> Insights</h5>
                                </div>
                                <div class="card-body">
                                    <div class="insights-list">
                                        <?php if (($funnel_stats['completion_rate'] ?? 0) < 50): ?>
                                        <div class="insight-item warning">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            <span>Taux de complétion faible (<?= $funnel_stats['completion_rate'] ?? 0 ?>%). Considérer une simplification du processus.</span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (($funnel_stats['hypothesis_rate'] ?? 0) < 30): ?>
                                        <div class="insight-item info">
                                            <i class="bi bi-info-circle"></i>
                                            <span>Peu d'utilisateurs passent aux hypothèses (<?= $funnel_stats['hypothesis_rate'] ?? 0 ?>%). Améliorer la transition.</span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (($time_stats['avg_time_to_hypothesis'] ?? 0) > 120): ?>
                                        <div class="insight-item warning">
                                            <i class="bi bi-clock"></i>
                                            <span>Temps élevé jusqu'aux hypothèses (<?= round(($time_stats['avg_time_to_hypothesis'] ?? 0) / 60, 1) ?>h). Optimiser le workflow.</span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="insight-item success">
                                            <i class="bi bi-check-circle"></i>
                                            <span>Données anonymisées respectées. Aucune information personnelle exposée.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Graphique temporel
        const timelineCtx = document.getElementById('timelineChart').getContext('2d');
        const timelineChart = new Chart(timelineCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($timeline_data, 'date')) ?>,
                datasets: [{
                    label: 'Projets créés',
                    data: <?= json_encode(array_column($timeline_data, 'projects')) ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Utilisateurs actifs',
                    data: <?= json_encode(array_column($timeline_data, 'users')) ?>,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Graphique secteurs
        const sectorCtx = document.getElementById('sectorChart').getContext('2d');
        const sectorChart = new Chart(sectorCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($sector_data, 'sector')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($sector_data, 'count')) ?>,
                    backgroundColor: [
                        '#3498db',
                        '#27ae60',
                        '#f39c12',
                        '#e74c3c',
                        '#9b59b6',
                        '#34495e'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        function changePeriod(period) {
            window.location.href = 'analytics.php?period=' + period;
        }
    </script>

    <style>
        .funnel-step {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .funnel-step.active {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        
        .funnel-step.inactive {
            background: #ecf0f1;
            color: #7f8c8d;
        }
        
        .funnel-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .funnel-label {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .funnel-percentage {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .metric-card {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--admin-primary);
        }
        
        .metric-label {
            font-size: 0.9rem;
            color: var(--admin-secondary);
            margin-top: 5px;
        }
        
        .insights-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .insight-item {
            display: flex;
            align-items: flex-start;
            padding: 15px;
            border-radius: 8px;
            gap: 10px;
        }
        
        .insight-item.warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 1px solid #ffc107;
            color: #856404;
        }
        
        .insight-item.info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            border: 1px solid #17a2b8;
            color: #0c5460;
        }
        
        .insight-item.success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 1px solid #28a745;
            color: #155724;
        }
        
        .insight-item i {
            margin-top: 2px;
        }
    </style>

            </div>
        </div>
    </div>

<?php include 'template_footer_simple.php'; ?> 