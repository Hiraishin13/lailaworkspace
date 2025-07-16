<!-- Sidebar -->
<div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Tableau de Bord
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i>
                    Utilisateurs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'active' : ''; ?>" href="projects.php">
                    <i class="fas fa-project-diagram"></i>
                    Projets
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'partnerships.php' ? 'active' : ''; ?>" href="partnerships.php">
                    <i class="fas fa-handshake"></i>
                    Partenariats
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ai_partnership_suggestions.php' ? 'active' : ''; ?>" href="ai_partnership_suggestions.php">
                    <i class="fas fa-robot text-primary"></i>
                    <span class="text-primary">Suggestions IA</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>" href="analytics.php">
                    <i class="fas fa-chart-bar"></i>
                    Analytics
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'audit.php' ? 'active' : ''; ?>" href="audit.php">
                    <i class="fas fa-shield-alt"></i>
                    Audit
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'send_notifications.php' ? 'active' : ''; ?>" href="send_notifications.php">
                    <i class="fas fa-bell"></i>
                    Notifications
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'consent_management.php' ? 'active' : ''; ?>" href="consent_management.php">
                    <i class="fas fa-user-check"></i>
                    Consentements
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i>
                    Paramètres
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                    <i class="fas fa-user"></i>
                    Profil
                </a>
            </li>
        </ul>
        
        <hr>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Actions Rapides</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="add_user.php">
                    <i class="fas fa-user-plus"></i>
                    Ajouter Utilisateur
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="download_export.php">
                    <i class="fas fa-download"></i>
                    Exporter Données
                </a>
            </li>
        </ul>
    </div>
</div> 