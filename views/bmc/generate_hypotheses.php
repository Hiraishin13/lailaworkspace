<?php
session_start();

// Définir le répertoire de base et inclure les fichiers nécessaires
define('BASE_DIR', dirname(__DIR__, 2));
require_once BASE_DIR . '/includes/db_connect.php';
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/vendor/autoload.php';

// Définir le type de contenu pour les réponses JSON
header('Content-Type: application/json; charset=UTF-8');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit();
}

// Vérifier si project_id est spécifié et valide
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
    error_log('Erreur lors de la récupération du projet : ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du projet']);
    exit();
}

// Configurer le client OpenAI
try {
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        throw new Exception('Clé API OpenAI non configurée.');
    }
    $client = \OpenAI::client(OPENAI_API_KEY);
} catch (Exception $e) {
    error_log('Erreur de configuration de l\'API OpenAI : ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de configuration de l\'API OpenAI']);
    exit();
}

// Créer le prompt pour générer des hypothèses
$api_prompt = <<<EOD
Tu es un expert en création de Business Model Canvas et en génération d'hypothèses testables.
Génère 4 à 5 hypothèses testables pour le Business Model Canvas du projet suivant : "{$project['description']}".
Chaque hypothèse doit être concise, claire et commencer par "Nous supposons que".
Par exemple : "Nous supposons que les clients seront prêts à payer 10€ par mois pour ce service."
Fournis les hypothèses sous forme de liste, une par ligne, sans numérotation ni puces.
EOD;

try {
    $response = $client->chat()->create([
        'model' => 'gpt-3.5-turbo', 
        'messages' => [
            ['role' => 'user', 'content' => $api_prompt], 
        ],
        'max_tokens' => 500,
        'temperature' => 0.7,
    ]);

    // Vérifier si la réponse contient du contenu
    if (!isset($response->choices[0]->message->content)) {
        throw new Exception('Réponse vide ou invalide de l\'API OpenAI');
    }

    $generated_text = $response->choices[0]->message->content;
    $hypotheses = array_filter(array_map('trim', explode("\n", $generated_text)));

    // Vérifier si des hypothèses ont été générées
    if (empty($hypotheses)) {
        throw new Exception('Aucune hypothèse générée par l\'API OpenAI');
    }

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
    error_log('Erreur lors de la génération des hypothèses : ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la génération des hypothèses']);
    exit();
}
?>