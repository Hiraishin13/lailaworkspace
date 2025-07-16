<?php
// Configuration des notifications pour Laila Workspace

// Types de notifications disponibles
define('NOTIFICATION_TYPES', [
    'welcome' => [
        'title' => 'Bienvenue',
        'icon' => 'bi-emoji-smile',
        'color' => 'success',
        'important' => true
    ],
    'bmc_completion' => [
        'title' => 'BMC Complété',
        'icon' => 'bi-check-circle',
        'color' => 'success',
        'important' => false
    ],
    'hypothesis_reminder' => [
        'title' => 'Rappel Hypothèses',
        'icon' => 'bi-lightbulb',
        'color' => 'warning',
        'important' => false
    ],
    'financial_plan' => [
        'title' => 'Plan Financier',
        'icon' => 'bi-wallet2',
        'color' => 'info',
        'important' => false
    ],
    'partnership_suggestion' => [
        'title' => 'Suggestion Partenariat',
        'icon' => 'bi-people',
        'color' => 'primary',
        'important' => true
    ],
    'achievement' => [
        'title' => 'Achievement',
        'icon' => 'bi-trophy',
        'color' => 'warning',
        'important' => false
    ],
    'system_alert' => [
        'title' => 'Alerte Système',
        'icon' => 'bi-exclamation-triangle',
        'color' => 'danger',
        'important' => true
    ],
    'admin_broadcast' => [
        'title' => 'Message Admin',
        'icon' => 'bi-megaphone',
        'color' => 'primary',
        'important' => true
    ],
    'maintenance' => [
        'title' => 'Maintenance',
        'icon' => 'bi-tools',
        'color' => 'warning',
        'important' => true
    ],
    'update' => [
        'title' => 'Mise à Jour',
        'icon' => 'bi-arrow-clockwise',
        'color' => 'info',
        'important' => false
    ]
]);

// Configuration des achievements
define('ACHIEVEMENTS', [
    'first_bmc' => [
        'title' => 'Premier BMC',
        'description' => 'Vous avez créé votre premier Business Model Canvas',
        'icon' => 'bi-rocket-takeoff',
        'points' => 100,
        'message' => 'Félicitations ! Vous avez créé votre premier BMC.'
    ],
    'bmc_completed' => [
        'title' => 'BMC Complété',
        'description' => 'Vous avez rempli tous les blocs de votre BMC',
        'icon' => 'bi-check-circle',
        'points' => 200,
        'message' => 'Excellent ! Votre BMC est maintenant complet.'
    ],
    'hypothesis_created' => [
        'title' => 'Hypothèses Créées',
        'description' => 'Vous avez créé vos premières hypothèses',
        'icon' => 'bi-lightbulb',
        'points' => 150,
        'message' => 'Bravo ! Vous avez créé vos premières hypothèses.'
    ],
    'financial_plan_created' => [
        'title' => 'Plan Financier',
        'description' => 'Vous avez créé votre premier plan financier',
        'icon' => 'bi-wallet2',
        'points' => 300,
        'message' => 'Parfait ! Votre plan financier est prêt.'
    ],
    'partnership_found' => [
        'title' => 'Partenariat Trouvé',
        'description' => 'Vous avez trouvé un partenaire potentiel',
        'icon' => 'bi-people',
        'points' => 250,
        'message' => 'Incroyable ! Un partenaire potentiel a été trouvé.'
    ],
    'streak_7_days' => [
        'title' => 'Streak 7 Jours',
        'description' => 'Vous avez travaillé 7 jours consécutifs',
        'icon' => 'bi-calendar-check',
        'points' => 500,
        'message' => 'Impressionnant ! Vous maintenez un rythme régulier.'
    ],
    'streak_30_days' => [
        'title' => 'Streak 30 Jours',
        'description' => 'Vous avez travaillé 30 jours consécutifs',
        'icon' => 'bi-calendar-star',
        'points' => 1000,
        'message' => 'Extraordinaire ! Vous êtes un entrepreneur dédié !'
    ]
]);

// Messages de notifications par défaut
define('DEFAULT_NOTIFICATION_MESSAGES', [
    'welcome' => 'Bienvenue sur Laila Workspace ! Commencez votre aventure entrepreneuriale en créant votre premier BMC.',
    'bmc_completion' => 'Félicitations ! Votre BMC est complet. Passez aux hypothèses pour valider votre modèle.',
    'hypothesis_reminder' => 'N\'oubliez pas de compléter vos hypothèses pour avancer dans votre projet.',
    'financial_plan' => 'Votre plan financier a été généré avec succès. Consultez-le maintenant !',
    'partnership_suggestion' => 'Nous avons trouvé un partenaire potentiel pour votre projet !',
    'achievement' => 'Nouveau badge débloqué ! Continuez comme ça !',
    'system_alert' => 'Une alerte système a été détectée. Veuillez vérifier votre compte.',
    'admin_broadcast' => 'Message important de l\'équipe Laila Workspace.',
    'maintenance' => 'Une maintenance est prévue. Veuillez sauvegarder vos données.',
    'update' => 'Une nouvelle mise à jour est disponible !'
]);

// Configuration des préférences par défaut
define('DEFAULT_NOTIFICATION_PREFERENCES', [
    'email_notifications' => true,
    'push_notifications' => true,
    'bmc_completion_notifications' => true,
    'hypothesis_reminders' => true,
    'financial_plan_notifications' => true,
    'partnership_suggestions' => true,
    'system_alerts' => true,
    'achievement_notifications' => true
]);

// Durées d'expiration des notifications (en jours)
define('NOTIFICATION_EXPIRATION', [
    'welcome' => null, // Ne jamais expirer
    'bmc_completion' => null, // Ne jamais expirer
    'hypothesis_reminder' => 7, // 7 jours
    'financial_plan' => null, // Ne jamais expirer
    'partnership_suggestion' => 14, // 14 jours
    'achievement' => null, // Ne jamais expirer
    'system_alert' => 3, // 3 jours
    'admin_broadcast' => null, // Ne jamais expirer
    'maintenance' => 1, // 1 jour
    'update' => 7 // 7 jours
]);

// Seuils pour les achievements automatiques
define('ACHIEVEMENT_THRESHOLDS', [
    'first_bmc' => 1,
    'bmc_completed' => 1,
    'hypothesis_created' => 5,
    'financial_plan_created' => 1,
    'partnership_found' => 1,
    'streak_7_days' => 7,
    'streak_30_days' => 30
]);
?> 