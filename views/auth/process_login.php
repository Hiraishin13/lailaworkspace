<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode non autorisée.";
    header('Location: login.php');
    exit();
}

// Vérifier le jeton CSRF pour la sécurité
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Erreur de validation CSRF. Veuillez réessayer.";
    header('Location: login.php');
    exit();
}

// Récupérer les données du formulaire
$email = trim($_POST['email']);
$password = $_POST['password'];

// Validation des champs
if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Veuillez remplir tous les champs.";
    header('Location: login.php');
    exit();
}

// Vérifier les identifiants dans la base de données
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        $user && (
            password_verify($password, $user['password'])
            || $password === $user['password']
        )
    ) {
        // Connexion réussie
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];

        // Vérifier si une URL de redirection est stockée dans la session
        if (isset($_SESSION['redirect_after_login'])) {
            $redirect_url = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']); // Supprimer l'URL après utilisation

            // Si l'utilisateur est maintenant connecté et qu'il y a des données temporaires, les enregistrer dans la base de données
            if (isset($_SESSION['temp_project'])) {
                $prompt = $_SESSION['temp_project']['description'];
                $generated_bmc = $_SESSION['temp_project']['generated_bmc'];

                // Enregistrer le projet dans la table `projects`
                $stmt = $pdo->prepare("INSERT INTO projects (user_id, name, description) VALUES (:user_id, :name, :description)");
                $stmt->execute([
                    'user_id' => $_SESSION['user_id'],
                    'name' => "BMC généré pour : " . substr($prompt, 0, 50),
                    'description' => $prompt
                ]);
                $project_id = $pdo->lastInsertId();

                // Enregistrer les 9 piliers dans la table `bmc`
                $stmt = $pdo->prepare("INSERT INTO bmc (project_id, block_name, content) VALUES (:project_id, :block_name, :content)");
                $blocks = [
                    'partenaires_cles' => $generated_bmc['partenaires_cles'],
                    'ressources_cles' => $generated_bmc['ressources_cles'],
                    'segments_clientele' => $generated_bmc['segments_clientele'],
                    'activites_cles' => $generated_bmc['activites_cles'],
                    'proposition_valeur' => $generated_bmc['proposition_valeur'],
                    'canaux' => $generated_bmc['canaux'],
                    'structure_couts' => $generated_bmc['structure_couts'],
                    'sources_revenus' => $generated_bmc['sources_revenus'],
                    'relations_clients' => $generated_bmc['relations_clients']
                ];

                foreach ($blocks as $block_name => $content) {
                    $stmt->execute([
                        'project_id' => $project_id,
                        'block_name' => $block_name,
                        'content' => $content
                    ]);
                }

                // Ajouter l'ID du projet à la session pour l'utiliser dans visualisation.php
                $_SESSION['temp_project']['project_id'] = $project_id;

                // Supprimer les données temporaires après enregistrement
                // Ne pas unset ici, car visualisation.php en a besoin pour afficher le BMC
                // unset($_SESSION['temp_project']);
            }

            // Rediriger vers l'URL stockée
            header('Location: ' . $redirect_url);
        } else {
            // Redirection par défaut
            header('Location: ' . BASE_URL . '/views/bmc/generate_bmc.php?login_success=true');
        }
        exit();
    } else {
        $_SESSION['error'] = "Email ou mot de passe incorrect.";
        header('Location: login.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la connexion : " . $e->getMessage();
    header('Location: login.php');
    exit();
}