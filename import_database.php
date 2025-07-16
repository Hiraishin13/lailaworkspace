<?php
/**
 * Script d'import de base de données pour déploiement manuel
 */

// Inclure la connexion
require_once 'includes/db_connect.php';

echo "<h2>Import de la base de données Laila Workspace</h2>";

// Fichiers SQL à importer dans l'ordre
$sqlFiles = [
    'database/create_tables.sql',
    'database/create_notifications_table.sql', 
    'database/create_partnerships_table.sql',
    'database/admin_tables.sql',
    'setup_admin_user.sql'
];

$totalSuccess = 0;
$totalErrors = 0;

foreach ($sqlFiles as $file) {
    echo "<h3>Import de $file</h3>";
    
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
                        echo "<span style='color: orange;'>⚠️ Table déjà existante (ignorée)</span><br>";
                    } else {
                        echo "<span style='color: red;'>❌ Erreur: " . $e->getMessage() . "</span><br>";
                        $errors++;
                    }
                }
            }
        }
        
        echo "<span style='color: green;'>✅ $success requêtes exécutées</span><br>";
        if ($errors > 0) {
            echo "<span style='color: orange;'>⚠️ $errors erreurs non critiques</span><br>";
        }
        
        $totalSuccess += $success;
        $totalErrors += $errors;
        
    } else {
        echo "<span style='color: red;'>❌ Fichier $file non trouvé</span><br>";
    }
    
    echo "<hr>";
}

echo "<h2>Résumé</h2>";
echo "<span style='color: green;'>✅ Total: $totalSuccess requêtes réussies</span><br>";
if ($totalErrors > 0) {
    echo "<span style='color: orange;'>⚠️ Total: $totalErrors erreurs non critiques</span><br>";
}

echo "<h3>Prochaines étapes:</h3>";
echo "<ul>";
echo "<li><a href='views/index.php'>Tester le site principal</a></li>";
echo "<li><a href='views/admin/'>Accéder à l'administration</a></li>";
echo "<li>Identifiants admin: admin@lailaworkspace.com / admin123</li>";
echo "<li>Supprimer ce fichier après utilisation</li>";
echo "</ul>";

echo "<p><strong>⚠️ IMPORTANT: Supprimez ce fichier pour la sécurité !</strong></p>";
?> 