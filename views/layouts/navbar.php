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

<?php if (empty($GLOBALS['ICONS_INCLUDED'])): ?>
    <?php $GLOBALS['ICONS_INCLUDED'] = true; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<?php endif; ?>

<!-- Desktop Navbar -->
<nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top desktop-navbar">
    <div class="container-fluid px-3 px-md-4">
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
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'partnerships.php' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/views/user/partnerships.php">
                            <i class="bi bi-people-fill me-1"></i>Partenariats
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
                    <!-- Composant Notifications -->
                    <?php include __DIR__ . '/../components/notifications.php'; ?>
                    
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
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <div class="dropdown-divider"></div>
                                <a href="<?= BASE_URL ?>/views/admin/dashboard.php" class="admin-link">
                                    <i class="bi bi-shield-lock"></i>Back-office
                                </a>
                            <?php endif; ?>
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

<!-- Mobile Bottom Navigation -->
<nav class="mobile-bottom-nav">
    <div class="mobile-nav-container">
        <a href="<?= BASE_URL ?>/views/index.php" class="mobile-nav-item <?= $current_page === 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-house-door"></i>
            <span>Accueil</span>
        </a>
        
        <?php if ($is_logged_in): ?>
            <a href="<?= BASE_URL ?>/views/bmc/generate_bmc.php" class="mobile-nav-item <?= $current_page === 'generate_bmc.php' ? 'active' : '' ?>">
                <i class="bi bi-kanban"></i>
                <span>BMC</span>
            </a>
            
            <a href="<?= BASE_URL ?>/views/user/partnerships.php" class="mobile-nav-item <?= $current_page === 'partnerships.php' ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span>Partenariats</span>
            </a>
        <?php else: ?>
                            <a href="<?= BASE_URL ?>/views/auth/login.php" class="mobile-nav-item <?= $current_page === 'login.php' ? 'active' : '' ?>">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Connexion</span>
                </a>
        <?php endif; ?>
        
        <button class="mobile-nav-item mobile-menu-trigger" id="mobileMenuTrigger">
            <i class="bi bi-list"></i>
            <span>Menu</span>
        </button>
    </div>
</nav>

<!-- Mobile Side Menu -->
<div class="mobile-side-menu" id="mobileSideMenu">
    <div class="mobile-side-menu-header">
        <div class="mobile-brand">
            <div class="mobile-brand-icon">
                <i class="bi bi-rocket-takeoff-fill"></i>
            </div>
            <span>Laila Workspace</span>
        </div>
        <button class="mobile-menu-close" id="mobileMenuClose">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    
    <div class="mobile-side-menu-content">
        <?php if ($is_logged_in): ?>
            <div class="mobile-user-info">
                <?php if ($profile_picture): ?>
                    <img src="<?= BASE_URL . htmlspecialchars($profile_picture) ?>" 
                         alt="Photo de profil" 
                         class="mobile-user-avatar">
                <?php else: ?>
                    <div class="mobile-user-avatar mobile-user-initials">
                        <?= htmlspecialchars($initials) ?>
                    </div>
                <?php endif; ?>
                <div class="mobile-user-details">
                    <span class="mobile-user-name">Mon Compte</span>
                    <span class="mobile-user-status">Connecté</span>
                </div>
            </div>
            
            <div class="mobile-menu-section">
                <h6 class="mobile-menu-title">Navigation</h6>
                <a href="<?= BASE_URL ?>/views/index.php" class="mobile-menu-item <?= $current_page === 'index.php' ? 'active' : '' ?>">
                    <i class="bi bi-house-door"></i>
                    <span>Accueil</span>
                </a>
                <a href="<?= BASE_URL ?>/views/bmc/generate_bmc.php" class="mobile-menu-item <?= $current_page === 'generate_bmc.php' ? 'active' : '' ?>">
                    <i class="bi bi-kanban"></i>
                    <span>Créer un BMC</span>
                </a>
                <a href="<?= BASE_URL ?>/views/user/partnerships.php" class="mobile-menu-item <?= $current_page === 'partnerships.php' ? 'active' : '' ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>Partenariats</span>
                </a>
                <a href="<?= $mon_bmc_href ?>" class="mobile-menu-item">
                    <i class="bi bi-eye"></i>
                    <span>Voir mon BMC</span>
                </a>
            </div>
            
            <?php if ($has_project_id): ?>
            <div class="mobile-menu-section">
                <h6 class="mobile-menu-title">Projet Actuel</h6>
                <a href="<?= BASE_URL ?>/views/bmc/hypotheses.php?project_id=<?= htmlspecialchars($_GET['project_id']) ?>" class="mobile-menu-item <?= $current_page === 'hypotheses.php' ? 'active' : '' ?>">
                    <i class="bi bi-lightbulb"></i>
                    <span>Hypothèses</span>
                </a>
                <a href="<?= BASE_URL ?>/views/bmc/financial_plan.php?project_id=<?= htmlspecialchars($_GET['project_id']) ?>" class="mobile-menu-item <?= $current_page === 'financial_plan.php' ? 'active' : '' ?>">
                    <i class="bi bi-wallet2"></i>
                    <span>Plan Financier</span>
                </a>
                <a href="<?= BASE_URL ?>/views/bmc/bmp_summary.php?project_id=<?= htmlspecialchars($_GET['project_id']) ?>" class="mobile-menu-item <?= $current_page === 'bmp_summary.php' ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Récapitulatif</span>
                </a>
            </div>
            <?php endif; ?>
            
            <div class="mobile-menu-section">
                <h6 class="mobile-menu-title">Compte</h6>
                <a href="<?= BASE_URL ?>/views/settings.php" class="mobile-menu-item">
                    <i class="bi bi-person-circle"></i>
                    <span>Paramètres</span>
                </a>
                <a href="<?= BASE_URL ?>/views/user/history.php" class="mobile-menu-item">
                    <i class="bi bi-clock-history"></i>
                    <span>Historique</span>
                </a>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="<?= BASE_URL ?>/views/admin/dashboard.php" class="mobile-menu-item admin-link">
                    <i class="bi bi-shield-lock"></i>
                    <span>Back-office</span>
                </a>
                <?php endif; ?>
            </div>
            
            <div class="mobile-menu-section">
                <a href="<?= BASE_URL ?>/views/auth/logout.php" class="mobile-menu-item mobile-logout">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        <?php else: ?>
            <div class="mobile-menu-section">
                <h6 class="mobile-menu-title">Accès</h6>
                <a href="<?= BASE_URL ?>/views/auth/login.php" class="mobile-menu-item <?= $current_page === 'login.php' ? 'active' : '' ?>">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Se connecter</span>
                </a>
                <a href="<?= BASE_URL ?>/views/auth/register.php" class="mobile-menu-item mobile-signup">
                    <i class="bi bi-person-plus"></i>
                    <span>Inscription</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

<style>
/* Reset et base */
* {
    box-sizing: border-box;
}

/* Desktop Navbar Styles */
.desktop-navbar {
    background-color: rgba(255, 255, 255, 0.98) !important;
    backdrop-filter: blur(15px);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 0.75rem 0;
    position: sticky;
    top: 0;
    z-index: 1030;
}

.desktop-navbar .container-fluid {
    max-width: 1400px;
    margin: 0 auto;
    padding-left: 1rem;
    padding-right: 1rem;
}

.navbar-brand {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    display: flex;
    align-items: center;
    margin-right: 0;
}

.navbar-brand:hover {
    transform: translateY(-1px);
}

.brand-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #007bff, #00c4b4);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 3px 10px rgba(0, 123, 255, 0.3);
    flex-shrink: 0;
}

.brand-icon:hover {
    transform: rotate(3deg) scale(1.05);
    box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
}

.brand-text {
    background: linear-gradient(135deg, #2c3e50, #34495e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-size: 1.2rem;
    letter-spacing: -0.3px;
    font-weight: 700;
    white-space: nowrap;
}

.nav-link {
    position: relative;
    padding: 0.6rem 1rem !important;
    margin: 0 0.25rem;
    border-radius: 8px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 500;
    color: #495057 !important;
    white-space: nowrap;
    text-decoration: none;
    display: flex;
    align-items: center;
    font-size: 0.95rem;
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
    margin-right: 0.4rem;
    font-size: 0.9rem;
}

.nav-link:hover i {
    transform: scale(1.1);
}

.user-avatar {
    width: 36px !important;
    height: 36px !important;
    background: linear-gradient(135deg, #007bff, #00c4b4);
    color: white;
    font-weight: 600;
    font-size: 0.85rem;
    border: 2px solid rgba(255, 255, 255, 0.9);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    flex-shrink: 0;
}

.user-avatar:hover {
    transform: scale(1.05) rotate(3deg);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    border-color: #007bff;
}

.profile-link {
    position: relative;
    padding: 0.5rem !important;
    margin: 0;
}

.signup-btn {
    background: linear-gradient(135deg, #007bff, #00c4b4);
    color: #fff !important;
    border: none !important;
    border-radius: 8px !important;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 0.6rem 1.2rem !important;
    box-shadow: 0 3px 12px rgba(0, 123, 255, 0.15);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    outline: none;
    white-space: nowrap;
    text-decoration: none;
    flex-shrink: 0;
}

.signup-btn:hover, .signup-btn:focus {
    background: linear-gradient(135deg, #0056b3, #009a8e);
    color: #fff !important;
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.25);
    transform: translateY(-1px) scale(1.02);
    text-decoration: none;
}

.signup-btn:active {
    transform: scale(0.98);
}

.navbar-toggler {
    border: none;
    padding: 0.4rem;
    border-radius: 6px;
    transition: all 0.3s ease;
    margin-left: 0.5rem;
}

.navbar-toggler:hover {
    background-color: rgba(0, 123, 255, 0.1);
}

.navbar-toggler:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.navbar-toggler-icon {
    width: 1.2rem;
    height: 1.2rem;
}

/* Navigation responsive */
.navbar-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.navbar-nav.ms-auto {
    gap: 0.75rem;
}

.navbar-nav .nav-item {
    display: flex;
    align-items: center;
}

/* Dropdown profile */
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
    border: 1px solid rgba(0,0,0,0.05);
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
    font-size: 0.95rem;
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

.logout-link {
    color: #dc3545 !important;
    font-weight: 500;
}

.logout-link:hover {
    color: #c82333 !important;
    background-color: rgba(220, 53, 69, 0.1);
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

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

.desktop-navbar {
    animation: slideDown 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-2px);
    }
    60% {
        transform: translateY(-1px);
    }
}

.nav-link:hover i {
    animation: bounce 0.6s ease;
}

/* Focus et accessibilité */
.nav-link:focus,
.navbar-brand:focus,
.signup-btn:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

/* ===== MOBILE STYLES ===== */

/* Mobile Bottom Navigation */
.mobile-bottom-nav {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(15px);
    border-top: 1px solid #e9ecef;
    z-index: 1000;
    padding: 0.5rem 0;
    box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.05);
}

.mobile-nav-container {
    display: flex;
    justify-content: space-around;
    align-items: center;
    max-width: 400px;
    margin: 0 auto;
    padding: 0 1rem;
}

.mobile-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    color: #636e72;
    min-width: 60px;
    position: relative;
}

.mobile-nav-item i {
    font-size: 1.2rem;
    margin-bottom: 0.25rem;
    transition: all 0.3s ease;
}

.mobile-nav-item span {
    font-size: 0.7rem;
    font-weight: 500;
    text-align: center;
}

.mobile-nav-item:hover,
.mobile-nav-item.active {
    color: #007bff;
    background: rgba(0, 123, 255, 0.1);
    transform: translateY(-2px);
}

.mobile-nav-item.active::after {
    content: '';
    position: absolute;
    bottom: -0.5rem;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 4px;
    background: var(--primary-color);
    border-radius: 50%;
}

.mobile-nav-item:hover i {
    transform: scale(1.1);
}

.mobile-menu-trigger {
    background: var(--primary-gradient);
    color: white !important;
    border: none;
    cursor: pointer;
}

.mobile-menu-trigger:hover {
    background: linear-gradient(135deg, #0056b3, #009a8e) !important;
    transform: translateY(-2px) scale(1.05);
}

/* Mobile Side Menu */
.mobile-side-menu {
    position: fixed;
    top: 0;
    right: -100%;
    width: 85%;
    max-width: 350px;
    height: 100vh;
    background: #ffffff;
    z-index: 2000;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-y: auto;
    box-shadow: -10px 0 30px rgba(0, 0, 0, 0.15);
    border-left: 1px solid #e9ecef;
}

.mobile-side-menu.active {
    right: 0;
}

.mobile-side-menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 1.5rem 1rem;
    border-bottom: 1px solid #e9ecef;
    background: var(--primary-gradient);
}

.mobile-brand {
    display: flex;
    align-items: center;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
}

.mobile-brand-icon {
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    font-size: 1rem;
}

.mobile-menu-close {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.mobile-menu-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg);
}

.mobile-side-menu-content {
    padding: 1rem 0;
}

.mobile-user-info {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem;
    margin-bottom: 1rem;
    background: #f8f9fa;
    margin: 0 1rem 1rem;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.mobile-user-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    margin-right: 1rem;
    object-fit: cover;
}

.mobile-user-initials {
    background: var(--primary-gradient);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.1rem;
}

.mobile-user-details {
    display: flex;
    flex-direction: column;
}

.mobile-user-name {
    color: var(--text-primary);
    font-weight: 600;
    font-size: 1rem;
}

.mobile-user-status {
    color: var(--text-secondary);
    font-size: 0.8rem;
}

.mobile-menu-section {
    margin-bottom: 1.5rem;
}

.mobile-menu-title {
    color: var(--text-secondary);
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0 1.5rem 0.5rem;
    margin-bottom: 0.5rem;
}

.mobile-menu-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    color: var(--text-primary);
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
}

.mobile-menu-item i {
    width: 24px;
    margin-right: 1rem;
    font-size: 1.1rem;
    color: var(--primary-color);
}

.mobile-menu-item span {
    font-weight: 500;
    font-size: 0.95rem;
}

.mobile-menu-item:hover {
    background: rgba(0, 123, 255, 0.1);
    transform: translateX(5px);
    color: var(--primary-color);
}

.mobile-menu-item.active {
    background: rgba(0, 123, 255, 0.15);
    border-right: 3px solid var(--primary-color);
    color: var(--primary-color);
}

.mobile-menu-item.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--primary-color);
}

.mobile-signup {
    background: var(--primary-gradient);
    margin: 0 1rem;
    border-radius: 6px;
    justify-content: center;
    color: white !important;
}

.mobile-signup:hover {
    background: linear-gradient(135deg, #0056b3, #009a8e);
    transform: translateY(-2px);
    color: white !important;
}

.mobile-logout {
    color: var(--danger-color) !important;
    border-top: 1px solid #e9ecef;
    margin-top: 0.5rem;
}

.mobile-logout:hover {
    background: rgba(220, 53, 69, 0.1) !important;
}

.admin-link {
    color: var(--warning-color) !important;
}

.admin-link:hover {
    background: rgba(255, 193, 7, 0.1) !important;
}

/* Mobile Menu Overlay */
.mobile-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.mobile-menu-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* Media Queries */
@media (max-width: 991.98px) {
    .desktop-navbar {
        display: none !important;
    }
    
    .mobile-bottom-nav {
        display: block;
    }
    
    body {
        padding-bottom: 80px;
    }
}

@media (max-width: 575.98px) {
    .mobile-nav-container {
        padding: 0 0.5rem;
    }
    
    .mobile-nav-item {
        min-width: 50px;
        padding: 0.4rem;
    }
    
    .mobile-nav-item i {
        font-size: 1.1rem;
    }
    
    .mobile-nav-item span {
        font-size: 0.65rem;
    }
    
    .mobile-side-menu {
        width: 90%;
    }
}

@media (max-width: 375px) {
    .mobile-nav-item {
        min-width: 45px;
        padding: 0.3rem;
    }
    
    .mobile-nav-item i {
        font-size: 1rem;
    }
    
    .mobile-nav-item span {
        font-size: 0.6rem;
    }
    
    .mobile-side-menu {
        width: 95%;
    }
}

/* Prévention du débordement */
.navbar-nav .nav-link {
    overflow: visible;
    text-overflow: unset;
    max-width: none;
}

.navbar-brand {
    overflow: visible;
    text-overflow: unset;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Desktop navbar functionality
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
    
    // Mobile menu functionality
    var mobileMenuTrigger = document.getElementById('mobileMenuTrigger');
    var mobileMenuClose = document.getElementById('mobileMenuClose');
    var mobileSideMenu = document.getElementById('mobileSideMenu');
    var mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    
    function openMobileMenu() {
        mobileSideMenu.classList.add('active');
        mobileMenuOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeMobileMenu() {
        mobileSideMenu.classList.remove('active');
        mobileMenuOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (mobileMenuTrigger) {
        mobileMenuTrigger.addEventListener('click', openMobileMenu);
    }
    
    if (mobileMenuClose) {
        mobileMenuClose.addEventListener('click', closeMobileMenu);
    }
    
    if (mobileMenuOverlay) {
        mobileMenuOverlay.addEventListener('click', closeMobileMenu);
    }
    
    // Close mobile menu when clicking on menu items
    var mobileMenuItems = document.querySelectorAll('.mobile-menu-item');
    mobileMenuItems.forEach(function(item) {
        if (!item.classList.contains('mobile-menu-trigger')) {
            item.addEventListener('click', function() {
                setTimeout(closeMobileMenu, 100);
            });
        }
    });
    
    // Close mobile menu on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileSideMenu.classList.contains('active')) {
            closeMobileMenu();
        }
    });
    
    // Desktop navbar close on mobile
    var mobileNavLinks = document.querySelectorAll('.navbar-nav .nav-link');
    var navbarCollapse = document.querySelector('.navbar-collapse');
    
    mobileNavLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 991.98 && navbarCollapse.classList.contains('show')) {
                var bsCollapse = new bootstrap.Collapse(navbarCollapse);
                bsCollapse.hide();
            }
        });
    });
});
</script>