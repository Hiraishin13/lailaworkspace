<?php
require_once '../models/Hypothesis.php';

class HypothesisController {
    private $pdo;
    private $hypothesisModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->hypothesisModel = new Hypothesis($pdo);
    }

    public function initialize($bmc_id, $user_id) {
        $hypotheses = $this->hypothesisModel->findByBmcId($bmc_id, $user_id);
        if (empty($hypotheses)) {
            $default_hypotheses = [
                "Les utilisateurs urbains préféreront notre application pour sa rapidité.",
                "Les restaurants locaux seront prêts à payer une commission de 20%.",
                "Les livreurs indépendants seront disponibles en nombre suffisant."
            ];
            foreach ($default_hypotheses as $hypothesis) {
                $this->hypothesisModel->create($bmc_id, $user_id, $hypothesis);
            }
        }
    }

    public function add($bmc_id, $user_id, $hypothesis_text) {
        $this->hypothesisModel->create($bmc_id, $user_id, $hypothesis_text);
    }

    public function update($id, $user_id, $hypothesis_text, $status) {
        $this->hypothesisModel->update($id, $user_id, $hypothesis_text, $status);
    }
}