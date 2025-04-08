<?php
require_once '../../includes/db_connect.php';

// Détruire toutes les données de la session
session_unset();
session_destroy();

// Rediriger vers la page d'accueil
header('Location: ../../index.php');
exit();