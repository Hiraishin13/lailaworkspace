`            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mb-2">
                        <i class="bi bi-shield-lock"></i> Laila Workspace - Back-office
                    </h6>
                    <p class="mb-0 text-muted small">
                        Plateforme de gestion Business Model Canvas - Version 2.0
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex flex-column flex-md-row justify-content-md-end align-items-md-center">
                        <div class="me-md-3 mb-2 mb-md-0">
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i> Système Opérationnel
                            </span>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-clock"></i> Dernière mise à jour : <?= date('d/m/Y H:i') ?>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            © 2024 Laila Workspace - Tous droits réservés
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($additional_js)): ?>
        <?= $additional_js ?>
    <?php endif; ?>
    <script>
        function refreshData() {
            location.reload();
        }
    </script>
</body>
</html> 