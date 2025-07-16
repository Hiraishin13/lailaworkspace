<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    exit('Connecte-toi d\'abord !');
}

$user_id = $_SESSION['user_id'];

// 1. Créer deux projets pour l'utilisateur
$stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id = ?");
$stmt->execute([$user_id]);
$nb_projects = $stmt->fetchColumn();

if ($nb_projects < 2) {
    $projects = [
        ['Projet Alpha', 'Tech', 'Idée', $user_id],
        ['Projet Beta', 'Santé', 'Prototype', $user_id]
    ];
    $stmt = $pdo->prepare("INSERT INTO projects (name, sector, development_stage, user_id) VALUES (?, ?, ?, ?)");
    foreach ($projects as $p) {
        $stmt->execute($p);
    }
    echo "Projets de test créés.<br>";
}

// 2. Récupérer deux projets de l'utilisateur
$stmt = $pdo->prepare("SELECT id FROM projects WHERE user_id = ? LIMIT 2");
$stmt->execute([$user_id]);
$proj = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (count($proj) < 2) exit('Pas assez de projets pour créer des partenariats.');
$project1_id = $proj[0];
$project2_id = $proj[1];

// 3. Créer un partenariat entre ces deux projets
$stmt = $pdo->prepare("SELECT COUNT(*) FROM partnerships WHERE (project1_id = ? AND project2_id = ?) OR (project1_id = ? AND project2_id = ?)");
$stmt->execute([$project1_id, $project2_id, $project2_id, $project1_id]);
if ($stmt->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO partnerships (project1_id, project2_id, status, created_at, updated_at) VALUES (?, ?, 'pending', NOW(), NOW())");
    $stmt->execute([$project1_id, $project2_id]);
    echo "Partenariat de test créé.<br>";
}

// 4. Créer une suggestion de partenariat avec un autre projet fictif
// On crée d'abord un projet fictif pour un autre utilisateur
$stmt = $pdo->prepare("SELECT id FROM users WHERE id != ? LIMIT 1");
$stmt->execute([$user_id]);
$other_user_id = $stmt->fetchColumn();
if (!$other_user_id) {
    // Créer un autre utilisateur fictif
    $stmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, password) VALUES ('autreuser', 'Autre', 'User', 'autreuser@example.com', 'test')");
    $stmt->execute();
    $other_user_id = $pdo->lastInsertId();
}
$stmt = $pdo->prepare("INSERT INTO projects (name, sector, development_stage, user_id) VALUES (?, ?, ?, ?)");
$stmt->execute(['Projet Gamma', 'Finance', 'Croissance', $other_user_id]);
$other_project_id = $pdo->lastInsertId();

// Créer une suggestion de partenariat
$stmt = $pdo->prepare("SELECT COUNT(*) FROM partnership_suggestions WHERE (project1_id = ? AND project2_id = ?) OR (project1_id = ? AND project2_id = ?)");
$stmt->execute([$project1_id, $other_project_id, $other_project_id, $project1_id]);
if ($stmt->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO partnership_suggestions (project1_id, project2_id, compatibility_score, status, created_at) VALUES (?, ?, 75, 'pending', NOW())");
    $stmt->execute([$project1_id, $other_project_id]);
    echo "Suggestion de partenariat créée.<br>";
}

echo "<b>Données de test insérées !</b> Tu peux maintenant recharger la page des partenariats."; 