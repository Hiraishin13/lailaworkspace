<?php
session_start();
define('BASE_DIR', dirname(__DIR__, 2));
require_once BASE_DIR . '/includes/db_connect.php';
require_once BASE_DIR . '/includes/config.php';

error_log("Début de update_bmc_block.php - Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'non connecté'));

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Vous devez être connecté pour modifier un bloc.';
    error_log("Erreur: Utilisateur non connecté");
    header('Location: ' . BASE_URL . '/views/auth/login.php');
    exit();
}

// Vérifier si la requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Méthode de requête invalide.';
    error_log("Erreur: Méthode non POST - " . $_SERVER['REQUEST_METHOD']);
    header('Location: ' . BASE_URL . '/views/dashboard.php');
    exit();
}

// Récupérer les données du formulaire
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$block_name = isset($_POST['block_name']) ? trim($_POST['block_name']) : '';
$content = isset($_POST['block_content']) ? trim($_POST['block_content']) : '';

error_log("Données reçues - project_id: $project_id, block_name: $block_name, content: " . substr($content, 0, 100));

// Liste des blocs valides
$valid_blocks = [
    'segments_clientele', 'proposition_valeur', 'canaux', 'relations_clients',
    'sources_revenus', 'ressources_cles', 'activites_cles', 'partenaires_cles', 'structure_couts'
];

// Validation des données
if ($project_id <= 0) {
    $_SESSION['error'] = 'ID du projet invalide.';
    error_log("Erreur: project_id invalide - $project_id");
    header('Location: ' . BASE_URL . '/views/dashboard.php');
    exit();
}
if (!in_array($block_name, $valid_blocks)) {
    $_SESSION['error'] = 'Nom du bloc invalide.';
    error_log("Erreur: block_name invalide - $block_name");
    header('Location: ' . BASE_URL . '/views/dashboard.php');
    exit();
}
if (empty($content)) {
    $_SESSION['error'] = 'Le contenu ne peut pas être vide.';
    error_log("Erreur: Contenu vide");
    header('Location: ' . BASE_URL . '/views/bmc/visualisation.php?project_id=' . $project_id);
    exit();
}
if (strlen($content) > 2000) {
    $_SESSION['error'] = 'Le contenu est trop long (maximum 2000 caractères).';
    error_log("Erreur: Contenu trop long - " . strlen($content));
    header('Location: ' . BASE_URL . '/views/bmc/visualisation.php?project_id=' . $project_id);
    exit();
}

// Vérifier l'accès au projet
try {
    $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $project_id, 'user_id' => $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'Projet non trouvé ou accès non autorisé.';
        error_log("Erreur: Projet non trouvé ou accès non autorisé - project_id: $project_id, user_id: {$_SESSION['user_id']}");
        header('Location: ' . BASE_URL . '/views/dashboard.php');
        exit();
    }

    // Mettre à jour ou insérer le contenu du bloc
    $stmt = $pdo->prepare("UPDATE bmc SET content = :content WHERE project_id = :project_id AND block_name = :block_name");
    $stmt->execute([
        'content' => $content,
        'project_id' => $project_id,
        'block_name' => $block_name
    ]);

    if ($stmt->rowCount() === 0) {
        error_log("Aucune ligne affectée par UPDATE, tentative d'INSERT - project_id: $project_id, block_name: $block_name");
        $stmt = $pdo->prepare("INSERT INTO bmc (project_id, block_name, content) VALUES (:project_id, :block_name, :content)");
        $stmt->execute([
            'project_id' => $project_id,
            'block_name' => $block_name,
            'content' => $content
        ]);
    }

    $_SESSION['success'] = 'Bloc mis à jour avec succès.';
    error_log("Succès: Bloc mis à jour - project_id: $project_id, block_name: $block_name");
} catch (PDOException $e) {
    $_SESSION['error'] = 'Erreur lors de la mise à jour dans la base de données.';
    error_log("Erreur PDO: " . $e->getMessage());
    header('Location: ' . BASE_URL . '/views/bmc/visualisation.php?project_id=' . $project_id);
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur inattendue.';
    error_log("Erreur générale: " . $e->getMessage());
    header('Location: ' . BASE_URL . '/views/bmc/visualisation.php?project_id=' . $project_id);
    exit();
}

// Rediriger vers visualisation.php avec le project_id
header('Location: ' . BASE_URL . '/views/bmc/visualisation.php?project_id=' . $project_id);
exit();
?>