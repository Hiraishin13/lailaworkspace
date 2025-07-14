<?php
session_start();
// Définir le chemin de base pour les inclusions
define('BASE_DIR', dirname(__DIR__, 2)); // Remonte de views/pages/ à la racine du projet (laila_workspace)
require_once BASE_DIR . '/includes/db_connect.php'; // Inclure db_connect.php au lieu de config.php
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conditions Générales d'Utilisation - Laila Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
</head>
<body>
    <?php include '../layouts/navbar.php'; ?>

    <div class="container my-5">
        <h2 class="text-center text-primary mb-5 fw-bold">Conditions Générales d'Utilisation</h2>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card p-4 shadow-sm">
                    <h3 class="text-primary fw-bold mb-4">1. Introduction</h3>
                    <p class="text-muted">
                        Bienvenue sur Laila Workspace, une plateforme innovante conçue pour aider les entrepreneurs, les startups et les professionnels à développer leurs idées de projets grâce à des outils avancés de création de Business Model Canvas (BMC), de planification financière, et bien plus encore. Les présentes Conditions Générales d'Utilisation (ci-après dénommées "CGU") régissent l’ensemble de votre utilisation de notre plateforme, y compris, mais sans s’y limiter, l’accès à nos services, l’utilisation de nos fonctionnalités, et la création de contenu via notre interface. En vous inscrivant sur Laila Workspace, en accédant à notre site, ou en utilisant l’un de nos services, vous acceptez expressément et sans réserve de vous conformer à ces CGU ainsi qu’à toutes les lois et réglementations applicables dans votre juridiction. Si, pour une raison quelconque, vous n’êtes pas d’accord avec une ou plusieurs clauses de ces CGU, nous vous prions de ne pas utiliser notre plateforme ni aucun de ses services associés. L’utilisation continue de Laila Workspace après la publication de modifications apportées aux présentes CGU sera considérée comme une acceptation de ces modifications. Nous vous recommandons vivement de lire attentivement chaque section de ce document afin de bien comprendre vos droits et obligations en tant qu’utilisateur de notre plateforme.
                    </p>

                    <h3 class="text-primary fw-bold mb-4">2. Définitions</h3>
                    <p class="text-muted">
                        Pour une meilleure compréhension des présentes CGU, les termes suivants sont définis comme suit : <br>
                        - <strong>"Plateforme"</strong> : Désigne Laila Workspace, accessible via l’URL officielle (<?= BASE_URL ?>) ou via toute autre interface mise à disposition par nos soins, y compris les applications mobiles, les API, ou les intégrations tierces. <br>
                        - <strong>"Utilisateur"</strong> : Toute personne physique ou morale qui accède à la Plateforme, crée un compte, ou utilise nos services, qu’elle soit un utilisateur non inscrit, un utilisateur inscrit, ou un administrateur. <br>
                        - <strong>"Compte"</strong> : L’espace personnel créé par un Utilisateur lors de son inscription sur la Plateforme, qui lui permet d’accéder à des fonctionnalités spécifiques et de gérer ses projets. <br>
                        - <strong>"Contenu"</strong> : Tout élément, y compris mais sans s’y limiter, les textes, images, vidéos, données, Business Model Canvas (BMC), plans financiers, hypothèses, ou tout autre document généré ou téléchargé par un Utilisateur sur la Plateforme. <br>
                        - <strong>"Services"</strong> : Ensemble des fonctionnalités et outils fournis par Laila Workspace, tels que la génération de BMC, l’analyse de données, la création de plans financiers, et les fonctionnalités collaboratives. <br>
                        - <strong>"Nous", "Notre", "Laila Workspace"</strong> : Désigne l’entité juridique qui exploite la Plateforme, ainsi que ses filiales, partenaires, et représentants légaux. <br>
                        Ces définitions s’appliquent à l’ensemble des CGU, sauf disposition contraire expressément mentionnée dans une section spécifique.
                    </p>

                    <h3 class="text-primary fw-bold mb-4">3. Inscription et compte utilisateur</h3>
                    <p class="text-muted">
                        3.1. <strong>Création de compte</strong> : Pour accéder à certaines fonctionnalités avancées de Laila Workspace, telles que la génération de Business Model Canvas, la sauvegarde de projets, ou l’accès à des outils d’analyse approfondie, il est requis de créer un compte utilisateur. Lors de l’inscription, vous devrez fournir des informations personnelles telles que votre nom, prénom, adresse email, numéro de téléphone, et un mot de passe sécurisé. Vous vous engagez à fournir des informations exactes, complètes et à jour. Toute information inexacte ou frauduleuse peut entraîner la suspension ou la résiliation immédiate de votre compte sans préavis. <br><br>
                        3.2. <strong>Sécurité du compte</strong> : Vous êtes entièrement responsable de la confidentialité de vos identifiants de connexion, y compris votre adresse email et votre mot de passe. Vous acceptez de ne pas partager vos identifiants avec des tiers et de nous informer immédiatement en cas d’utilisation non autorisée de votre compte ou de toute autre violation de sécurité. Laila Workspace ne saurait être tenu responsable des pertes ou dommages résultant de votre incapacité à protéger vos identifiants. Nous vous recommandons d’utiliser un mot de passe fort, composé d’au moins 8 caractères, incluant des lettres majuscules, minuscules, chiffres et caractères spéciaux, et de le modifier régulièrement. <br><br>
                        3.3. <strong>Âge minimum</strong> : L’utilisation de la Plateforme est réservée aux personnes âgées d’au moins 18 ans, ou à celles ayant atteint l’âge de la majorité légale dans leur juridiction. En créant un compte, vous déclarez et garantissez que vous remplissez ces conditions d’âge. Si nous constatons qu’un utilisateur est mineur, nous nous réservons le droit de suspendre ou de supprimer son compte sans préavis. <br><br>
                        3.4. <strong>Compte unique</strong> : Chaque utilisateur est limité à la création d’un seul compte. La création de plusieurs comptes par une même personne est strictement interdite et peut entraîner la suspension de tous les comptes associés. Si vous avez besoin d’un compte supplémentaire pour une entité distincte (par exemple, une entreprise), veuillez contacter notre support à l’adresse support@lailaworkspace.com pour une autorisation préalable.
                    </p>

                    <h3 class="text-primary fw-bold mb-4">4. Utilisation de la plateforme</h3>
                    <p class="text-muted">
                        4.1. <strong>Objet de la plateforme</strong> : Laila Workspace est conçu pour fournir des outils avancés aux entrepreneurs, aux startups, et aux professionnels afin de structurer leurs idées commerciales à travers la création de Business Model Canvas, de plans financiers, et d’hypothèses stratégiques. Vous pouvez utiliser la Plateforme pour générer, sauvegarder, modifier, et partager vos projets, sous réserve de respecter les présentes CGU. <br><br>
                        4.2. <strong>Comportement interdit</strong> : En utilisant Laila Workspace, vous vous engagez à adopter un comportement respectueux et légal. Les comportements suivants sont strictement interdits : <br>
                        - Publier, partager ou transmettre tout contenu illégal, offensant, diffamatoire, pornographique, discriminatoire, ou autrement inapproprié, y compris, mais sans s’y limiter, des contenus incitant à la haine, à la violence, ou à la discrimination fondée sur la race, le genre, la religion, ou toute autre caractéristique protégée. <br>
                        - Utiliser la Plateforme pour des activités frauduleuses, telles que l’usurpation d’identité, le phishing, ou toute tentative d’escroquerie. <br>
                        - Tenter d’accéder à des données ou fonctionnalités non autorisées, y compris, mais sans s’y limiter, le piratage, l’exploitation de failles de sécurité, ou l’utilisation de bots ou scripts automatisés pour extraire des données. <br>
                        - Interférer avec le fonctionnement normal de la Plateforme, par exemple en surchargeant les serveurs, en envoyant des spams, ou en propageant des virus ou logiciels malveillants. <br>
                        - Reproduire, copier, ou distribuer tout contenu ou fonctionnalité de la Plateforme sans autorisation préalable écrite de Laila Workspace. <br>
                        Toute violation de ces règles peut entraîner la suspension ou la résiliation immédiate de votre compte, ainsi que des poursuites judiciaires si nécessaire. <br><br>
                        4.3. <strong>Utilisation responsable</strong> : Vous vous engagez à utiliser la Plateforme de manière responsable et à respecter les droits des autres utilisateurs. Cela inclut, mais ne se limite pas à, le respect des droits d’auteur et des marques déposées lors du téléchargement ou du partage de contenu sur la Plateforme. Vous êtes seul responsable de l’exactitude et de la légalité de tout contenu que vous soumettez ou générez via la Plateforme. <br><br>
                        4.4. <strong>Surveillance et modération</strong> : Laila Workspace se réserve le droit, mais n’a pas l’obligation, de surveiller les activités et le contenu publié sur la Plateforme. Nous pouvons, à notre seule discrétion, supprimer ou modifier tout contenu que nous jugeons inapproprié ou non conforme à ces CGU, sans préavis ni responsabilité envers vous.
                    </p>

                    <h3 class="text-primary fw-bold mb-4">5. Propriété intellectuelle</h3>
                    <p class="text-muted">
                        5.1. <strong>Contenu généré par l’utilisateur</strong> : Tout contenu que vous créez ou téléchargez sur la Plateforme, y compris les Business Model Canvas, les plans financiers, les hypothèses, ou tout autre document, reste votre propriété exclusive. En soumettant ce contenu, vous accordez toutefois à Laila Workspace une licence mondiale, non exclusive, gratuite, irrévocable, et transférable pour utiliser, reproduire, modifier, distribuer, afficher, et stocker ce contenu dans le cadre de la fourniture de nos services, y compris, mais sans s’y limiter, pour l’hébergement, la sauvegarde, et l’amélioration de la Plateforme. Cette licence prend fin lorsque vous supprimez votre contenu de la Plateforme, sauf si ce contenu a été partagé avec d’autres utilisateurs ou intégré dans des fonctionnalités collaboratives. <br><br>
                        5.2. <strong>Propriété de Laila Workspace</strong> : L’ensemble des éléments constitutifs de la Plateforme, y compris, mais sans s’y limiter, le design, les logos, les icônes, les interfaces utilisateur, le code source, les algorithmes, les bases de données, et tout autre élément protégé par des droits de propriété intellectuelle, sont la propriété exclusive de Laila Workspace ou de ses concédants de licence. Toute reproduction, modification, distribution, ou exploitation de ces éléments sans autorisation préalable écrite est strictement interdite et peut entraîner des poursuites judiciaires. <br><br>
                        5.3. <strong>Marques déposées</strong> : "Laila Workspace", ainsi que tous les logos, slogans, et marques associés, sont des marques déposées appartenant à Laila Workspace. Vous n’êtes pas autorisé à utiliser ces marques sans notre consentement préalable écrit. Toute utilisation non autorisée peut entraîner des sanctions civiles et pénales. <br><br>
                        5.4. <strong>Contenu tiers</strong> : Certains contenus ou fonctionnalités de la Plateforme peuvent inclure des éléments fournis par des tiers, tels que des bibliothèques open source, des API externes, ou des contenus sous licence Creative Commons. Ces éléments sont soumis à leurs propres conditions d’utilisation, et vous vous engagez à les respecter lors de votre utilisation de la Plateforme.
                    </p>

                    <h3 class="text-primary fw-bold mb-4">6. Limitation de responsabilité</h3>
                    <p class="text-muted">
                        6.1. <strong>Service "tel quel"</strong> : Laila Workspace est fourni "tel quel" et "selon disponibilité". Nous ne garantissons pas que la Plateforme sera exempte d’erreurs, de bugs, ou d’interruptions, ni que les services seront disponibles à tout moment. Nous déclinons toute garantie, explicite ou implicite, y compris, mais sans s’y limiter, les garanties de qualité marchande, d’adéquation à un usage particulier, ou de non-contrefaçon. Vous utilisez la Plateforme à vos propres risques, et vous êtes seul responsable de tout dommage ou perte de données résultant de cette utilisation. <br><br>
                        6.2. <strong>Responsabilité limitée</strong> : En aucun cas, Laila Workspace, ses dirigeants, employés, partenaires, ou affiliés ne pourront être tenus responsables des dommages directs, indirects, accessoires, spéciaux, consécutifs, ou punitifs, y compris, mais sans s’y limiter, la perte de profits, la perte de données, ou l’interruption d’activité, résultant de l’utilisation ou de l’incapacité à utiliser la Plateforme, même si nous avons été informés de la possibilité de tels dommages. <br><br>
                        6.3. <strong>Force majeure</strong> : Laila Workspace ne saurait être tenu responsable des retards, interruptions, ou défaillances dans la fourniture des services dus à des événements indépendants de notre volonté, y compris, mais sans s’y limiter, les catastrophes naturelles, les pannes de réseau, les cyberattaques, les grèves, les guerres, les actes de terrorisme, ou les restrictions gouvernementales. <br><br>
                        6.4. <strong>Contenu généré par l’IA</strong> : Certaines fonctionnalités de la Plateforme, telles que la génération de Business Model Canvas ou d’hypothèses, peuvent s’appuyer sur des technologies d’intelligence artificielle (IA). Bien que nous nous efforcions de fournir des résultats précis et utiles, nous ne garantissons pas l’exactitude, la complétude, ou la pertinence des contenus générés par l’IA. Vous êtes seul responsable de la vérification et de l’utilisation de ces contenus à des fins professionnelles ou personnelles.
                    </p>

                    <h3 class="text-primary fw-bold mb-4">7. Résiliation et suspension</h3>
                    <p class="text-muted">
                        7.1. <strong>Résiliation par l’utilisateur</strong> : Vous pouvez résilier votre compte à tout moment en accédant aux paramètres de votre compte et en sélectionnant l’option de suppression. La résiliation prendra effet immédiatement, et toutes vos données associées seront supprimées conformément à notre politique de confidentialité, sauf si des obligations légales nous imposent de conserver certaines informations. <br><br>
                        7.2. <strong>Résiliation par Laila Workspace</strong> : Nous nous réservons le droit de suspendre ou de résilier votre compte, avec ou sans préavis, en cas de violation des présentes CGU, d’activités frauduleuses, ou pour toute autre raison jugée appropriée à notre seule discrétion. En cas de résiliation, vous perdrez l’accès à votre compte et à toutes les données associées, et nous ne serons pas tenus de vous rembourser ou de vous indemniser pour cette perte. <br><br>
                        7.3. <strong>Effets de la résiliation</strong> : En cas de résiliation, toutes les licences et droits d’utilisation accordés par les présentes CGU prendront fin immédiatement. Vous devrez cesser d’utiliser la Plateforme et supprimer toute copie des contenus ou éléments de la Plateforme en votre possession. Les sections des CGU qui, par leur nature, doivent survivre à la résiliation (comme la propriété intellectuelle, la limitation de responsabilité, et la juridiction applicable) resteront en vigueur.
                    </p>

                    <h3 class="text-primary fw-bold mb-4">8. Modification des CGU</h3>
                    <p class="text-muted">
                        8.1. <strong>Droit de modification</strong> : Laila Workspace se réserve le droit de modifier ou de mettre à jour les présentes CGU à tout moment, à sa seule discrétion, afin de refléter les évolutions de nos services, les changements légaux ou réglementaires, ou pour toute autre raison jugée nécessaire. Les modifications seront publiées sur cette page, et la date de la dernière mise à jour sera indiquée en haut du document. <br><br>
                        8.2. <strong>Notification des modifications</strong> : Nous nous engageons à informer les utilisateurs des modifications importantes apportées aux CGU, soit par email, soit par une notification visible sur la Plateforme. Toutefois, il vous incombe de consulter régulièrement cette page pour prendre connaissance des éventuelles mises à jour. Votre utilisation continue de la Plateforme après la publication des modifications constitue une acceptation de ces nouvelles conditions. Si vous n’acceptez pas les modifications, vous devez cesser d’utiliser la Plateforme et résilier votre compte conformément à la section 7. <br><br>
                        8.3. <strong>Historique des versions</strong> : À des fins de transparence, nous pouvons conserver un historique des versions précédentes des CGU, accessible sur demande. Toutefois, la version actuellement en ligne est la seule version juridiquement contraignante à tout moment.
                    </p>

                    <h3 class="text-primary fw-bold mb-4">9. Confidentialité et protection des données</h3>
                    <p class="text-muted">
                        9.1. <strong>Politique de confidentialité</strong> : Votre utilisation de la Plateforme est également soumise à notre Politique de Confidentialité, qui explique comment nous collectons, utilisons, stockons, et protégeons vos données personnelles. En acceptant ces CGU, vous acceptez également les termes de notre Politique de Confidentialité, accessible à l’adresse [insérer lien vers la politique de confidentialité]. <br><br>
                        9.2. <strong>Collecte de données</strong> : Nous collectons diverses informations vous concernant lorsque vous utilisez la Plateforme, y compris, mais sans s’y limiter, votre nom, adresse email, numéro de téléphone, adresse IP, données de navigation, et contenu généré. Ces données sont utilisées pour fournir et améliorer nos services, personnaliser votre expérience, et respecter nos obligations légales. <br><br>
                        9.3. <strong>Partage des données</strong> : Nous ne vendons pas vos données personnelles à des tiers. Cependant, nous pouvons partager vos données avec des partenaires de confiance (par exemple, des fournisseurs de services d’hébergement, des processeurs de paiement, ou des outils d’analyse) dans la mesure nécessaire pour fournir nos services. Nous pouvons également divulguer vos données si cela est requis par la loi, une autorité judiciaire, ou pour protéger nos droits et intérêts légitimes. <br><br>
                        9.4. <strong>Sécurité des données</strong> : Nous mettons en œuvre des mesures de sécurité techniques et organisationnelles pour protéger vos données contre l’accès non autorisé, la perte, ou la divulgation. Cela inclut, mais ne se limite pas à, le chiffrement des données sensibles, l’utilisation de pare-feu, et la mise en place de protocoles d’authentification sécurisés. Toutefois, aucune méthode de transmission ou de stockage électronique n’est totalement sécurisée, et nous ne pouvons garantir une protection absolue contre les cyberattaques ou autres violations de sécurité.
                    </p>

                    <h3 class="text-primary fw-bold mb-4">10. Droit applicable et juridiction</h3>
                    <p class="text-muted">
                        10.1. <strong>Droit applicable</strong> : Les présentes CGU sont régies par le droit français, sans égard aux principes de conflit de lois. Toute réclamation, action en justice, ou litige découlant de ou lié à votre utilisation de la Plateforme sera soumis à l’interprétation et à l’application du droit français. <br><br>
                        10.2. <strong>Juridiction compétente</strong> : En cas de litige, les parties s’engagent à tenter de résoudre le différend à l’amiable dans un délai de 30 jours à compter de la notification écrite du litige. Si aucune solution amiable n’est trouvée, tout litige sera soumis à la compétence exclusive des tribunaux de Paris, France, sauf disposition légale impérative contraire. <br><br>
                        10.3. <strong>Résolution alternative des litiges</strong> : Nous encourageons les utilisateurs à envisager des méthodes alternatives de résolution des litiges, telles que la médiation ou l’arbitrage, avant de recourir à une action en justice. Pour plus d’informations sur les options disponibles, veuillez contacter notre support à support@lailaworkspace.com.
                    </p>

                    <h3 class="text-primary fw-bold mb-4">11. Dispositions diverses</h3>
                    <p class="text-muted">
                        11.1. <strong>Intégralité de l’accord</strong> : Les présentes CGU, ainsi que notre Politique de Confidentialité et toute autre politique ou condition mentionnée sur la Plateforme, constituent l’intégralité de l’accord entre vous et Laila Workspace concernant votre utilisation de la Plateforme. Elles remplacent tous les accords, communications, ou understandings antérieurs, qu’ils soient écrits ou oraux. <br><br>
                        11.2. <strong>Non-renonciation</strong> : Le fait pour Laila Workspace de ne pas exercer ou appliquer un droit ou une disposition des présentes CGU ne constitue pas une renonciation à ce droit ou à cette disposition. Toute renonciation doit être expressément formulée par écrit et signée par un représentant autorisé de Laila Workspace. <br><br>
                        11.3. <strong>Divisibilité</strong> : Si une disposition des présentes CGU est jugée invalide ou inapplicable par un tribunal compétent, cette disposition sera limitée ou supprimée dans la mesure minimale nécessaire, et les autres dispositions resteront pleinement en vigueur. <br><br>
                        11.4. <strong>Langue</strong> : Les présentes CGU sont rédigées en français. En cas de traduction dans une autre langue, la version française prévaudra en cas de divergence ou d’incohérence.
                    </p>

                    <h3 class="text-primary fw-bold mb-4">12. Contact</h3>
                    <p class="text-muted">
                        Si vous avez des questions, préoccupations, ou réclamations concernant les présentes CGU ou votre utilisation de la Plateforme, nous vous invitons à nous contacter à l’adresse suivante : <br>
                        <strong>Email</strong> : <a href="mailto:support@lailaworkspace.com" class="text-primary">support@lailaworkspace.com</a> <br>
                        <strong>Adresse postale</strong> : Laila Workspace, 123 Rue de l’Innovation, 75001 Paris, France. <br>
                        <strong>Téléphone</strong> : +33 1 23 45 67 89 (disponible du lundi au vendredi, de 9h à 17h). <br>
                        Nous nous efforcerons de répondre à toutes les demandes dans un délai de 5 jours ouvrables. Pour les demandes urgentes, veuillez indiquer "URGENT" dans l’objet de votre email.
                    </p>

                    <h3 class="text-primary fw-bold mb-4">13. Annexes</h3>
                    <p class="text-muted">
                        13.1. <strong>Annexes relatives aux services premium</strong> : Si vous souscrivez à un abonnement premium ou à des services payants, des conditions supplémentaires peuvent s’appliquer. Ces conditions seront détaillées dans un contrat séparé ou dans les paramètres de votre compte lors de la souscription. Les services premium incluent, sans s’y limiter, l’accès à des fonctionnalités avancées, un support prioritaire, et des options de personnalisation supplémentaires. <br><br>
                        13.2. <strong>Annexes relatives aux API</strong> : Si vous utilisez nos API pour intégrer Laila Workspace à des applications tierces, vous devez respecter nos conditions d’utilisation des API, disponibles sur demande. Toute utilisation abusive des API peut entraîner la suspension de votre accès. <br><br>
                        13.3. <strong>Annexes relatives aux partenariats</strong> : Si vous êtes un partenaire ou un affilié de Laila Workspace, des conditions spécifiques peuvent s’appliquer, notamment en ce qui concerne les commissions, les obligations de promotion, et les responsabilités légales. Ces conditions seront précisées dans un accord de partenariat séparé.
                    </p>

                    <h3 class="text-primary fw-bold mb-4">14. Historique des mises à jour</h3>
                    <p class="text-muted">
                        - <strong>Version 1.1</strong> (20/12/2024) : Ajout d’une section sur la résolution alternative des litiges. <br>
                        - <strong>Version 1.5</strong> (08/04/2025) : Extension des sections pour inclure des détails sur les partenariats et les annexes. <br>
                        Nous vous encourageons à consulter régulièrement cette section pour suivre l’évolution des CGU au fil du temps.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include '../layouts/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>