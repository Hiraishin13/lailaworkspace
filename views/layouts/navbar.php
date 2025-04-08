<?php
// Liste des pages publiques accessibles sans connexion
$public_pages = ['index.php', 'guide.php', 'login.php', 'register.php'];

$current_page = basename($_SERVER['PHP_SELF']);
if (!in_array($current_page, $public_pages) && !isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/views/auth/login.php");
    exit;
}

$is_logged_in = isset($_SESSION['user_id']);
// Utiliser project_id au lieu de bmc_id pour la cohérence avec financial_plan.php
$has_project_id = isset($_GET['project_id']) && !empty($_GET['project_id']);

// Récupérer les initiales et la photo de profil de l'utilisateur connecté
$initials = '';
$profile_picture = '';
if ($is_logged_in) {
    try {
        $stmt = $pdo->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $first_name = $user['first_name'] ?? '';
            $last_name = $user['last_name'] ?? '';
            $profile_picture = $user['profile_picture'] ?? '';
            // Prendre la première lettre du prénom et du nom de famille
            $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
            // Si les champs sont vides, utiliser un placeholder comme "U"
            if (empty($initials)) {
                $initials = 'U';
            }
        } else {
            $initials = 'U'; // Utilisateur par défaut si aucune donnée
        }
    } catch (PDOException $e) {
        $initials = 'U'; // En cas d'erreur, utiliser une valeur par défaut
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-light shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/views/index.php">
            <i class="bi bi-rocket-takeoff-fill me-2 text-primary" style="font-size: 1.5rem;"></i>
            <span class="fw-bold">Laila Workspace</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" 
                       href="<?= BASE_URL ?>/views/index.php">
                        <i class="bi bi-house-door me-1"></i>Accueil
                    </a>
                </li>
                <?php if ($is_logged_in): ?>
                    <?php if ($has_project_id): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'visualisation.php' ? 'active' : '' ?>" 
                               href="<?= BASE_URL ?>/views/bmc/visualisation.php?project_id=<?= htmlspecialchars($_GET['project_id']) ?>">
                                <i class="bi bi-grid me-1"></i>Business Model
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'hypotheses.php' ? 'active' : '' ?>" 
                               href="<?= BASE_URL ?>/views/bmc/hypotheses.php?project_id=<?= htmlspecialchars($_GET['project_id']) ?>">
                                <i class="bi bi-lightbulb me-1"></i>Hypothèses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'financial_plan.php' ? 'active' : '' ?>" 
                               href="<?= BASE_URL ?>/views/bmc/financial_plan.php?project_id=<?= htmlspecialchars($_GET['project_id']) ?>">
                                <i class="bi bi-wallet2 me-1"></i>Plan Financier
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'bmp_summary.php' ? 'active' : '' ?>" 
                               href="<?= BASE_URL ?>/views/bmc/bmp_summary.php?project_id=<?= htmlspecialchars($_GET['project_id']) ?>">
                                <i class="bi bi-file-earmark-text me-1"></i>Récapitulatif
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <?php if ($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'guide.php' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/views/guide.php">
                            <i class="bi bi-book me-1"></i>Guide
                        </a>
                    </li>
                    <!-- Afficher la photo de profil ou les initiales -->
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/views/settings.php" class="nav-link">
                            <?php if ($profile_picture): ?>
                                <img src="<?= BASE_URL . htmlspecialchars($profile_picture) ?>" alt="Photo de profil" class="user-avatar rounded-circle">
                            <?php else: ?>
                                <span class="user-avatar"><?= htmlspecialchars($initials) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/views/auth/logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Se déconnecter
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'login.php' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/views/auth/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Se connecter
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm rounded-3 ms-2" 
                           href="<?= BASE_URL ?>/views/auth/register.php">
                            <i class="bi bi-person-plus me-1"></i>Commencer maintenant
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>