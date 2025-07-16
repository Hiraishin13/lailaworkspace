<?php
/**
 * Template PDF unifié pour Laila Workspace
 * Utilisé par tous les générateurs de PDF
 */

class PDFTemplate {
    private $project_name;
    private $user_name;
    private $generation_date;
    
    public function __construct($project_name, $user_name = '') {
        $this->project_name = $project_name;
        $this->user_name = $user_name;
        $this->generation_date = date('d/m/Y à H:i');
    }
    
    /**
     * Génère le header du PDF
     */
    public function getHeader() {
        return '
        <div class="header">
            <div class="main-title">
                <h1 class="laila-title">LAILA WORKSPACE</h1>
            </div>
            <div class="header-content">
                <div class="logo-section">
                    <div class="logo">
                        <svg width="50" height="50" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="logoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#007bff;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#00c4b4;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <rect width="50" height="50" rx="12" fill="url(#logoGradient)"/>
                            <path d="M15 15 L25 25 L35 15 M15 35 L25 25 L35 35" stroke="white" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="25" cy="25" r="3" fill="white"/>
                        </svg>
                    </div>
                    <div class="brand">
                        <h2>Business Model Canvas Generator</h2>
                        <p class="tagline">Plateforme d\'innovation et de développement d\'entreprise</p>
                    </div>
                </div>
                <div class="project-info">
                    <h3>' . htmlspecialchars($this->project_name) . '</h3>
                    <p class="generation-date">Généré le ' . $this->generation_date . '</p>
                </div>
            </div>
        </div>';
    }
    
    /**
     * Génère le footer du PDF
     */
    public function getFooter() {
        return '
        <div class="footer">
            <div class="footer-content">
                <div class="footer-left">
                    <p>© ' . date('Y') . ' Laila Workspace - Tous droits réservés</p>
                </div>
                <div class="footer-right">
                    <p>Page <span class="page"></span> sur <span class="topage"></span></p>
                </div>
            </div>
        </div>';
    }
    
    /**
     * Génère les styles CSS modernes
     */
    public function getStyles() {
        return '
        <style>
            @page {
                margin: 1.5cm;
                size: A4;
            }
            
            body { 
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                margin: 0;
                padding: 0;
                color: #333;
                line-height: 1.5;
                font-size: 11px;
                background: #fff;
            }
            
            /* Header Styles */
            .header {
                background: linear-gradient(135deg, #667eea 0%, #00c4b4 100%);
                color: white;
                padding: 20px 15px 15px 15px;
                margin-bottom: 20px;
                border-radius: 0 0 10px 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                page-break-after: avoid;
            }
            
            .main-title {
                text-align: center;
                margin-bottom: 20px;
                padding: 25px 0 20px 0;
                border-bottom: 4px solid rgba(255,255,255,0.5);
                background: rgba(255,255,255,0.15);
                border-radius: 8px 8px 0 0;
                position: relative;
            }
            
            .main-title::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 3px;
                background: linear-gradient(90deg, #ffffff, #00c4b4, #ffffff);
            }
            
            .laila-title {
                margin: 0;
                font-size: 3.5em;
                font-weight: 900;
                letter-spacing: 5px;
                text-transform: uppercase;
                text-shadow: 4px 4px 8px rgba(0,0,0,0.5);
                line-height: 1.1;
                color: #ffffff;
                padding: 15px 0;
                position: relative;
                z-index: 10;
            }
            
            .header-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .logo-section {
                display: flex;
                align-items: center;
                gap: 12px;
                flex: 1;
            }
            
            .logo {
                flex-shrink: 0;
            }
            
            .logo svg {
                display: block;
            }
            
            .brand h2 {
                margin: 0;
                font-size: 1.4em;
                font-weight: 700;
                letter-spacing: -0.5px;
                line-height: 1.2;
            }
            
            .tagline {
                margin: 3px 0 0 0;
                font-size: 0.9em;
                opacity: 0.9;
                line-height: 1.2;
            }
            
            .project-info {
                text-align: right;
                flex-shrink: 0;
            }
            
            .project-info h3 {
                margin: 0;
                font-size: 1.1em;
                font-weight: 600;
                line-height: 1.2;
                word-wrap: break-word;
                max-width: 200px;
            }
            
            .generation-date {
                margin: 3px 0 0 0;
                font-size: 0.7em;
                opacity: 0.8;
                line-height: 1.2;
            }
            
            /* Content Styles */
            .container {
                padding: 0 10px;
                margin-bottom: 60px; /* Espace pour le footer */
            }
            
            .section {
                margin-bottom: 25px;
                page-break-inside: avoid;
            }
            
            .section-title {
                color: #667eea;
                font-size: 1.2em;
                font-weight: 600;
                margin-bottom: 12px;
                padding-bottom: 6px;
                border-bottom: 2px solid #e9ecef;
                position: relative;
                page-break-after: avoid;
            }
            
            .section-title::after {
                content: "";
                position: absolute;
                bottom: -2px;
                left: 0;
                width: 40px;
                height: 2px;
                background: linear-gradient(135deg, #667eea, #00c4b4);
            }
            
            /* Card Styles */
            .card {
                background: #fff;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 12px;
                box-shadow: 0 1px 5px rgba(0,0,0,0.05);
                page-break-inside: avoid;
            }
            
            .card-title {
                color: #667eea;
                font-size: 1em;
                font-weight: 600;
                margin-bottom: 8px;
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
            }
            
            .card-content {
                color: #6c757d;
                line-height: 1.4;
                font-size: 0.9em;
            }
            
            /* Grid Layout */
            .grid {
                display: grid;
                gap: 15px;
                margin-bottom: 15px;
            }
            
            .grid-2 {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .grid-3 {
                grid-template-columns: repeat(3, 1fr);
            }
            
            /* BMC Specific Styles */
            .bmc-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
                margin: 15px 0;
            }
            
            .bmc-block {
                background: #f8f9fa;
                border: 1px solid #e9ecef;
                border-radius: 6px;
                padding: 12px;
                text-align: center;
                min-height: 100px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                page-break-inside: avoid;
            }
            
            .bmc-block-title {
                color: #667eea;
                font-weight: 600;
                font-size: 0.8em;
                margin-bottom: 8px;
                text-transform: uppercase;
                letter-spacing: 0.3px;
                line-height: 1.2;
            }
            
            .bmc-block-content {
                color: #495057;
                font-size: 0.8em;
                line-height: 1.3;
                flex-grow: 1;
                word-wrap: break-word;
            }
            
            /* Table Styles */
            .table {
                width: 100%;
                border-collapse: collapse;
                margin: 12px 0;
                background: white;
                border-radius: 6px;
                overflow: hidden;
                box-shadow: 0 1px 5px rgba(0,0,0,0.05);
                font-size: 0.8em;
            }
            
            .table th {
                background: linear-gradient(135deg, #667eea, #00c4b4);
                color: white;
                padding: 10px;
                text-align: left;
                font-weight: 600;
                font-size: 0.8em;
            }
            
            .table td {
                padding: 10px;
                border-bottom: 1px solid #e9ecef;
                font-size: 0.8em;
                vertical-align: top;
            }
            
            .table tr:nth-child(even) {
                background: #f8f9fa;
            }
            
            /* Status Badges */
            .badge {
                display: inline-block;
                padding: 3px 6px;
                border-radius: 10px;
                font-size: 0.7em;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.3px;
                white-space: nowrap;
            }
            
            .badge-success {
                background: #d4edda;
                color: #155724;
            }
            
            .badge-warning {
                background: #fff3cd;
                color: #856404;
            }
            
            .badge-danger {
                background: #f8d7da;
                color: #721c24;
            }
            
            .badge-info {
                background: #d1ecf1;
                color: #0c5460;
            }
            
            /* Footer Styles */
            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: #f8f9fa;
                border-top: 1px solid #e9ecef;
                padding: 8px 15px;
                font-size: 0.7em;
                color: #6c757d;
                z-index: 1000;
            }
            
            .footer-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                max-width: 100%;
            }
            
            .footer-left, .footer-right {
                flex: 1;
            }
            
            .footer-right {
                text-align: right;
            }
            
            .footer p {
                margin: 0;
                line-height: 1.2;
            }
            
            /* Utility Classes */
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .text-muted { color: #6c757d; }
            .font-weight-bold { font-weight: 600; }
            .mb-0 { margin-bottom: 0; }
            .mt-0 { margin-top: 0; }
            
            /* Page Break Control */
            .page-break { page-break-before: always; }
            .no-break { page-break-inside: avoid; }
            
            /* Responsive adjustments */
            @media print {
                .header {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    z-index: 1000;
                }
                
                .container {
                    margin-top: 100px;
                }
                
                .footer {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    z-index: 1000;
                }
            }
            
            /* Prevent text overflow */
            * {
                box-sizing: border-box;
            }
            
            p, div, span {
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            
            /* Ensure proper spacing */
            h1, h2, h3, h4, h5, h6 {
                margin-top: 0;
                margin-bottom: 0.5em;
                line-height: 1.2;
            }
            
            p {
                margin-top: 0;
                margin-bottom: 0.5em;
            }
            
            /* Pagination styles */
            .page:before {
                content: counter(page);
            }
            
            .topage:before {
                content: counter(pages);
            }
        </style>';
    }
    
    /**
     * Nettoie et formate un nom de fichier
     */
    public static function cleanFileName($name) {
        // Supprimer les caractères spéciaux et accents
        $name = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $name);
        // Remplacer les espaces par des tirets
        $name = preg_replace('/\s+/', '-', $name);
        // Convertir en minuscules
        $name = strtolower($name);
        // Limiter la longueur
        return substr($name, 0, 50);
    }
    
    /**
     * Génère un nom de fichier propre
     */
    public function generateFileName($type, $project_id = '') {
        $clean_project_name = self::cleanFileName($this->project_name);
        $date = date('Y-m-d');
        $time = date('H-i');
        
        switch ($type) {
            case 'bmc':
                return "bmc-{$clean_project_name}-{$date}.pdf";
            case 'hypotheses':
                return "hypotheses-{$clean_project_name}-{$date}.pdf";
            case 'financial':
                return "plan-financier-{$clean_project_name}-{$date}.pdf";
            case 'summary':
                return "resume-bmp-{$clean_project_name}-{$date}.pdf";
            default:
                return "laila-workspace-{$clean_project_name}-{$date}.pdf";
        }
    }
}
?> 