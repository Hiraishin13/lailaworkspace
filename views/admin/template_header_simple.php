<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

// Déterminer la page courante
$current_page = '';
$page_title = 'Back-office Laila Workspace';
$page_icon = 'bi-speedometer2';

$script_name = basename($_SERVER['PHP_SELF'], '.php');
switch ($script_name) {
    case 'dashboard':
        $current_page = 'dashboard';
        $page_title = 'Dashboard';
        $page_icon = 'bi-speedometer2';
        break;
    case 'users':
        $current_page = 'users';
        $page_title = 'Gestion des Utilisateurs';
        $page_icon = 'bi-people';
        break;
    case 'user_details':
        $current_page = 'users';
        $page_title = 'Détails Utilisateur';
        $page_icon = 'bi-person';
        break;
    case 'add_user':
        $current_page = 'users';
        $page_title = 'Ajouter un Utilisateur';
        $page_icon = 'bi-person-plus';
        break;
    case 'projects':
        $current_page = 'projects';
        $page_title = 'Gestion des Projets';
        $page_icon = 'bi-kanban';
        break;
    case 'project_details':
        $current_page = 'projects';
        $page_title = 'Détails du Projet';
        $page_icon = 'bi-file-earmark-text';
        break;
    case 'partnerships':
        $current_page = 'partnerships';
        $page_title = 'Partenariats B2B';
        $page_icon = 'bi-handshake';
        break;
    case 'partnership_details':
        $current_page = 'partnerships';
        $page_title = 'Détails du Partenariat';
        $page_icon = 'bi-handshake';
        break;
    case 'partnership_suggestions':
        $current_page = 'partnerships';
        $page_title = 'Suggestions de Partenariats';
        $page_icon = 'bi-lightbulb';
        break;
    case 'ai_partnership_suggestions':
        $current_page = 'ai_suggestions';
        $page_title = 'Suggestions IA';
        $page_icon = 'bi-robot';
        break;
    case 'analytics':
        $current_page = 'analytics';
        $page_title = 'Analytics';
        $page_icon = 'bi-graph-up';
        break;
    case 'audit':
        $current_page = 'audit';
        $page_title = 'Audit';
        $page_icon = 'bi-shield-check';
        break;
    case 'send_notifications':
        $current_page = 'notifications';
        $page_title = 'Notifications';
        $page_icon = 'bi-megaphone';
        break;
    case 'consent_management':
        $current_page = 'consent';
        $page_title = 'Gestion des Consentements';
        $page_icon = 'bi-shield-check';
        break;
    case 'settings':
        $current_page = 'settings';
        $page_title = 'Paramètres';
        $page_icon = 'bi-gear';
        break;
    case 'profile':
        $current_page = 'profile';
        $page_title = 'Mon Profil';
        $page_icon = 'bi-person-circle';
        break;
    default:
        $current_page = 'dashboard';
        $page_title = 'Back-office Laila Workspace';
        $page_icon = 'bi-speedometer2';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Laila Workspace Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                        <a class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>" href="dashboard.php" role="menuitem">
                            <i class="bi bi-speedometer2" aria-hidden="true"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link <?= $current_page === 'users' ? 'active' : '' ?>" href="users.php" role="menuitem">
                            <i class="bi bi-people" aria-hidden="true"></i> Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link <?= $current_page === 'projects' ? 'active' : '' ?>" href="projects.php" role="menuitem">
                            <i class="bi bi-kanban" aria-hidden="true"></i> Projets
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link <?= $current_page === 'analytics' ? 'active' : '' ?>" href="analytics.php" role="menuitem">
                            <i class="bi bi-graph-up" aria-hidden="true"></i> Analytics
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link <?= $current_page === 'partnerships' ? 'active' : '' ?>" href="partnerships.php" role="menuitem">
                            <i class="bi bi-handshake" aria-hidden="true"></i> Partenariats B2B
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link <?= $current_page === 'ai_suggestions' ? 'active' : '' ?>" href="ai_partnership_suggestions.php" role="menuitem">
                            <i class="bi bi-robot" aria-hidden="true"></i> Suggestions IA
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link <?= $current_page === 'audit' ? 'active' : '' ?>" href="audit.php" role="menuitem">
                            <i class="bi bi-shield-check" aria-hidden="true"></i> Audit
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a class="nav-link <?= $current_page === 'notifications' ? 'active' : '' ?>" href="send_notifications.php" role="menuitem">
                            <i class="bi bi-megaphone" aria-hidden="true"></i> Notifications
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav" role="menubar">
                    <li class="nav-item dropdown" role="none">
                        <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell" aria-hidden="true"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" role="menu">
                            <li role="none"><h6 class="dropdown-header">Alertes système</h6></li>
                            <li role="none"><hr class="dropdown-divider"></li>
                            <li role="none"><a class="dropdown-item" href="#" role="menuitem">Aucune alerte</a></li>
                        </ul>
                    </li>

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
                    <h6>Navigation</h6>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'users' ? 'active' : '' ?>" href="users.php">
                                <i class="bi bi-people"></i> Utilisateurs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'projects' ? 'active' : '' ?>" href="projects.php">
                                <i class="bi bi-kanban"></i> Projets
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'analytics' ? 'active' : '' ?>" href="analytics.php">
                                <i class="bi bi-graph-up"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'partnerships' ? 'active' : '' ?>" href="partnerships.php">
                                <i class="bi bi-handshake"></i> Partenariats B2B
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'ai_suggestions' ? 'active' : '' ?>" href="ai_partnership_suggestions.php">
                                <i class="bi bi-robot"></i> Suggestions IA
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'audit' ? 'active' : '' ?>" href="audit.php">
                                <i class="bi bi-shield-check"></i> Audit
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'notifications' ? 'active' : '' ?>" href="send_notifications.php">
                                <i class="bi bi-megaphone"></i> Notifications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'consent' ? 'active' : '' ?>" href="consent_management.php">
                                <i class="bi bi-shield-check"></i> Consentements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'settings' ? 'active' : '' ?>" href="settings.php">
                                <i class="bi bi-gear"></i> Paramètres
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'profile' ? 'active' : '' ?>" href="profile.php">
                                <i class="bi bi-person-circle"></i> Profil
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
                        <i class="bi <?= $page_icon ?>"></i> <?= $page_title ?>
                    </h1>
                </div> 