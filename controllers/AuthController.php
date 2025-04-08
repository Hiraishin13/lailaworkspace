<?php
require_once '../models/User.php';

class AuthController {
    private $pdo;
    private $userModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->userModel = new User($pdo);
    }

    public function register($data) {
        $user_id = $this->userModel->create(
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['experience'],
            $data['password']
        );

        $_SESSION['user_id'] = $user_id;

        // Si un BMP temporaire existe, l'enregistrer
        if (isset($_SESSION['temp_bmc'])) {
            $bmc_model = new Bmc($this->pdo);
            $bmc_id = $bmc_model->create($user_id, $_SESSION['temp_prompt'], $_SESSION['temp_bmc']);
            unset($_SESSION['temp_bmc']);
            unset($_SESSION['temp_prompt']);
            header("Location: " . BASE_URL . "/views/bmc/edit_bmc.php?bmc_id=" . $bmc_id);
            exit;
        }

        header("Location: " . BASE_URL . "/index.php");
        exit;
    }
}