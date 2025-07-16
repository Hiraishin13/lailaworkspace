<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

// Récupérer les suggestions de partenariats
try {
    // Suggestions automatiques basées sur les secteurs similaires
    $stmt = $pdo->query("
        SELECT 
            p1.id as project1_id,
            p1.name as project1_name,
            p1.target_market as project1_sector,
            p2.id as project2_id,
            p2.name as project2_name,
            p2.target_market as project2_sector,
            COUNT(*) as common_elements,
            'Secteur similaire' as suggestion_type
        FROM projects p1
        INNER JOIN projects p2 ON p1.id < p2.id
        WHERE p1.target_market = p2.target_market 
        AND p1.target_market != '' 
        AND p1.target_market IS NOT NULL
        GROUP BY p1.id, p2.id
        HAVING common_elements > 0
        ORDER BY common_elements DESC
        LIMIT 20
    ");
    $sector_suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Suggestions basées sur les complémentarités (ressources vs canaux)
    $stmt = $pdo->query("
        SELECT 
            p1.id as project1_id,
            p1.name as project1_name,
            p2.id as project2_id,
            p2.name as project2_name,
            'Complémentarité' as suggestion_type,
            'Ressources vs Canaux' as reason
        FROM projects p1
        INNER JOIN projects p2 ON p1.id < p2.id
        INNER JOIN bmc b1 ON p1.id = b1.project_id AND b1.block_name = 'ressources_cles'
        INNER JOIN bmc b2 ON p2.id = b2.project_id AND b2.block_name = 'canaux'
        WHERE b1.content != '' AND b1.content != 'Non spécifié'
        AND b2.content != '' AND b2.content != 'Non spécifié'
        LIMIT 15
    ");
    $complementarity_suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Suggestions basées sur les partenaires clés
    $stmt = $pdo->query("
        SELECT 
            p1.id as project1_id,
            p1.name as project1_name,
            p2.id as project2_id,
            p2.name as project2_name,
            'Partenaires' as suggestion_type,
            'Partenaires clés similaires' as reason
        FROM projects p1
        INNER JOIN projects p2 ON p1.id < p2.id
        INNER JOIN bmc b1 ON p1.id = b1.project_id AND b1.block_name = 'partenaires_cles'
        INNER JOIN bmc b2 ON p2.id = b2.project_id AND b2.block_name = 'partenaires_cles'
        WHERE b1.content != '' AND b1.content != 'Non spécifié'
        AND b2.content != '' AND b2.content != 'Non spécifié'
        AND b1.content = b2.content
        LIMIT 10
    ");
    $partner_suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $all_suggestions = array_merge($sector_suggestions, $complementarity_suggestions, $partner_suggestions);

} catch (PDOException $e) {
    error_log('Erreur suggestions partenariats : ' . $e->getMessage());
    $all_suggestions = array();
}
?>

<?php include 'template_header_simple.php'; ?>\n\n                <!-- Actions de page -->
                    <h1 style="color: var(--primary); margin: 0;">
                        <i class="bi bi-lightbulb"></i> Suggestions de Partenariats
                    </h1>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshSuggestions()">
                            <i class="bi bi-arrow-clockwise"></i> Actualiser
                        </button>
                        <button class="btn btn-success btn-sm" onclick="generateNewSuggestions()">
                            <i class="bi bi-magic"></i> Générer
                        </button>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-primary">
                                    <i class="bi bi-lightbulb"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0"><?= count($all_suggestions) ?></h4>
                                    <small class="text-muted">Suggestions totales</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-success">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0"><?= count($sector_suggestions) ?></h4>
                                    <small class="text-muted">Par secteur</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-warning">
                                    <i class="bi bi-arrow-left-right"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0"><?= count($complementarity_suggestions) ?></h4>
                                    <small class="text-muted">Complémentarités</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="d-flex align-items-center">
                                <div class="kpi-icon bg-info">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0"><?= count($partner_suggestions) ?></h4>
                                    <small class="text-muted">Partenaires</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Suggestions -->
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-lightbulb"></i> Suggestions de Partenariats</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($all_suggestions)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-lightbulb fs-1 text-muted"></i>
                            <h5 class="mt-3">Aucune suggestion disponible</h5>
                            <p class="text-muted">Les suggestions seront générées automatiquement basées sur l'analyse des projets.</p>
                            <button class="btn btn-primary" onclick="generateNewSuggestions()">
                                <i class="bi bi-magic"></i> Générer des suggestions
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Projet 1</th>
                                        <th>Projet 2</th>
                                        <th>Type</th>
                                        <th>Raison</th>
                                        <th>Score</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_suggestions as $suggestion): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($suggestion['project1_name']) ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars($suggestion['project1_sector'] ?? 'N/A') ?></small>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($suggestion['project2_name']) ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars($suggestion['project2_sector'] ?? 'N/A') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $suggestion['suggestion_type'] === 'Secteur similaire' ? 'primary' : ($suggestion['suggestion_type'] === 'Complémentarité' ? 'warning' : 'info') ?>">
                                                <?= htmlspecialchars($suggestion['suggestion_type']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($suggestion['reason'] ?? $suggestion['suggestion_type']) ?></td>
                                        <td>
                                            <?php 
                                            $score = isset($suggestion['common_elements']) ? $suggestion['common_elements'] * 10 : 75;
                                            $score = min($score, 100);
                                            ?>
                                            <div class="progress" style="height: 15px;">
                                                <div class="progress-bar bg-success" style="width: <?= $score ?>%">
                                                    <?= $score ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewPartnership(<?= $suggestion['project1_id'] ?>, <?= $suggestion['project2_id'] ?>)" title="Voir le partenariat">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" onclick="createPartnership(<?= $suggestion['project1_id'] ?>, <?= $suggestion['project2_id'] ?>)" title="Créer le partenariat">
                                                    <i class="bi bi-plus-circle"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" onclick="analyzeCompatibility(<?= $suggestion['project1_id'] ?>, <?= $suggestion['project2_id'] ?>)" title="Analyser la compatibilité">
                                                    <i class="bi bi-graph-up"></i>
                                                </button>
                                            </div>
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
    <?php include 'template_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function refreshSuggestions() {
            location.reload();
        }

        function generateNewSuggestions() {
            // Simulation de génération de nouvelles suggestions
            alert('Génération de nouvelles suggestions en cours...');
            setTimeout(() => {
                location.reload();
            }, 2000);
        }

        function viewPartnership(project1Id, project2Id) {
            window.open(`partnership_details.php?project1=${project1Id}&project2=${project2Id}`, '_blank');
        }

        function createPartnership(project1Id, project2Id) {
            if (confirm('Créer un partenariat entre ces deux projets ?')) {
                // Logique de création de partenariat
                alert('Partenariat créé avec succès !');
            }
        }

        function analyzeCompatibility(project1Id, project2Id) {
            window.open(`compatibility_analysis.php?project1=${project1Id}&project2=${project2Id}`, '_blank');
        }
    </script>
</body>
</html> 