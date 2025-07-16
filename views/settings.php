<?php
session_start();

// Définir le chemin de base pour les inclusions
define('BASE_DIR', dirname(__DIR__, 1));
require_once BASE_DIR . '/includes/db_connect.php';
require_once BASE_DIR . '/includes/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Définir le chemin de redirection
    $login_path = '/views/auth/login.php'; // Chemin relatif par rapport à BASE_URL

    // Si on est en local (WAMP), on peut utiliser le chemin absolu pour le développement
    if (defined('BASE_URL') && strpos(BASE_URL, 'localhost') !== false) {
        $login_path = 'C:/wamp64/www/laila_workspace/views/auth/login.php';
        // Redirection avec un chemin absolu (pour le développement local uniquement)
        header('Location: file:///' . str_replace('\\', '/', $login_path));
    } else {
        // Redirection avec BASE_URL pour un environnement serveur
        header('Location: ' . BASE_URL . $login_path);
    }
    exit();
}

// Charger les informations de l'utilisateur
$user = null;
try {
    $stmt = $pdo->prepare("SELECT email, profile_picture FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérifier si l'utilisateur existe
    if (!$user) {
        $_SESSION['error'] = "Utilisateur non trouvé. Veuillez vous reconnecter.";
        header('Location: ../auth/logout.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des informations utilisateur : " . $e->getMessage();
    header('Location: ../dashboard.php');
    exit();
}

// Mettre à jour les informations de l'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $profile_picture_path = $user['profile_picture']; // Garder l'ancienne photo par défaut

    // Gestion de l'upload de la photo de profil
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = BASE_DIR . '/uploads/profile_pictures/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Créer le dossier s'il n'existe pas
        }

        $file_name = $_SESSION['user_id'] . '_' . time() . '_' . basename($_FILES['profile_picture']['name']);
        $target_path = $upload_dir . $file_name;

        // Vérifier le type de fichier (seulement images)
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['profile_picture']['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error'] = "Seules les images JPEG, PNG ou GIF sont autorisées.";
        } elseif ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) { // Limite de 5 Mo
            $_SESSION['error'] = "La taille de l'image ne doit pas dépasser 5 Mo.";
        } else {
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_path)) {
                $profile_picture_path = '/uploads/profile_pictures/' . $file_name;

                // Supprimer l'ancienne photo si elle existe
                if ($user['profile_picture'] && file_exists(BASE_DIR . $user['profile_picture'])) {
                    unlink(BASE_DIR . $user['profile_picture']);
                }
            } else {
                $_SESSION['error'] = "Erreur lors de l'upload de la photo de profil.";
            }
        }
    }

    if (!isset($_SESSION['error'])) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Adresse email invalide.";
        } else {
            try {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET email = :email, password = :password, profile_picture = :profile_picture WHERE id = :id");
                    $stmt->execute([
                        'email' => $email,
                        'password' => $hashed_password,
                        'profile_picture' => $profile_picture_path,
                        'id' => $_SESSION['user_id']
                    ]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET email = :email, profile_picture = :profile_picture WHERE id = :id");
                    $stmt->execute([
                        'email' => $email,
                        'profile_picture' => $profile_picture_path,
                        'id' => $_SESSION['user_id']
                    ]);
                }
                $_SESSION['success'] = "Informations mises à jour avec succès !";
                header("Location: settings.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la mise à jour des informations : " . $e->getMessage();
            }
        }
    }
}

// Supprimer le compte de l'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    try {
        // Supprimer la photo de profil si elle existe
        if ($user['profile_picture'] && file_exists(BASE_DIR . $user['profile_picture'])) {
            unlink(BASE_DIR . $user['profile_picture']);
        }

        // Supprimer l'utilisateur (les projets associés seront supprimés automatiquement grâce à ON DELETE CASCADE)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);

        // Déconnecter l'utilisateur
        session_unset();
        session_destroy();

        // Rediriger vers la page de connexion avec un message de succès
        $_SESSION['success'] = "Votre compte a été supprimé avec succès.";
        header('Location: ' . BASE_URL . '/views/auth/login.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression du compte : " . $e->getMessage();
        header("Location: settings.php");
        exit();
    }
}

// Charger l'historique des BMP
$projects = [];
try {
    $stmt = $pdo->prepare("SELECT id, name, created_at FROM projects WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération de l'historique des projets : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include './layouts/navbar.php'; ?>

    <div class="container my-5">
        <div class="settings-grid">
        <!-- Section info/conseil utilisateur -->
        <div class="user-info-box mb-4" style="grid-column: 1 / -1;">
            <i class="bi bi-info-circle"></i>
            <div>
                <strong>Bienvenue dans vos paramètres !</strong><br>
                Ici, vous pouvez gérer votre compte, personnaliser votre profil, consulter l’historique de vos projets et supprimer votre compte si besoin.<br>
                <span class="text-muted">Astuce : gardez votre profil à jour pour une expérience optimale sur Laila Workspace.</span>
            </div>
        </div>
        <!-- Afficher les messages d'erreur ou de succès -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>


        <h2 class="section-title text-center mb-5">Paramètres du compte</h2>

        <!-- Gestion du compte -->
        <div class="settings-section mb-5">
            <h4 class="text-primary mb-4"><i class="bi bi-person-circle me-2"></i> Gestion du Compte</h4>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_account" value="1">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Adresse Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Nouveau Mot de Passe (laisser vide pour ne pas changer)</label>
                        <input type="password" class="form-control" id="password" name="password">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="profile_picture" class="form-label">Photo de Profil</label>
                        <div class="profile-picture-container mb-3">
                            <?php if ($user['profile_picture']): ?>
                                <div class="current-profile-picture">
                                    <img src="<?= BASE_URL . htmlspecialchars($user['profile_picture']) ?>" alt="Photo de profil actuelle" class="profile-picture rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid var(--primary-color);">
                                    <div class="profile-picture-overlay">
                                        <i class="bi bi-camera"></i>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="default-profile-picture">
                                    <div class="profile-placeholder rounded-circle" style="width: 120px; height: 120px; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <div class="profile-picture-overlay">
                                        <i class="bi bi-camera"></i>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="input-group">
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*" style="display: none;">
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('profile_picture').click()">
                                <i class="bi bi-upload me-2"></i>Choisir une photo
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="previewProfilePicture()">
                                <i class="bi bi-eye me-2"></i>Aperçu
                            </button>
                        </div>
                        <small class="form-text text-muted">Formats acceptés : JPEG, PNG, GIF. Taille max : 5 Mo.</small>
                        <div id="profile-preview" class="mt-2" style="display: none;">
                            <img id="preview-image" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover; border: 2px solid var(--primary-color);">
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-save me-2"></i> Mettre à Jour
                    </button>
                </div>
            </form>
        </div>

        <!-- Section Préférences utilisateur -->
        <div class="settings-section mb-5">
            <h4 class="text-primary mb-4"><i class="bi bi-sliders me-2"></i> Préférences</h4>
            <form method="POST">
                <div class="row align-items-center mb-3">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Thème de l’interface</label>
                        <select class="form-select" name="theme" disabled>
                            <option value="light">Clair</option>
                            <option value="dark">Sombre</option>
                        </select>
                        <small class="form-text text-muted">(Bientôt disponible)</small>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Langue</label>
                        <select class="form-select" name="lang" disabled>
                            <option value="fr">Français</option>
                            <option value="en">English</option>
                        </select>
                        <small class="form-text text-muted">(Bientôt disponible)</small>
                    </div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="notifEmail" name="notif_email" checked disabled>
                    <label class="form-check-label" for="notifEmail">Recevoir les notifications importantes par email <span class="text-muted">(Bientôt disponible)</span></label>
                </div>
            </form>
        </div>

        <!-- Section Sécurité -->
        <div class="settings-section mb-5">
            <h4 class="text-primary mb-4"><i class="bi bi-shield-lock me-2"></i> Sécurité</h4>
            <ul class="list-group mb-3">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>Dernière connexion</span>
                    <span class="badge bg-light text-primary"><?php echo date('d/m/Y H:i', $_SESSION['last_login'] ?? time()); ?></span>
                </li>
            </ul>
            <div class="d-flex flex-wrap gap-3">
                <a href="../auth/logout.php" class="btn btn-outline-primary"><i class="bi bi-box-arrow-right"></i> Déconnexion partout</a>
                <a href="../auth/forgot_password.php" class="btn btn-outline-secondary"><i class="bi bi-key"></i> Réinitialiser le mot de passe</a>
            </div>
        </div>

        <!-- Section Support & Aide -->
        <div class="settings-section mb-5">
            <h4 class="text-primary mb-4"><i class="bi bi-question-circle me-2"></i> Support & Aide</h4>
            <ul class="list-group mb-3">
                <li class="list-group-item"><a href="/views/pages/guide.php" class="text-decoration-none"><i class="bi bi-journal-text me-2"></i> Guide d’utilisation</a></li>
                <li class="list-group-item"><a href="mailto:support@lailaworkspace.com" class="text-decoration-none"><i class="bi bi-envelope me-2"></i> Contacter le support</a></li>
                <li class="list-group-item"><a href="/views/pages/terms.php" class="text-decoration-none"><i class="bi bi-file-earmark-text me-2"></i> Conditions d’utilisation</a></li>
            </ul>
            <div class="alert alert-info mb-0"><i class="bi bi-lightbulb me-2"></i> Pour toute question, consultez la FAQ ou contactez notre équipe !</div>
        </div>

        <!-- Modal de confirmation pour la suppression -->
        <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteAccountModalLabel">Confirmer la suppression</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible et supprimera toutes vos données, y compris vos projets.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <form method="POST">
                            <input type="hidden" name="delete_account" value="1">
                            <button type="submit" class="btn btn-danger">Oui, supprimer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historique des BMP -->
        <div class="history-section mt-5 mb-5">
            <h4 class="text-primary mb-4"><i class="bi bi-clock-history me-2"></i> Historique des BMP Créés</h4>
            <?php if (empty($projects)): ?>
                <p class="text-muted">Aucun projet créé pour le moment.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nom du Projet</th>
                                <th>Date de Création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td><?= htmlspecialchars($project['name']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($project['created_at'])) ?></td>
                                    <td>
                                        <a href="bmc/visualisation.php?project_id=<?= $project['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i> Voir
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section Suppression de compte (tout en bas) -->
        <div class="settings-section mt-5 mb-5 border-top pt-5">
            <div class="text-center">
                <h4 class="text-danger mb-4"><i class="bi bi-exclamation-triangle me-2"></i> Zone Dangereuse</h4>
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-warning me-2"></i>
                    <strong>Attention :</strong> La suppression de votre compte est irréversible. Toutes vos données, projets et informations seront définitivement supprimés.
                </div>
                <button type="button" class="btn btn-danger btn-lg px-5 py-3" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                    <i class="bi bi-trash me-2"></i> Supprimer mon compte définitivement
                </button>
                <p class="text-muted mt-3">
                    <small>Cette action ne peut pas être annulée. Assurez-vous d'avoir sauvegardé toutes vos données importantes.</small>
                </p>
            </div>
        </div>
        <!-- fin des sections -->
    </div>
    </div>

    <?php include './layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Prévisualisation de la photo de profil
        function previewProfilePicture() {
            const fileInput = document.getElementById('profile_picture');
            const preview = document.getElementById('profile-preview');
            const previewImage = document.getElementById('preview-image');
            
            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(fileInput.files[0]);
            } else {
                alert('Veuillez d\'abord sélectionner une image.');
            }
        }

        // Aperçu automatique lors de la sélection d'un fichier
        document.getElementById('profile_picture').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                previewProfilePicture();
            }
        });

        // Animation au survol de la photo de profil
        document.addEventListener('DOMContentLoaded', function() {
            const profileContainer = document.querySelector('.profile-picture-container');
            if (profileContainer) {
                profileContainer.addEventListener('mouseenter', function() {
                    const overlay = this.querySelector('.profile-picture-overlay');
                    if (overlay) {
                        overlay.style.opacity = '1';
                    }
                });
                
                profileContainer.addEventListener('mouseleave', function() {
                    const overlay = this.querySelector('.profile-picture-overlay');
                    if (overlay) {
                        overlay.style.opacity = '0';
                    }
                });
            }
        });
    </script>
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            flex: 1 0 auto;
            padding-bottom: 60px; /* Plus d'espace pour séparer du footer */
        }
        .footer-modern {
            flex-shrink: 0;
            width: 100%;
            margin-top: auto; /* Pousse le footer vers le bas */
        }

        /* Styles pour la photo de profil */
        .profile-picture-container {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .current-profile-picture,
        .default-profile-picture {
            position: relative;
            display: inline-block;
        }

        .profile-picture-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            color: white;
            font-size: 1.5rem;
        }

        .profile-picture-container:hover .profile-picture-overlay {
            opacity: 1;
        }

        .profile-picture-container:hover {
            transform: scale(1.05);
            transition: transform 0.3s ease;
        }

        .input-group .btn {
            border-radius: 8px;
        }

        #profile-preview {
            text-align: center;
        }

        /* Animation pour le bouton de suppression */
        .btn-danger {
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-picture-container img,
            .profile-placeholder {
                width: 100px !important;
                height: 100px !important;
            }
            
            .input-group {
                flex-direction: column;
            }
            
            .input-group .btn {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</body>
</html>