<?php
session_start();

// Définir le chemin de base pour les inclusions
define('BASE_DIR', dirname(__DIR__, 1));
require_once BASE_DIR . '/includes/db_connect.php';
require_once BASE_DIR . '/includes/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
</head>
<body>
    <?php include './layouts/navbar.php'; ?>

    <div class="container my-5">
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

        <h2 class="section-title text-center mb-5">Paramètres</h2>

        <!-- Gestion du compte -->
        <div class="settings-section">
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
                        <?php if ($user['profile_picture']): ?>
                            <div class="mb-2">
                                <img src="<?= BASE_URL . htmlspecialchars($user['profile_picture']) ?>" alt="Photo de profil" class="profile-picture rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                        <small class="form-text text-muted">Formats acceptés : JPEG, PNG, GIF. Taille max : 5 Mo.</small>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Mettre à Jour</button>
                </div>
            </form>
        </div>

        <!-- Historique des BMP -->
        <div class="history-section mt-5">
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
    </div>

    <?php include './layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>