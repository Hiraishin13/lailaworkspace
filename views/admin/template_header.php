<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier les permissions admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Back-office Laila Workspace' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <?php if (isset($additional_css)): ?>
        <?= $additional_css ?>
    <?php endif; ?>
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
                            <?php if (isset($alerts) && count($alerts) > 0): ?>
                            <span class="badge bg-danger ms-1"><?= count($alerts) ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" role="menu">
                            <li role="none"><h6 class="dropdown-header">Alertes système</h6></li>
                            <li role="none"><hr class="dropdown-divider"></li>
                            <?php if (isset($alerts) && !empty($alerts)): ?>
                                <?php foreach ($alerts as $alert): ?>
                                <li role="none">
                                    <a class="dropdown-item" href="#" role="menuitem">
                                        <i class="bi <?= $alert['icon'] ?> text-<?= $alert['type'] ?>"></i>
                                        <?= htmlspecialchars($alert['message']) ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li role="none"><a class="dropdown-item" href="#" role="menuitem">Aucune alerte</a></li>
                            <?php endif; ?>
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
                    <h6><?= $sidebar_title ?? 'Navigation' ?></h6>
                    
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
                    </ul>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="col-md-9 col-lg-10">
                <!-- En-tête -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 style="color: var(--primary); margin: 0;">
                        <i class="bi <?= $page_icon ?? 'bi-speedometer2' ?>"></i> <?= $page_title ?? 'Page' ?>
                    </h1>
                    <div class="d-flex gap-2">
                        <?php if (isset($page_actions)): ?>
                            <?= $page_actions ?>
                        <?php endif; ?> 