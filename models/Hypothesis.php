<?php
class Hypothesis {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($bmc_id, $user_id, $hypothesis_text) {
        $stmt = $this->pdo->prepare("INSERT INTO hypotheses (bmc_id, user_id, hypothesis_text) VALUES (?, ?, ?)");
        $stmt->execute([$bmc_id, $user_id, $hypothesis_text]);
    }

    public function findByBmcId($bmc_id, $user_id) {
        $stmt = $this->pdo->prepare("SELECT id, hypothesis_text, status FROM hypotheses WHERE bmc_id = ? AND user_id = ?");
        $stmt->execute([$bmc_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $user_id, $hypothesis_text, $status) {
        $stmt = $this->pdo->prepare("UPDATE hypotheses SET hypothesis_text = ?, status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$hypothesis_text, $status, $id, $user_id]);
    }
}