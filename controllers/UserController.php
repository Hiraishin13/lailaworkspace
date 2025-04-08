<?php
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $userModel;

    public function __construct($pdo) {
        $this->userModel = new User($pdo);
    }

    // Méthode pour récupérer les informations de l'utilisateur
    public function getUser($user_id) {
        return $this->userModel->findById($user_id); // Changement de getUserById() à findById()
    }

    // Exemple de méthode pour mettre à jour les informations de l'utilisateur
    public function updateUser($user_id, $data) {
        // Logique pour mettre à jour l'utilisateur
        // À implémenter selon tes besoins
    }
}