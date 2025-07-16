<?php
/**
 * Script de déploiement pour Hostinger
 * À exécuter une seule fois après le déploiement Git
 */

echo "=== Script de déploiement Hostinger ===\n\n";

// 1. Vérifier les prérequis
echo "1. Vérification des prérequis...\n";
if (!extension_loaded('pdo_mysql')) {
    die("❌ Extension PDO MySQL non disponible\n");
}
if (!extension_loaded('curl')) {
    die("❌ Extension cURL non disponible\n");
}
echo "✅ Prérequis OK\n\n";

// 2. Créer les dossiers nécessaires
echo "2. Création des dossiers...\n";
$directories = [
    'uploads/profile_pictures',
    'uploads/financial_statements',
    'logs'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Dossier créé: $dir\n";
        } else {
            echo "❌ Erreur création dossier: $dir\n";
        }
    } else {
        echo "✅ Dossier existe: $dir\n";
    }
}

// 3. Vérifier les permissions
echo "\n3. Vérification des permissions...\n";
$writable_dirs = [
    'uploads',
    'uploads/profile_pictures',
    'uploads/financial_statements',
    'logs'
];

foreach ($writable_dirs as $dir) {
    if (is_writable($dir)) {
        echo "✅ $dir est accessible en écriture\n";
    } else {
        echo "⚠️  $dir n'est pas accessible en écriture\n";
        echo "   Chmod recommandé: chmod 755 $dir\n";
    }
}

// 4. Instructions pour la configuration
echo "\n4. Configuration requise:\n";
echo "   a) Créer une base de données MySQL sur Hostinger\n";
echo "   b) Copier le contenu de database/create_tables.sql\n";
echo "   c) Modifier includes/db_connect_hostinger.php avec vos infos DB\n";
echo "   d) Renommer db_connect_hostinger.php en db_connect.php\n";
echo "   e) Configurer votre domaine dans BASE_URL\n";
echo "   f) Configurer les paramètres SMTP Hostinger\n\n";

echo "5. Fichiers SQL à exécuter dans l'ordre:\n";
echo "   - database/create_tables.sql\n";
echo "   - database/create_notifications_table.sql\n";
echo "   - database/create_partnerships_table.sql\n";
echo "   - database/admin_tables.sql\n";
echo "   - setup_admin_user.sql\n\n";

echo "6. Test de connexion:\n";
echo "   Visitez votre site pour vérifier que tout fonctionne\n";
echo "   URL d'administration: https://votre-domaine.com/views/admin/\n\n";

echo "=== Déploiement terminé ===\n";
echo "N'oubliez pas de supprimer ce fichier après configuration!\n";
?> 