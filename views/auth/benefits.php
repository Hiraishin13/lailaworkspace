<?php
session_start();
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/db_connect.php';
require_once '../layouts/navbar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pourquoi utiliser Laila Workspace ?</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
    <style>
        .benefits-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .benefits-container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .hero-section {
            text-align: center;
            margin-bottom: 50px;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 20px;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: #718096;
            max-width: 600px;
            margin: 0 auto;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 600;
            color: #2d3748;
            text-align: center;
            margin: 50px 0 30px 0;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .benefit-card {
            background: white;
            border-radius: 6px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .benefit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .benefit-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 24px;
            color: white;
        }

        .benefit-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
        }

        .benefit-description {
            color: #718096;
            line-height: 1.6;
        }

        .cta-section {
            text-align: center;
            margin-top: 50px;
        }

        .cta-button {
            display: inline-block;
            padding: 15px 30px;
            margin: 0 10px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .cta-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .cta-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .cta-secondary {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .cta-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .benefits-container {
                padding: 30px 20px;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .benefits-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .cta-button {
                display: block;
                margin: 10px auto;
                max-width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="benefits-page">
        <div class="benefits-container">
            <div class="hero-section">
                <h1 class="hero-title">Pourquoi utiliser Laila Workspace ?</h1>
                <p class="hero-subtitle">Découvrez les avantages de vous connecter ou de créer un compte pour booster vos projets entrepreneuriaux.</p>
            </div>

            <h2 class="section-title">Avantages de vous connecter</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">📄</div>
                    <h3 class="benefit-title">Accédez à vos BMCs</h3>
                    <p class="benefit-description">Retrouvez tous vos Business Model Canvases et projets sauvegardés en un seul endroit.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">✏️</div>
                    <h3 class="benefit-title">Modifiez vos projets</h3>
                    <p class="benefit-description">Éditez et gérez vos projets existants facilement et en toute fluidité.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">📊</div>
                    <h3 class="benefit-title">Générez des hypothèses</h3>
                    <p class="benefit-description">Créez des hypothèses et des plans financiers pour faire avancer vos projets.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">⬇️</div>
                    <h3 class="benefit-title">Téléchargez en PDF</h3>
                    <p class="benefit-description">Exportez vos récapitulatifs de projets en PDF pour les partager ou les archiver.</p>
                </div>
            </div>

            <h2 class="section-title">Avantages de créer un compte</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">🚀</div>
                    <h3 class="benefit-title">Démarrez vos projets</h3>
                    <p class="benefit-description">Créez des BMCs et des projets à partir de zéro pour donner vie à vos idées.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">🔒</div>
                    <h3 class="benefit-title">Sauvegarde sécurisée</h3>
                    <p class="benefit-description">Stockez vos projets en toute sécurité et accédez-y à tout moment.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">⚙️</div>
                    <h3 class="benefit-title">Personnalisation</h3>
                    <p class="benefit-description">Ajustez vos paramètres, comme votre photo de profil et vos préférences email.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">👥</div>
                    <h3 class="benefit-title">Rejoignez une communauté</h3>
                    <p class="benefit-description">Connectez-vous avec d'autres entrepreneurs et innovateurs.</p>
                </div>
            </div>

            <div class="cta-section">
                <a href="<?= BASE_URL ?>/views/auth/login.php" class="cta-button cta-primary">Se connecter</a>
                <a href="<?= BASE_URL ?>/views/auth/register.php" class="cta-button cta-secondary">Créer un compte</a>
            </div>
        </div>
    </div>

    <?php include '../layouts/footer.php'; ?>
</body>
</html>