<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';

// Configuration pour le template de base
$page_title = 'Accueil - Laila Workspace';

// CSS spécifique à la page d'accueil
$additional_css = '
<style>
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #00c4b4 100%);
        color: #fff;
        padding: 5rem 0 3rem 0;
        position: relative;
        overflow: hidden;
    }
    .hero-section::before {
        content: \'\';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: url(\'https://www.transparenttextures.com/patterns/cubes.png\');
        opacity: 0.08;
        z-index: 0;
    }
    .hero-content {
        position: relative;
        z-index: 1;
    }
    .hero-title {
        font-size: 2.8rem;
        font-weight: 800;
        margin-bottom: 1rem;
    }
    .hero-subtitle {
        font-size: 1.3rem;
        margin-bottom: 2.2rem;
        opacity: 0.95;
    }
    .hero-btn {
        font-size: 1.15rem;
        padding: 1rem 2.5rem;
        border-radius: 6px;
        box-shadow: 0 6px 24px rgba(0,123,255,0.13);
        font-weight: 700;
        transition: background 0.3s, transform 0.2s;
    }
    .hero-btn:hover {
        background: linear-gradient(135deg, #0056b3, #009a8e);
        transform: translateY(-2px) scale(1.04);
    }
    .presentation-section .card {
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.07);
        transition: transform 0.2s, box-shadow 0.2s;
        border: none;
    }
    .presentation-section .card:hover {
        transform: translateY(-6px) scale(1.03);
        box-shadow: 0 8px 32px rgba(0,0,0,0.13);
    }
    .presentation-section .bi {
        font-size: 2.7rem;
        margin-bottom: 1rem;
        color: #00c4b4;
    }
    .team-card {
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.07);
        transition: transform 0.2s, box-shadow 0.2s;
        border: none;
        background: #fff;
    }
    .team-card:hover {
        transform: translateY(-6px) scale(1.03);
        box-shadow: 0 8px 32px rgba(0,0,0,0.13);
    }
    .user-avatar {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #007bff, #00c4b4);
        color: #fff;
        font-weight: 700;
        font-size: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .linkedin-link {
        color: #0077b5;
        font-size: 1.3rem;
        margin-left: 0.5rem;
    }
    .testimonials-section {
        background: #f8fafc;
        padding: 3rem 0 2rem 0;
    }
    .testimonial-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.07);
        padding: 2rem 1.5rem;
        text-align: center;
        margin-bottom: 1.5rem;
    }
    .testimonial-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 1rem;
    }
    @media (max-width: 576px) {
        .hero-title { font-size: 2rem; }
        .hero-section { padding: 2.5rem 0 1.5rem 0; }
    }
</style>
';

// Contenu de la page
ob_start();
?>
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #00c4b4 100%);
            color: #fff;
            padding: 5rem 0 3rem 0;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/cubes.png');
            opacity: 0.08;
            z-index: 0;
        }
        .hero-content {
            position: relative;
            z-index: 1;
        }
        .hero-title {
            font-size: 2.8rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2.2rem;
            opacity: 0.95;
        }
        .hero-btn {
            font-size: 1.15rem;
            padding: 1rem 2.5rem;
            border-radius: 6px;
            box-shadow: 0 6px 24px rgba(0,123,255,0.13);
            font-weight: 700;
            transition: background 0.3s, transform 0.2s;
        }
        .hero-btn:hover {
            background: linear-gradient(135deg, #0056b3, #009a8e);
            transform: translateY(-2px) scale(1.04);
        }
        .presentation-section .card {
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.07);
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
        }
        .presentation-section .card:hover {
            transform: translateY(-6px) scale(1.03);
            box-shadow: 0 8px 32px rgba(0,0,0,0.13);
        }
        .presentation-section .bi {
            font-size: 2.7rem;
            margin-bottom: 1rem;
            color: #00c4b4;
        }
        .team-card {
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.07);
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            background: #fff;
        }
        .team-card:hover {
            transform: translateY(-6px) scale(1.03);
            box-shadow: 0 8px 32px rgba(0,0,0,0.13);
        }
        .user-avatar {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #007bff, #00c4b4);
            color: #fff;
            font-weight: 700;
            font-size: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .linkedin-link {
            color: #0077b5;
            font-size: 1.3rem;
            margin-left: 0.5rem;
        }
        .testimonials-section {
            background: #f8fafc;
            padding: 3rem 0 2rem 0;
        }
        .testimonial-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.07);
            padding: 2rem 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .testimonial-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
        }
        @media (max-width: 576px) {
            .hero-title { font-size: 2rem; }
            .hero-section { padding: 2.5rem 0 1.5rem 0; }
        }
    </style>
</head>
<body>
    <!-- Section Hero -->
    <div class="hero-section">
        <div class="container text-center hero-content">
            <h1 class="hero-title">
                <i class="bi bi-rocket-takeoff-fill me-2"></i>Laila Workspace
            </h1>
            <div class="hero-subtitle">
                L’outil intelligent pour transformer vos idées en business model, valider vos hypothèses et piloter vos projets comme un pro.
            </div>
            <a href="<?= BASE_URL ?>/views/bmc/generate_bmc.php" class="btn btn-primary hero-btn">
                <i class="bi bi-rocket-takeoff me-3"></i>Commencer maintenant
            </a>
        </div>
    </div>

    <!-- Section Présentation -->
    <div class="container my-5 presentation-section">
        <h2 class="text-center text-primary mb-5 fw-bold">Pourquoi Laila Workspace ?</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card text-center p-4 h-100">
                    <i class="bi bi-grid"></i>
                    <h5 class="fw-semibold">Génération de BMP</h5>
                    <p class="text-muted">Créez un Business Model Canvas en quelques clics grâce à notre IA avancée.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center p-4 h-100">
                    <i class="bi bi-lightbulb"></i>
                    <h5 class="fw-semibold">Gestion des hypothèses</h5>
                    <p class="text-muted">Suivez et validez vos hypothèses pour affiner votre stratégie.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center p-4 h-100">
                    <i class="bi bi-bar-chart"></i>
                    <h5 class="fw-semibold">Analyse et insights</h5>
                    <p class="text-muted">Obtenez des analyses pour prendre des décisions éclairées.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Témoignages -->
    <div class="testimonials-section">
        <div class="container">
            <h2 class="text-center text-primary mb-5 fw-bold">Ils utilisent Laila Workspace</h2>
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" class="testimonial-avatar" alt="Utilisateur 1">
                        <p class="mb-2">« Laila Workspace m’a permis de structurer mon projet en un temps record. L’IA est bluffante ! »</p>
                        <div class="fw-bold">Julien M.</div>
                        <div class="text-muted small">Entrepreneur</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" class="testimonial-avatar" alt="Utilisateur 2">
                        <p class="mb-2">« L’interface est moderne, intuitive et l’accompagnement IA fait vraiment la différence. »</p>
                        <div class="fw-bold">Sophie L.</div>
                        <div class="text-muted small">Consultante</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Notre Équipe -->
    <div class="container my-5">
        <h2 class="text-center text-primary mb-5 fw-bold">Notre Équipe</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="team-card text-center p-4 h-100">
                    <div class="user-avatar mb-3 mx-auto">DN</div>
                    <h6 class="fw-semibold">David Ndizeye
                        <a href="#" class="linkedin-link" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    </h6>
                    <p class="text-muted small mb-2">Fondateur de la Startup</p>
                    <p class="text-muted">
                        David est le visionnaire derrière Laila Workspace, passionné par l'innovation et l'entrepreneuriat.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="team-card text-center p-4 h-100">
                    <div class="user-avatar mb-3 mx-auto">GL</div>
                    <h6 class="fw-semibold">Gad Lelo
                        <a href="#" class="linkedin-link" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    </h6>
                    <p class="text-muted small mb-2">Développeur</p>
                    <p class="text-muted">
                        Gad est un développeur talentueux qui a contribué à la création de l'interface intuitive de Laila Workspace.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="team-card text-center p-4 h-100">
                    <div class="user-avatar mb-3 mx-auto">ED</div>
                    <h6 class="fw-semibold">Eliel D.
                        <a href="#" class="linkedin-link" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    </h6>
                    <p class="text-muted small mb-2">Développeur</p>
                    <p class="text-muted">
                        Eliel a travaillé sur les fonctionnalités avancées de l'IA pour rendre Laila Workspace plus intelligent.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include '../views/layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>