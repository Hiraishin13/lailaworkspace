<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if BASE_URL is defined
if (!defined('BASE_URL')) {
    die('Error: BASE_URL is not defined. Please check your configuration.');
}

// Liste des pages publiques accessibles sans connexion
$public_pages = ['index.php', 'guide.php', 'login.php', 'register.php', 'generate_bmc.php', 'terms.php', 'benefits.php', 'forgot_password.php', 'reset_password.php', 'visualisation.php'];

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

// Bouton Mon BMC
$mon_bmc_href = BASE_URL . "/views/auth/login.php";
if ($is_logged_in) {
    $last_bmc_id = null;
    try {
        $stmt = $pdo->prepare("SELECT id FROM bmc WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $last_bmc = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($last_bmc) {
            $last_bmc_id = $last_bmc['id'];
            $mon_bmc_href = BASE_URL . "/views/bmc/visualisation.php?bmc_id=" . $last_bmc_id;
        }
    } catch (PDOException $e) {
        // Rien
    }
}

// Bouton Voir mon BMC : toujours visible, lien actif si un BMC existe
$mon_bmc_href = BASE_URL . "/views/bmc/generate_bmc.php";
if ($is_logged_in) {
    $last_project_id = null;
    try {
        $stmt = $pdo->prepare("SELECT id FROM projects WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $last_project = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($last_project) {
            $last_project_id = $last_project['id'];
            $mon_bmc_href = BASE_URL . "/views/bmc/visualisation.php?project_id=" . $last_project_id;
        }
    } catch (PDOException $e) {
        // Rien
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/views/index.php">
            <div class="brand-icon me-2">
                <i class="bi bi-rocket-takeoff-fill"></i>
            </div>
            <span class="fw-bold brand-text">Laila Workspace</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
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
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'generate_bmc.php' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/views/bmc/generate_bmc.php">
                            <i class="bi bi-kanban me-1"></i>Créer un BMC
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $mon_bmc_href ?>">
                        <i class="bi bi-eye me-1"></i>Voir mon BMC
                    </a>
                </li>
                <?php if ($is_logged_in && $has_project_id): ?>
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
            </ul>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <?php if ($is_logged_in): ?>
                    <li class="nav-item position-relative">
                        <a href="#" class="nav-link position-relative profile-link" tabindex="0" aria-haspopup="true" aria-expanded="false">
                            <?php if ($profile_picture): ?>
                                <img src="<?= BASE_URL . htmlspecialchars($profile_picture) ?>" 
                                     alt="Photo de profil" 
                                     class="user-avatar rounded-circle"
                                     style="width: 40px; height: 40px; object-fit: cover;">
                            <?php else: ?>
                                <span class="user-avatar rounded-circle d-flex align-items-center justify-content-center">
                                    <?= htmlspecialchars($initials) ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="profile-dropdown">
                            <a href="<?= BASE_URL ?>/views/settings.php"><i class="bi bi-person-circle"></i>Mon compte</a>
                            <a href="<?= BASE_URL ?>/views/user/history.php"><i class="bi bi-clock-history"></i>Historique</a>
                            <div class="dropdown-divider"></div>
                            <a href="<?= BASE_URL ?>/views/auth/logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i>Déconnexion</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'login.php' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/views/auth/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Se connecter
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm ms-2 px-3 py-2 signup-btn" 
                           href="<?= BASE_URL ?>/views/auth/register.php">
                            <i class="bi bi-person-plus me-1"></i>S'inscrire
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
.navbar {
    background-color: rgba(255, 255, 255, 0.98) !important;
    backdrop-filter: blur(15px);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 1rem 0;
}

.navbar-brand {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
}

.navbar-brand:hover {
    transform: translateY(-2px);
}

.brand-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #007bff, #00c4b4);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.brand-icon:hover {
    transform: rotate(5deg) scale(1.05);
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
}

.brand-text {
    background: linear-gradient(135deg, #2c3e50, #34495e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-size: 1.3rem;
    letter-spacing: -0.5px;
}

.nav-link {
    position: relative;
    padding: 0.75rem 1.5rem !important;
    margin: 0 0.5rem;
    border-radius: 8px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 500;
    color: #495057 !important;
    white-space: nowrap;
    min-width: fit-content;
}

.nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #007bff, #00c4b4);
    border-radius: 8px;
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: -1;
}

.nav-link:hover::before,
.nav-link.active::before {
    opacity: 0.1;
}

.nav-link:hover {
    color: #007bff !important;
    transform: translateY(-1px);
}

.nav-link.active {
    color: #007bff !important;
    font-weight: 600;
}

.nav-link i {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.nav-link:hover i {
    transform: scale(1.1);
}

.user-avatar {
    width: 40px !important;
    height: 40px !important;
    background: linear-gradient(135deg, #007bff, #00c4b4);
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    border: 3px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.user-avatar:hover {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    border-color: #007bff;
}

.profile-link {
    position: relative;
}

.profile-tooltip {
    position: absolute;
    bottom: -30px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    white-space: nowrap;
    z-index: 1000;
}

.profile-tooltip::before {
    content: '';
    position: absolute;
    top: -4px;
    left: 50%;
    transform: translateX(-50%);
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-bottom: 4px solid rgba(0, 0, 0, 0.8);
}

.profile-link:hover .profile-tooltip {
    opacity: 1;
    visibility: visible;
    bottom: -25px;
}

.logout-link {
    color: #dc3545 !important;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.logout-link:hover {
    color: #c82333 !important;
    background-color: rgba(220, 53, 69, 0.1);
    transform: translateY(-1px);
}

.signup-btn {
    background: linear-gradient(135deg, #007bff, #00c4b4);
    color: #fff !important;
    border: none !important;
    border-radius: 6px !important;
    font-weight: 600;
    font-size: 1rem;
    padding: 0.75rem 2rem !important;
    box-shadow: 0 4px 16px rgba(0, 123, 255, 0.10);
    transition: background 0.3s, box-shadow 0.3s, transform 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    outline: none;
    white-space: nowrap;
    min-width: fit-content;
}
.signup-btn:hover, .signup-btn:focus {
    background: linear-gradient(135deg, #0056b3, #009a8e);
    color: #fff !important;
    box-shadow: 0 8px 24px rgba(0, 123, 255, 0.18);
    transform: translateY(-2px) scale(1.04);
    text-decoration: none;
}
.signup-btn:active {
    transform: scale(0.98);
}

.navbar-toggler {
    border: none;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.navbar-toggler:hover {
    background-color: rgba(0, 123, 255, 0.1);
}

.navbar-toggler:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

@media (max-width: 991.98px) {
    .navbar-collapse {
        background-color: rgba(255, 255, 255, 0.98);
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        margin-top: 1rem;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .nav-link {
        margin: 0.5rem 0;
        padding: 0.75rem 1.5rem !important;
        white-space: nowrap;
    }
    
    .navbar-nav {
        gap: 0.75rem;
    }
    
    .navbar-nav .nav-item {
        margin: 0.25rem 0;
    }
    
    .profile-tooltip {
        display: none;
    }
}

@media (max-width: 576px) {
    .navbar-brand {
        font-size: 1.1rem;
    }
    
    .brand-icon {
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }
    
    .signup-btn {
        font-size: 0.95rem;
        padding: 0.75rem 1.5rem !important;
        white-space: nowrap;
        min-width: fit-content !important;
    }
}

/* Animation d'entrée pour la navbar */
@keyframes slideDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.navbar {
    animation: slideDown 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Effet de focus amélioré pour l'accessibilité */
.nav-link:focus,
.navbar-brand:focus,
.signup-btn:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

/* Animation pour les icônes au survol */
.nav-link i {
    display: inline-block;
}

/* Amélioration de l'espacement des éléments de navigation */
.navbar-nav.ms-auto {
    gap: 1rem;
}

.navbar-nav .nav-item {
    display: flex;
    align-items: center;
}

/* S'assurer que les textes ne se coupent pas */
.navbar-nav .nav-link {
    overflow: visible;
    text-overflow: unset;
}

.nav-link:hover i {
    animation: bounce 0.6s ease;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-3px);
    }
    60% {
        transform: translateY(-1px);
    }
}

.profile-dropdown {
    position: absolute;
    top: 110%;
    right: 0;
    min-width: 180px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    padding: 0.5rem 0;
    z-index: 2000;
    display: none;
    flex-direction: column;
    animation: fadeIn 0.2s;
}
.profile-dropdown.show {
    display: flex;
}
.profile-dropdown a {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.7rem 1.2rem;
    color: #2d3436;
    text-decoration: none;
    font-weight: 500;
    transition: background 0.2s, color 0.2s;
    font-size: 1rem;
}
.profile-dropdown a:hover {
    background: #f1f3f6;
    color: #007bff;
}
.profile-dropdown .dropdown-divider {
    height: 1px;
    background: #e9ecef;
    margin: 0.3rem 0;
    border: none;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.profile-link {
    cursor: pointer;
    position: relative;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var profileLink = document.querySelector('.profile-link');
    var dropdown = document.querySelector('.profile-dropdown');
    if (profileLink && dropdown) {
        profileLink.addEventListener('click', function(e) {
            e.preventDefault();
            dropdown.classList.toggle('show');
        });
        document.addEventListener('click', function(e) {
            if (!profileLink.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    }
});
</script>