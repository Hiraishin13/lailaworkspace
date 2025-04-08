<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
        <div class="row g-4">
            <!-- Section 1 : Logo et description -->
            <div class="col-md-4">
                <h5 class="mb-3 fw-bold d-flex align-items-center">
                    <i class="bi bi-rocket-takeoff-fill me-2 text-primary"></i>
                    Laila Workspace
                </h5>
                <p class="text-muted">
                    Transformez vos idées en Business Model Canvas avec notre générateur IA. Créez, analysez et affinez vos projets en toute simplicité.
                </p>
            </div>

            <!-- Section 2 : Liens utiles -->
            <div class="col-md-4">
                <h5 class="mb-3 fw-bold">Liens utiles</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="<?= BASE_URL ?>/views/index.php" class="text-light text-decoration-none hover-link">
                            <i class="bi bi-house-door me-2"></i>Accueil
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= BASE_URL ?>/views/bmc/generate_bmc.php" class="text-light text-decoration-none hover-link">
                            <i class="bi bi-grid me-2"></i>Business Model
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= BASE_URL ?>/views/settings.php" class="text-light text-decoration-none hover-link">
                            <i class="bi bi-person-circle me-2"></i>Mon compte
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Section 3 : Contact -->
            <div class="col-md-4">
                <h5 class="mb-3 fw-bold">Contactez-nous</h5>
                <ul class="list-unstyled text-muted">
                    <li class="mb-2">
                        <i class="bi bi-envelope me-2"></i>
                        <a href="mailto:support@lailaworkspace.com" class="text-light text-decoration-none hover-link">support@lailaworkspace.com</a>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-telephone me-2"></i>
                        <a href="tel:+33123456789" class="text-light text-decoration-none hover-link">+1 (781) 299-0222</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Ligne de séparation -->
        <hr class="my-4 border-secondary">

        <!-- Copyright avec année dynamique et "Powered by Gad Lelo" -->
        <div class="text-center">
            <p class="text-muted mb-0">© <?= date('Y') ?> - Laila Workspace - Tous droits réservés</p>
            <p class="text-muted mt-2 mb-0">
                <small>
                    Powered by 
                    <a href="mailto:gadlelo759@gmail.com" class="text-light text-decoration-none hover-link">
                        Gad Lelo
                    </a>
                </small>
            </p>
        </div>
    </div>
</footer>