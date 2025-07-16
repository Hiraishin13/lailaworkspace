<?php
require_once 'config.php';

class AIService {
    private $api_key;
    private $model;
    private $base_url = 'https://api.openai.com/v1/chat/completions';
    
    public function __construct() {
        $this->api_key = OPENAI_API_KEY;
        $this->model = AI_MODEL;
    }
    
    /**
     * Analyse la compatibilité entre deux projets avec l'IA
     */
    public function analyzePartnershipCompatibility($project1_data, $project2_data) {
        $prompt = $this->buildCompatibilityPrompt($project1_data, $project2_data);
        
        $response = $this->callOpenAI($prompt);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            return $this->parseCompatibilityResponse($response['choices'][0]['message']['content']);
        }
        
        return null;
    }
    
    /**
     * Génère des suggestions de partenariats intelligentes
     */
    public function generatePartnershipSuggestions($project_data, $all_projects) {
        $prompt = $this->buildSuggestionPrompt($project_data, $all_projects);
        
        $response = $this->callOpenAI($prompt);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            return $this->parseSuggestionResponse($response['choices'][0]['message']['content']);
        }
        
        return [];
    }
    
    /**
     * Analyse les tendances du marché
     */
    public function analyzeMarketTrends($projects_data) {
        $prompt = $this->buildTrendsPrompt($projects_data);
        
        $response = $this->callOpenAI($prompt);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            return $this->parseTrendsResponse($response['choices'][0]['message']['content']);
        }
        
        return null;
    }
    
    /**
     * Construit le prompt pour l'analyse de compatibilité
     */
    private function buildCompatibilityPrompt($project1, $project2) {
        return "Tu es un expert en analyse de partenariats business. Analyse la compatibilité entre ces deux projets et donne un score de 0 à 100 avec des explications détaillées.

PROJET 1:
- Nom: {$project1['name']}
- Description: {$project1['description']}
- Marché cible: {$project1['target_market']}
- Segments de clientèle: {$project1['segments']}
- Proposition de valeur: {$project1['value_proposition']}
- Canaux: {$project1['channels']}
- Partenaires clés: {$project1['partners']}
- Hypothèses validées: {$project1['validated_hypotheses']}
- Marge bénéficiaire: {$project1['profit_margin']}%

PROJET 2:
- Nom: {$project2['name']}
- Description: {$project2['description']}
- Marché cible: {$project2['target_market']}
- Segments de clientèle: {$project2['segments']}
- Proposition de valeur: {$project2['value_proposition']}
- Canaux: {$project2['channels']}
- Partenaires clés: {$project2['partners']}
- Hypothèses validées: {$project2['validated_hypotheses']}
- Marge bénéficiaire: {$project2['profit_margin']}%

Réponds au format JSON suivant:
{
  \"score\": 85,
  \"overall_assessment\": \"Excellente compatibilité...\",
  \"factors\": [
    {
      \"name\": \"Segments de Clientèle\",
      \"score\": 25,
      \"description\": \"Segments très similaires...\",
      \"synergy\": \"Les deux projets ciblent des PME...\"
    }
  ],
  \"recommendations\": [
    \"Partage des canaux de distribution\",
    \"Développement de solutions intégrées\"
  ],
  \"risks\": [
    \"Concurrence potentielle sur certains segments\"
  ]
}";
    }
    
    /**
     * Construit le prompt pour les suggestions de partenariats
     */
    private function buildSuggestionPrompt($project, $all_projects) {
        $projects_list = "";
        foreach ($all_projects as $p) {
            $projects_list .= "- {$p['name']}: {$p['description']} (Marché: {$p['target_market']})\n";
        }
        
        return "Tu es un expert en partenariats business. Pour le projet suivant, suggère les 3 meilleurs partenariats possibles parmi la liste donnée.

PROJET ACTUEL:
- Nom: {$project['name']}
- Description: {$project['description']}
- Marché cible: {$project['target_market']}
- Segments: {$project['segments']}
- Proposition de valeur: {$project['value_proposition']}

PROJETS DISPONIBLES:
{$projects_list}

Réponds au format JSON:
{
  \"suggestions\": [
    {
      \"project_id\": 2,
      \"project_name\": \"Nom du projet\",
      \"synergy_score\": 85,
      \"synergy_reasons\": [\"Raison 1\", \"Raison 2\"],
      \"potential_benefits\": [\"Bénéfice 1\", \"Bénéfice 2\"],
      \"implementation_strategy\": \"Stratégie d'implémentation...\"
    }
  ]
}";
    }
    
    /**
     * Construit le prompt pour l'analyse des tendances
     */
    private function buildTrendsPrompt($projects_data) {
        $projects_summary = "";
        foreach ($projects_data as $project) {
            $projects_summary .= "- {$project['name']}: {$project['sector']} ({$project['stage']})\n";
        }
        
        return "Tu es un expert en analyse de marché. Analyse les tendances basées sur ces projets et donne des insights business.

PROJETS ANALYSÉS:
{$projects_summary}

Réponds au format JSON:
{
  \"market_trends\": [
    {
      \"trend\": \"Tendance identifiée\",
      \"description\": \"Description de la tendance\",
      \"impact\": \"Impact sur le marché\",
      \"opportunities\": [\"Opportunité 1\", \"Opportunité 2\"]
    }
  ],
  \"sector_analysis\": {
    \"hot_sectors\": [\"Secteur 1\", \"Secteur 2\"],
    \"emerging_opportunities\": [\"Opportunité 1\", \"Opportunité 2\"]
  },
  \"recommendations\": [
    \"Recommandation stratégique 1\",
    \"Recommandation stratégique 2\"
  ]
}";
    }
    
    /**
     * Appelle l'API OpenAI
     */
    private function callOpenAI($prompt) {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key
        ];
        
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un expert en analyse business et partenariats. Réponds toujours en français et au format JSON demandé.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 2000
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            return json_decode($response, true);
        }
        
        error_log("OpenAI API Error: HTTP $http_code - $response");
        return null;
    }
    
    /**
     * Parse la réponse de compatibilité
     */
    private function parseCompatibilityResponse($content) {
        // Nettoyer le contenu JSON
        $content = preg_replace('/```json\s*|\s*```/', '', $content);
        $content = trim($content);
        
        $data = json_decode($content, true);
        
        if (!$data) {
            // Fallback si le JSON n'est pas valide
            return [
                'score' => 50,
                'overall_assessment' => 'Analyse IA non disponible',
                'factors' => [],
                'recommendations' => [],
                'risks' => []
            ];
        }
        
        return $data;
    }
    
    /**
     * Parse la réponse de suggestions
     */
    private function parseSuggestionResponse($content) {
        $content = preg_replace('/```json\s*|\s*```/', '', $content);
        $content = trim($content);
        
        $data = json_decode($content, true);
        
        if (!$data || !isset($data['suggestions'])) {
            return [];
        }
        
        return $data['suggestions'];
    }
    
    /**
     * Parse la réponse des tendances
     */
    private function parseTrendsResponse($content) {
        $content = preg_replace('/```json\s*|\s*```/', '', $content);
        $content = trim($content);
        
        $data = json_decode($content, true);
        
        if (!$data) {
            return null;
        }
        
        return $data;
    }
}
?> 