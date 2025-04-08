<?php
session_start();

define('BASE_DIR', dirname(__DIR__, 2));
require_once BASE_DIR . '/includes/db_connect.php';
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit();
}

if (!isset($_GET['project_id']) || !is_numeric($_GET['project_id'])) {
    echo json_encode(['success' => false, 'message' => 'Projet non spécifié']);
    exit();
}

$project_id = (int)$_GET['project_id'];

// Vérifier si le projet appartient à l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT description FROM projects WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $project_id, 'user_id' => $_SESSION['user_id']]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Projet non trouvé ou accès non autorisé']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du projet : ' . $e->getMessage()]);
    exit();
}

// Configurer le client OpenAI
try {
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        throw new Exception('Clé API OpenAI non configurée.');
    }
    $client = \OpenAI::client(OPENAI_API_KEY);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de configuration de l\'API OpenAI : ' . $e->getMessage()]);
    exit();
}

// Créer le prompt pour générer des hypothèses
$api_prompt = "Génère 4 à 5 hypothèses testables pour le Business Model Canvas du projet suivant : " . $project['description'] . ". Chaque hypothèse doit être concise, claire et commencer par 'Nous supposons que'. Par exemple : 'Nous supposons que les clients seront prêts à payer 10€ par mois pour ce service.' Fournis les hypothèses sous forme de liste, une par ligne.";

try {
    $response = $client->chat()->create([
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'system', 'content' => 'Tu es un expert en création de Business Model Canvas et en génération d\'hypothèses testables.'],
            ['role' => 'user', 'content' => $api_prompt],
        ],
        'max_tokens' => 500,
        'temperature' => 0.7,
    ]);

    $generated_text = $response->choices[0]->message->content;
    $hypotheses = array_filter(array_map('trim', explode("\n", $generated_text)));

    // Enregistrer les hypothèses dans la base de données
    $stmt = $pdo->prepare("INSERT INTO hypotheses (project_id, hypothesis_text) VALUES (:project_id, :hypothesis_text)");
    foreach ($hypotheses as $hypothesis) {
        if (!empty($hypothesis)) {
            $stmt->execute([
                'project_id' => $project_id,
                'hypothesis_text' => $hypothesis
            ]);
        }
    }

    // Rediriger vers hypotheses.php
    header("Location: hypotheses.php?project_id=$project_id");
    exit();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la génération des hypothèses : ' . $e->getMessage()]);
    exit();
}
?>