<?php
class Bmc {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($user_id, $prompt, $bmc_data) {
        $stmt = $this->pdo->prepare("INSERT INTO bmcs (user_id, prompt, bmc_data) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $prompt, json_encode($bmc_data)]);
        return $this->pdo->lastInsertId();
    }

    public function findById($id, $user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM bmcs WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findAllByUser($user_id) {
        $stmt = $this->pdo->prepare("SELECT id, prompt, created_at FROM bmcs WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $user_id, $bmc_data) {
        $stmt = $this->pdo->prepare("UPDATE bmcs SET bmc_data = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([json_encode($bmc_data), $id, $user_id]);
    }
}