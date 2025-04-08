<?php
session_start();

// Définir le chemin de base pour les inclusions
define('BASE_DIR', dirname(__DIR__, 1));
require_once BASE_DIR . '/includes/db_connect.php';
require_once BASE_DIR . '/includes/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guide et Tutoriel - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
</head>
<body>
    <?php include './layouts/navbar.php'; ?>

    <div class="container my-5">
        <h2 class="text-center text-primary mb-5 fw-bold">Guide et Tutoriel pour Laila Workspace</h2>
        <p class="text-center text-muted mb-5">Bienvenue dans ce guide détaillé pour utiliser Laila Workspace ! Suivez ces étapes pour créer, gérer et optimiser votre Business Model Plan (BMP) de manière efficace.</p>

        <!-- Vidéo explicative -->
        <div class="tutorial-section mb-5">
            <h4 class="text-primary mb-4"><i class="bi bi-play-circle me-2"></i> Vidéo Explicative</h4>
            <div class="ratio ratio-16x9">
                <!-- Remplacer par une vraie vidéo si disponible -->
                <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Tutoriel Laila Workspace" frameborder="0" allowfullscreen></iframe>
            </div>
            <p class="text-muted mt-3">Cette vidéo de 5 minutes vous guide à travers les principales fonctionnalités de Laila Workspace. Vous apprendrez à créer un projet, remplir un Business Model Canvas (BMC), gérer vos hypothèses, analyser vos finances, et générer un appel à action motivant.</p>
            <div class="tip-box">
                <strong>Astuce :</strong> Si vous êtes pressé, utilisez les chapitres de la vidéo pour aller directement à la section qui vous intéresse (par exemple, "Créer un BMC" à 1:30).
            </div>
        </div>

        <!-- Texte explicatif -->
        <div class="guide-section mb-5">
            <h4 class="text-primary mb-4"><i class="bi bi-book me-2"></i> Étapes Détaillées pour Créer un BMP</h4>
            <div class="accordion" id="guideAccordion">
                <!-- Étape 1 : Créer un Projet -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            Étape 1 : Créer un Projet
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <p>La première étape pour utiliser Laila Workspace est de créer un projet. Un projet est l'espace où vous allez organiser toutes les informations liées à votre idée d'entreprise.</p>
                            <div class="step-card">
                                <h6>Instructions :</h6>
                                <ol>
                                    <li>Connectez-vous à votre compte et accédez au <a href="../dashboard.php">tableau de bord</a>.</li>
                                    <li>Cliquez sur le bouton <strong>"Nouveau Projet"</strong> (icône <i class="bi bi-plus-circle"></i>) en haut à droite.</li>
                                    <li>Remplissez les champs suivants :
                                        <ul>
                                            <li><strong>Nom du projet</strong> : Par exemple, "Lancement d'une application de fitness".</li>
                                            <li><strong>Description</strong> : Décrivez brièvement votre idée, par exemple, "Une application mobile pour suivre les entraînements et connecter les utilisateurs à des coachs virtuels."</li>
                                        </ul>
                                    </li>
                                    <li>Cliquez sur <strong>"Créer"</strong> pour enregistrer votre projet.</li>
                                </ol>
                            </div>
                            <div class="screenshot-placeholder">
                                <p><strong>Capture d'écran :</strong> Formulaire de création de projet avec les champs "Nom du projet" et "Description", et un bouton "Créer" en bleu.</p>
                            </div>
                            <div class="tip-box">
                                <strong>Astuce :</strong> Choisissez un nom de projet clair et spécifique pour faciliter la gestion si vous travaillez sur plusieurs idées (ex. "App Fitness 2025" au lieu de "Projet 1").
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Étape 2 : Remplir le Business Model Canvas -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            Étape 2 : Remplir le Business Model Canvas (BMC)
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <p>Le Business Model Canvas (BMC) est un outil stratégique qui vous permet de visualiser les 9 composantes clés de votre modèle d'affaires. Laila Workspace vous aide à le remplir de manière intuitive.</p>
                            <div class="step-card">
                                <h6>Instructions :</h6>
                                <ol>
                                    <li>Depuis le tableau de bord, cliquez sur votre projet pour ouvrir ses détails.</li>
                                    <li>Allez dans la section <strong>"Business Model Canvas"</strong> (accessible via l'onglet <i class="bi bi-grid"></i>).</li>
                                    <li>Vous verrez un canevas interactif avec 9 sections :
                                        <ul>
                                            <li><strong>Segments de clientèle</strong> : Qui sont vos clients ? (Ex. "Jeunes adultes intéressés par le fitness")</li>
                                            <li><strong>Proposition de valeur</strong> : Quelle valeur apportez-vous ? (Ex. "Entraînements personnalisés à la demande")</li>
                                            <li><strong>Canaux</strong> : Comment atteignez-vous vos clients ? (Ex. "Application mobile, réseaux sociaux")</li>
                                            <li><strong>Relations clients</strong> : Quel type de relation entretenez-vous ? (Ex. "Support automatisé via chat")</li>
                                            <li><strong>Sources de revenus</strong> : Comment gagnez-vous de l'argent ? (Ex. "Abonnements mensuels")</li>
                                            <li><strong>Ressources clés</strong> : De quoi avez-vous besoin ? (Ex. "Développeurs, serveurs cloud")</li>
                                            <li><strong>Activités clés</strong> : Quelles sont vos activités principales ? (Ex. "Développement de l'application")</li>
                                            <li><strong>Partenaires clés</strong> : Avec qui travaillez-vous ? (Ex. "Coachs sportifs, fournisseurs de cloud")</li>
                                            <li><strong>Structure des coûts</strong> : Quels sont vos coûts ? (Ex. "Développement, marketing")</li>
                                        </ul>
                                    </li>
                                    <li>Cliquez sur chaque section pour la remplir. Par exemple, dans "Segments de clientèle", entrez "Jeunes adultes intéressés par le fitness".</li>
                                    <li>Enregistrez vos modifications en cliquant sur <strong>"Sauvegarder"</strong>.</li>
                                </ol>
                            </div>
                            <div class="screenshot-placeholder">
                                <p><strong>Capture d'écran :</strong> Interface du BMC avec 9 sections colorées (bleu, vert, etc.), un champ de texte actif pour "Segments de clientèle", et un bouton "Sauvegarder" en bas.</p>
                            </div>
                            <div class="tip-box">
                                <strong>Astuce :</strong> Si vous n'êtes pas sûr de certaines sections, commencez par des hypothèses. Vous pourrez les ajuster plus tard après avoir testé vos idées (voir Étape 3).
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Étape 3 : Gérer les Hypothèses -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            Étape 3 : Gérer les Hypothèses
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <p>Les hypothèses sont des suppositions que vous faites sur votre modèle d'affaires. Laila Workspace vous permet de les générer, de les tester, et de les valider ou infirmer.</p>
                            <div class="step-card">
                                <h6>Instructions :</h6>
                                <ol>
                                    <li>Dans votre projet, allez dans la section <strong>"Hypothèses"</strong> (icône <i class="bi bi-lightbulb"></i>).</li>
                                    <li>Cliquez sur <strong>"Générer des Hypothèses"</strong> pour laisser l'IA proposer des hypothèses basées sur votre BMC. Par exemple :
                                        <ul>
                                            <li>"Les jeunes adultes sont prêts à payer 10€/mois pour des entraînements personnalisés."</li>
                                            <li>"Les réseaux sociaux seront le canal le plus efficace pour attirer des clients."</li>
                                        </ul>
                                    </li>
                                    <li>Vous pouvez aussi ajouter manuellement une hypothèse en cliquant sur <strong>"Ajouter une Hypothèse"</strong>.</li>
                                    <li>Pour chaque hypothèse, définissez un plan de test :
                                        <ul>
                                            <li><strong>Méthode de test</strong> : Par exemple, "Lancer une campagne publicitaire sur Instagram et mesurer le taux de conversion."</li>
                                            <li><strong>Critère de validation</strong> : Par exemple, "Obtenir au moins 100 inscriptions en 1 mois."</li>
                                        </ul>
                                    </li>
                                    <li>Après avoir effectué le test, marquez l'hypothèse comme <strong>"Confirmée"</strong> ou <strong>"Infirmée"</strong>.</li>
                                    <li>Si une hypothèse est infirmée, ajustez votre BMC. Par exemple, si les réseaux sociaux ne fonctionnent pas, essayez un autre canal comme des partenariats avec des salles de sport.</li>
                                </ol>
                            </div>
                            <div class="screenshot-placeholder">
                                <p><strong>Capture d'écran :</strong> Liste des hypothèses avec des colonnes "Hypothèse", "Statut", "Plan de Test", et des boutons "Confirmer" et "Infirmer".</p>
                            </div>
                            <div class="tip-box">
                                <strong>Astuce :</strong> Priorisez les hypothèses les plus risquées (celles qui, si elles sont fausses, pourraient faire échouer votre projet). Testez-les en premier pour minimiser les risques.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Étape 4 : Plan Financier -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            Étape 4 : Élaborer le Plan Financier
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <p>Le plan financier vous permet d'estimer vos revenus, coûts, et rentabilité. Laila Workspace simplifie ce processus avec des outils de calcul et de visualisation.</p>
                            <div class="step-card">
                                <h6>Instructions :</h6>
                                <ol>
                                    <li>Dans votre projet, allez dans la section <strong>"Plan Financier"</strong> (icône <i class="bi bi-calculator"></i>).</li>
                                    <li>Remplissez les champs suivants :
                                        <ul>
                                            <li><strong>Revenus mensuels estimés</strong> : Par exemple, "5000€" (basé sur 500 abonnés à 10€/mois).</li>
                                            <li><strong>Coûts fixes mensuels</strong> : Par exemple, "1000€" (serveurs, licences).</li>
                                            <li><strong>Coûts variables mensuels</strong> : Par exemple, "500€" (publicité).</li>
                                            <li><strong>Prix de vente unitaire</strong> : Par exemple, "10€" (prix de l'abonnement).</li>
                                            <li><strong>Coût variable unitaire</strong> : Par exemple, "2€" (frais de transaction par abonnement).</li>
                                        </ul>
                                    </li>
                                    <li>Cliquez sur <strong>"Générer les Prévisions"</strong> pour voir une projection sur 12 mois. Vous obtiendrez :
                                        <ul>
                                            <li>Un graphique des revenus et coûts.</li>
                                            <li>Un tableau avec le bénéfice mensuel.</li>
                                            <li>Un seuil de rentabilité (break-even point).</li>
                                        </ul>
                                    </li>
                                    <li>Si les prévisions ne sont pas satisfaisantes, ajustez vos données (par exemple, augmentez le prix de vente ou réduisez les coûts).</li>
                                    <li>Téléchargez un rapport PDF en cliquant sur <strong>"Exporter en PDF"</strong>.</li>
                                </ol>
                            </div>
                            <div class="screenshot-placeholder">
                                <p><strong>Capture d'écran :</strong> Formulaire du plan financier avec des champs pour les revenus, coûts, et un graphique de prévision montrant une courbe ascendante des bénéfices.</p>
                            </div>
                            <div class="tip-box">
                                <strong>Astuce :</strong> Soyez conservateur dans vos estimations de revenus et généreux dans vos estimations de coûts pour éviter les surprises. Par exemple, prévoyez une marge de 20% pour les imprévus.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Étape 5 : Récapitulatif et Appel à Action -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFive">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                            Étape 5 : Consulter le Récapitulatif et Générer un Appel à Action
                        </button>
                    </h2>
                    <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <p>Une fois que vous avez complété les étapes précédentes, Laila Workspace vous fournit un récapitulatif complet de votre BMP. Vous pouvez également générer un appel à action motivant pour passer à l'étape suivante.</p>
                            <div class="step-card">
                                <h6>Instructions :</h6>
                                <ol>
                                    <li>Dans votre projet, allez dans la section <strong>"Récapitulatif"</strong> (icône <i class="bi bi-file-earmark-text"></i>).</li>
                                    <li>Vous verrez un résumé de votre BMP, incluant :
                                        <ul>
                                            <li>La description de votre projet.</li>
                                            <li>Votre Business Model Canvas complété.</li>
                                            <li>Les hypothèses validées.</li>
                                            <li>Les données financières et prévisions.</li>
                                        </ul>
                                    </li>
                                    <li>Exportez le récapitulatif en PDF en cliquant sur <strong>"Exporter en PDF"</strong>. Ce document est idéal pour partager avec des investisseurs ou des partenaires.</li>
                                    <li>Pour motiver vos prochaines étapes, cliquez sur <strong>"Générer un Appel à Action"</strong>. L'IA créera un message percutant basé sur votre projet. Par exemple :
                                        <ul>
                                            <li>"Lancez votre application de fitness dès aujourd'hui et atteignez 500 abonnés en 3 mois !"</li>
                                        </ul>
                                    </li>
                                    <li>Utilisez cet appel à action dans vos présentations ou pour vous motiver à passer à l'action.</li>
                                </ol>
                            </div>
                            <div class="screenshot-placeholder">
                                <p><strong>Capture d'écran :</strong> Page de récapitulatif avec un aperçu du BMC, une liste d'hypothèses validées, un tableau financier, et un appel à action en surbrillance ("Lancez votre projet maintenant !").</p>
                            </div>
                            <div class="tip-box">
                                <strong>Astuce :</strong> Si l'appel à action généré ne vous convient pas, vous pouvez le régénérer en cliquant à nouveau sur le bouton. Essayez d'ajuster la description de votre projet pour obtenir un message plus pertinent.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ -->
        <div class="faq-section mb-5">
            <h4 class="text-primary mb-4"><i class="bi bi-question-circle me-2"></i> Foire Aux Questions (FAQ)</h4>
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseOne" aria-expanded="true" aria-controls="faqCollapseOne">
                            Comment puis-je modifier mon BMC après l'avoir créé ?
                        </button>
                    </h2>
                    <div id="faqCollapseOne" class="accordion-collapse collapse show" aria-labelledby="faqOne" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>Vous pouvez modifier votre BMC à tout moment en suivant ces étapes :</p>
                            <ol>
                                <li>Allez dans votre projet via le tableau de bord.</li>
                                <li>Cliquez sur l'onglet <strong>"Business Model Canvas"</strong>.</li>
                                <li>Cliquez sur la section que vous souhaitez modifier (par exemple, "Segments de clientèle").</li>
                                <li>Mettez à jour le contenu et cliquez sur <strong>"Sauvegarder"</strong>.</li>
                            </ol>
                            <p>Les modifications sont enregistrées automatiquement et mises à jour dans votre récapitulatif.</p>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseTwo" aria-expanded="false" aria-controls="faqCollapseTwo">
                            Que faire si une hypothèse est infirmée ?
                        </button>
                    </h2>
                    <div id="faqCollapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>Si une hypothèse est infirmée, cela signifie que votre supposition initiale était incorrecte. Voici comment procéder :</p>
                            <ol>
                                <li>Marquez l'hypothèse comme <strong>"Infirmée"</strong> dans la section "Hypothèses".</li>
                                <li>Analysez les résultats de votre test pour comprendre pourquoi l'hypothèse était fausse. Par exemple, si vous pensiez que "les réseaux sociaux attireront 100 clients en 1 mois" mais que vous n'en avez eu que 10, peut-être que votre audience cible n'est pas active sur ces plateformes.</li>
                                <li>Ajustez votre BMC en conséquence. Par exemple, explorez d'autres canaux comme des partenariats ou des événements locaux.</li>
                                <li>Générez une nouvelle hypothèse pour tester votre ajustement, par exemple, "Les partenariats avec des salles de sport attireront 50 clients en 1 mois."</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseThree" aria-expanded="false" aria-controls="faqCollapseThree">
                            Comment générer des prévisions financières ?
                        </button>
                    </h2>
                    <div id="faqCollapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>Pour générer des prévisions financières dans Laila Workspace :</p>
                            <ol>
                                <li>Allez dans la section <strong>"Plan Financier"</strong> de votre projet.</li>
                                <li>Remplissez les champs requis (revenus mensuels estimés, coûts fixes, coûts variables, prix de vente unitaire, coût variable unitaire).</li>
                                <li>Cliquez sur <strong>"Générer les Prévisions Financières"</strong>.</li>
                                <li>Vous verrez un graphique et un tableau montrant :
                                    <ul>
                                        <li>Les revenus et coûts projetés sur 12 mois.</li>
                                        <li>Le bénéfice mensuel.</li>
                                        <li>Le seuil de rentabilité (nombre d'unités à vendre pour couvrir les coûts).</li>
                                    </ul>
                                </li>
                                <li>Si les prévisions ne sont pas satisfaisantes, ajustez vos données et régénérez les prévisions.</li>
                            </ol>
                            <p>Vous pouvez également télécharger un rapport PDF de vos prévisions pour le partager avec des partenaires ou investisseurs.</p>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseFour" aria-expanded="false" aria-controls="faqCollapseFour">
                            Comment utiliser l'appel à action généré ?
                        </button>
                    </h2>
                    <div id="faqCollapseFour" class="accordion-collapse collapse" aria-labelledby="faqFour" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>L'appel à action généré par Laila Workspace est un message motivant conçu pour vous pousser à agir ou pour inspirer vos parties prenantes (investisseurs, partenaires, etc.). Voici comment l'utiliser :</p>
                            <ol>
                                <li>Après avoir généré l'appel à action dans la section "Récapitulatif", notez-le ou copiez-le.</li>
                                <li>Incluez-le dans vos présentations ou pitchs. Par exemple, ajoutez-le à la fin d'une diapositive : "Lancez votre projet dès aujourd'hui et atteignez vos objectifs en 3 mois !"</li>
                                <li>Utilisez-le comme slogan pour motiver votre équipe ou vous-même.</li>
                                <li>Si vous exportez votre BMP en PDF, l'appel à action sera inclus dans le document, ce qui le rend idéal pour partager avec des investisseurs.</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqFive">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseFive" aria-expanded="false" aria-controls="faqCollapseFive">
                            Puis-je collaborer avec mon équipe sur un projet ?
                        </button>
                    </h2>
                    <div id="faqCollapseFive" class="accordion-collapse collapse" aria-labelledby="faqFive" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>Actuellement, Laila Workspace ne prend pas en charge la collaboration en temps réel. Cependant, vous pouvez partager votre projet avec votre équipe en suivant ces étapes :</p>
                            <ol>
                                <li>Exportez votre BMP en PDF depuis la section "Récapitulatif".</li>
                                <li>Partagez le PDF avec votre équipe via email ou une plateforme de collaboration (comme Google Drive ou Slack).</li>
                                <li>Pour collaborer sur des modifications, désignez une personne pour mettre à jour le projet dans Laila Workspace, puis partagez à nouveau le PDF mis à jour.</li>
                            </ol>
                            <p><strong>Note :</strong> Une fonctionnalité de collaboration en temps réel pourrait être ajoutée dans une future mise à jour. Restez à l'écoute !</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ressources supplémentaires -->
        <div class="resources-section mb-5">
            <h4 class="text-primary mb-4"><i class="bi bi-link-45deg me-2"></i> Ressources Supplémentaires</h4>
            <ul class="list-group">
                <li class="list-group-item">
                    <a href="https://www.strategyzer.com/canvas/business-model-canvas" target="_blank" class="text-primary">Guide Officiel du Business Model Canvas</a>
                    <p class="text-muted mb-0">Apprenez-en plus sur le BMC et ses 9 composantes avec ce guide officiel de Strategyzer.</p>
                </li>
                <li class="list-group-item">
                    <a href="https://www.leanstack.com/lean-canvas" target="_blank" class="text-primary">Introduction au Lean Canvas</a>
                    <p class="text-muted mb-0">Découvrez une alternative au BMC, plus axée sur les startups et les projets agiles.</p>
                </li>
                <li class="list-group-item">
                    <a href="mailto:support@laila-workspace.com" class="text-primary">Contacter le Support</a>
                    <p class="text-muted mb-0">Si vous avez des questions ou rencontrez des problèmes, contactez notre équipe de support à support@laila-workspace.com.</p>
                </li>
            </ul>
        </div>
    </div>

    <?php include './layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>