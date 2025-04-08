<?php
require_once '../models/Bmc.php';

class BmcController {
    private $pdo;
    private $bmcModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->bmcModel = new Bmc($pdo);
    }

    public function generate($prompt) {
        // Simuler un BMP généré (remplacer par une vraie IA dans un projet réel)
        $bmc_data = [
            'customer_segments' => 'Utilisateurs urbains, restaurants locaux, livreurs indépendants',
            'value_propositions' => 'Livraison rapide, large choix de restaurants, suivi en temps réel',
            'channels' => 'Application mobile, site web',
            'customer_relationships' => 'Support client 24/7, programme de fidélité',
            'revenue_streams' => 'Frais de livraison, commissions sur les restaurants',
            'key_resources' => 'Plateforme technologique, réseau de livreurs, partenariats avec restaurants',
            'key_activities' => 'Développement de l’application, gestion des livreurs, marketing',
            'key_partnerships' => 'Restaurants, livreurs, fournisseurs de paiement',
            'cost_structure' => 'Développement technologique, salaires des livreurs, marketing'
        ];

        if (isset($_SESSION['user_id'])) {
            $bmc_id = $this->bmcModel->create($_SESSION['user_id'], $prompt, $bmc_data);
            header("Location: " . BASE_URL . "/views/bmc/bmc.php?bmc_id=" . $bmc_id);
        } else {
            $_SESSION['temp_bmc'] = $bmc_data;
            $_SESSION['temp_prompt'] = $prompt;
            header("Location: " . BASE_URL . "/views/bmc/bmc.php");
        }
        exit;
    }

    public function update($bmc_id, $user_id, $bmc_data) {
        $this->bmcModel->update($bmc_id, $user_id, $bmc_data);
    }
}