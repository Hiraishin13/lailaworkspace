<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($username, $first_name, $last_name, $email, $phone, $experience, $password) {
        $stmt = $this->pdo->prepare("INSERT INTO users (username, first_name, last_name, email, phone, experience, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $first_name, $last_name, $email, $phone, $experience, password_hash($password, PASSWORD_DEFAULT)]);
        return $this->pdo->lastInsertId();
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $email, $password = null) {
        if ($password) {
            $stmt = $this->pdo->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
            $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $id]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$email, $id]);
        }
    }

    public function updatePassword($email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        return $stmt->execute([$hashedPassword, $email]);
    }
}