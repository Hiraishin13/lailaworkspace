<?php if (empty($GLOBALS['ICONS_INCLUDED'])): ?>
    <?php $GLOBALS['ICONS_INCLUDED'] = true; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<?php endif; ?>
<footer class="footer-modern py-5 mt-5">
    <div class="container">
        <div class="row g-4">
            <!-- Section 1 : Logo et description -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand mb-4">
                    <div class="brand-icon-footer me-3">
                        <i class="bi bi-rocket-takeoff-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Laila Workspace</h5>
                </div>
                <p class="text-light mb-4" style="color: rgba(255, 255, 255, 0.9) !important; font-weight: 400;">
                    Transformez vos idées en Business Model Canvas avec notre générateur IA. Créez, analysez et affinez vos projets en toute simplicité.
                </p>
                <div class="social-links">
                    <a href="#" class="social-link" aria-label="Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="Twitter">
                        <i class="bi bi-twitter"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="LinkedIn">
                        <i class="bi bi-linkedin"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>
                </div>
            </div>

            <!-- Section 2 : Liens utiles -->
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-3 text-primary">Navigation</h6>
                <ul class="list-unstyled footer-links">
                    <li class="mb-2">
                        <a href="<?= BASE_URL ?>/views/index.php" class="footer-link">
                            <i class="bi bi-house-door me-2"></i>Accueil
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= BASE_URL ?>/views/bmc/generate_bmc.php" class="footer-link">
                            <i class="bi bi-grid me-2"></i>Business Model
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= BASE_URL ?>/views/pages/guide.php" class="footer-link">
                            <i class="bi bi-book me-2"></i>Guide
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= BASE_URL ?>/views/pages/terms.php" class="footer-link">
                            <i class="bi bi-file-text me-2"></i>Conditions
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Section 3 : Mon compte -->
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-3 text-primary">Mon compte</h6>
                <ul class="list-unstyled footer-links">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="mb-2">
                            <a href="<?= BASE_URL ?>/views/settings.php" class="footer-link">
                                <i class="bi bi-person-circle me-2"></i>Profil
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= BASE_URL ?>/views/bmc/generate_bmc.php" class="footer-link">
                                <i class="bi bi-plus-circle me-2"></i>Nouveau BMC
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= BASE_URL ?>/views/auth/logout.php" class="footer-link">
                                <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="mb-2">
                            <a href="<?= BASE_URL ?>/views/auth/login.php" class="footer-link">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Connexion
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= BASE_URL ?>/views/auth/register.php" class="footer-link">
                                <i class="bi bi-person-plus me-2"></i>Inscription
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Section 4 : Contact -->
            <div class="col-lg-4 col-md-6">
                <h6 class="fw-bold mb-3 text-primary">Contactez-nous</h6>
                <div class="contact-info">
                    <div class="contact-item mb-3">
                        <div class="contact-icon">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <a href="mailto:support@lailaworkspace.com" class="footer-link">
                                support@lailaworkspace.com
                            </a>
                        </div>
                    </div>
                    <div class="contact-item mb-3">
                        <div class="contact-icon">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <div class="contact-details">
                            <a href="tel:+17812990222" class="footer-link">
                                +1 (781) 299-0222
                            </a>
                        </div>
                    </div>
                    <div class="contact-item mb-3">
                        <div class="contact-icon">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div class="contact-details">
                            <span style="color: rgba(255, 255, 255, 0.9) !important;">États-Unis</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ligne de séparation -->
        <div class="footer-divider my-4"></div>

        <!-- Copyright avec année dynamique et "Powered by Gad Lelo" -->
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0" style="color: rgba(255, 255, 255, 0.9) !important; font-weight: 500;">
                        © <?= date('Y') ?> - Laila Workspace - Tous droits réservés
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0" style="color: rgba(255, 255, 255, 0.9) !important;">
                        <small>
                            Powered by 
                            <a href="mailto:gadlelo759@gmail.com" class="footer-link fw-bold">
                                Gad Lelo
                            </a>
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
.footer-modern {
    background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
    color: #ffffff;
    position: relative;
    overflow: hidden;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.footer-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
}

.footer-brand {
    display: flex;
    align-items: center;
}

.brand-icon-footer {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #007bff, #00c4b4);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
}

.social-links {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.social-link {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.social-link:hover {
    background: linear-gradient(135deg, #007bff, #00c4b4);
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
}

.footer-links {
    list-style: none;
    padding: 0;
}

.footer-link {
    color: rgba(255, 255, 255, 0.95);
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-block;
    padding: 0.25rem 0;
    position: relative;
    font-weight: 500;
}

.footer-link::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, #007bff, #00c4b4);
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.footer-link:hover {
    color: #ffffff;
    transform: translateX(5px);
    text-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
}

.footer-link:hover::before {
    width: 100%;
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.contact-icon {
    width: 35px;
    height: 35px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #007bff;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.contact-details {
    flex: 1;
}

.footer-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    border: none;
}

.footer-bottom {
    padding-top: 1rem;
}

.footer-bottom p {
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Animation d'entrée pour le footer */
@keyframes slideUp {
    from {
        transform: translateY(50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.footer-modern {
    animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Responsive design */
@media (max-width: 768px) {
    .footer-brand {
        justify-content: center;
        margin-bottom: 2rem;
    }
    
    .social-links {
        justify-content: center;
    }
    
    .footer-bottom {
        text-align: center;
    }
    
    .footer-bottom .row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .contact-item {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
}

@media (max-width: 576px) {
    .footer-modern {
        padding: 3rem 0;
    }
    
    .brand-icon-footer {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }
    
    .social-link {
        width: 35px;
        height: 35px;
    }
}

/* Effet de focus pour l'accessibilité */
.footer-link:focus,
.social-link:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
    border-radius: 4px;
}

/* Animation pour les icônes au survol */
.footer-link i,
.social-link i {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.footer-link:hover i,
.social-link:hover i {
    transform: scale(1.1);
}
</style>