<?php
require_once 'includes/db_connect.php';

// Rediriger vers la page d'accueil
header("Location: " . BASE_URL . "/views/index.php");
exit;