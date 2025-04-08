<?php
// Définition du chemin du fichier FPDF
$fpdf_path = realpath(__DIR__ . '/includes/fpdf.php');
if (!$fpdf_path || !file_exists($fpdf_path)) {
    die("Erreur : Le fichier FPDF n'a pas été trouvé à l'emplacement : " . __DIR__ . '/includes/fpdf.php');
}

// Inclusion de FPDF
require_once $fpdf_path;

// Vérification si la classe FPDF est bien chargée
if (!class_exists('FPDF')) {
    die("Erreur : La classe FPDF n'est pas définie. Vérifiez l'inclusion du fichier fpdf.php.");
}

// Création du PDF
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(40, 10, 'Hello World!');
$pdf->Output();
?>
