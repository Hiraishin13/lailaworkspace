<?php
/**
 * Installation en un clic - Laila Workspace
 * Exécutez ce fichier une seule fois après l'upload
 */

// Inclure la connexion
require_once 'includes/db_connect.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Installation Laila Workspace</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚀 Installation Laila Workspace</h1>";

// Vérifier la connexion
try {
    $pdo->query("SELECT 1");
    echo "<p class='success'>✅ Connexion à la base de données réussie</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur de connexion à la base de données</p>";
    echo "<p>Vérifiez la configuration dans includes/db_connect.php</p>";
    exit;
}

// Fichiers SQL à importer
$sqlFiles = [
    'database/create_tables.sql',
    'database/create_notifications_table.sql', 
    'database/create_partnerships_table.sql',
    'database/admin_tables.sql',
    'setup_admin_user.sql'
];

$totalSuccess = 0;
$totalErrors = 0;

echo "<h2>📊 Import de la base de données</h2>";

foreach ($sqlFiles as $file) {
    echo "<h3>📁 $file</h3>";
    
    if (file_exists($file)) {
        $sql = file_get_contents($file);
        
        // Nettoyer les instructions problématiques
        $sql = preg_replace('/\/\*!40101.*?\*\/;/s', '', $sql);
        $sql = preg_replace('/SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;/', '', $sql);
        $sql = preg_replace('/SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;/', '', $sql);
        $sql = preg_replace('/SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;/', '', $sql);
        $sql = preg_replace('/SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;/', '', $sql);
        $sql = preg_replace('/SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;/', '', $sql);
        $sql = preg_replace('/SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;/', '', $sql);
        
        $queries = explode(';', $sql);
        $success = 0;
        $errors = 0;
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query) && strlen($query) > 10) {
                try {
                    $pdo->exec($query);
                    $success++;
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'already exists') !== false) {
                        echo "<p class='warning'>⚠️ Table déjà existante (ignorée)</p>";
                    } else {
                        echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
                        $errors++;
                    }
                }
            }
        }
        
        echo "<p class='success'>✅ $success requêtes exécutées</p>";
        if ($errors > 0) {
            echo "<p class='warning'>⚠️ $errors erreurs non critiques</p>";
        }
        
        $totalSuccess += $success;
        $totalErrors += $errors;
        
    } else {
        echo "<p class='error'>❌ Fichier $file non trouvé</p>";
    }
}

echo "<h2>🎯 Résumé</h2>";
echo "<p class='success'>✅ Total: $totalSuccess requêtes réussies</p>";
if ($totalErrors > 0) {
    echo "<p class='warning'>⚠️ Total: $totalErrors erreurs non critiques</p>";
}

echo "<h2>🚀 Installation terminée !</h2>
<p>Votre site Laila Workspace est maintenant prêt.</p>

<h3>🔗 Liens importants :</h3>
<p><a href='views/index.php' class='btn'>🏠 Site principal</a></p>
<p><a href='views/admin/' class='btn'>⚙️ Administration</a></p>

<h3>🔐 Identifiants admin :</h3>
<p><strong>Email :</strong> admin@lailaworkspace.com</p>
<p><strong>Mot de passe :</strong> admin123</p>

<h3>⚠️ Sécurité :</h3>
<p class='warning'>Supprimez ce fichier install.php après utilisation !</p>

</div>
</body>
</html>";
?> 