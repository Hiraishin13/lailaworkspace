# 🚀 Guide de déploiement manuel - Laila Workspace

## 📋 Fichiers essentiels pour le déploiement

### Structure du projet
```
lailaworkspace/
├── api/                    # API endpoints
├── assets/                 # CSS, JS, images
├── config/                 # Configuration
├── controllers/            # Contrôleurs PHP
├── database/               # Scripts SQL
├── includes/               # Connexion DB et services
├── models/                 # Modèles de données
├── uploads/                # Dossiers d'upload (vides)
├── views/                  # Templates et pages
├── vendor/                 # Dépendances Composer
├── .htaccess              # Configuration Apache
├── composer.json          # Dépendances PHP
├── composer.lock          # Versions exactes
└── setup_admin_user.sql   # Utilisateur admin
```

## 🔧 Configuration requise

### 1. Base de données MySQL
- **Nom :** `u343759769_laila_db`
- **Utilisateur :** `u343759769_Lailaworkspace`
- **Mot de passe :** `Mauvaisgarcon04.com`
- **Hôte :** `localhost`

### 2. Configuration dans `includes/db_connect.php`
```php
define('BASE_URL', 'https://lailaworkspace.com');
```

### 3. Import de la base de données
Exécuter dans l'ordre :
1. `database/create_tables.sql`
2. `database/create_notifications_table.sql`
3. `database/create_partnerships_table.sql`
4. `database/admin_tables.sql`
5. `setup_admin_user.sql`

## 📁 Dossiers à créer avec permissions 755
- `uploads/profile_pictures/`
- `uploads/financial_statements/`
- `logs/`

## 🌐 URLs importantes
- **Site principal :** `https://lailaworkspace.com`
- **Administration :** `https://lailaworkspace.com/views/admin/`
- **API :** `https://lailaworkspace.com/api/`

## 🔐 Identifiants par défaut
- **Email :** `admin@lailaworkspace.com`
- **Mot de passe :** `admin123`

## ⚠️ Sécurité
- Supprimer ce fichier README après déploiement
- Changer le mot de passe admin par défaut
- Vérifier les permissions des dossiers

## 🎯 Déploiement terminé
Votre projet Laila Workspace est maintenant prêt ! 