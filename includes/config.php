<?php
// Configuration de l'API OpenAI
if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', 'sk-svcacct-Qz0mXclNZWxR15933RKEN6LncZREDwsW34pavQu3Sg-rPrgoeLRYtbWsa7mkzT3BlbkFJnd-TXL8oYOKiEaxIPqqaa4_otXU_9v99fk9DehTbasb_ywfyOMOAJ6lBn25OwA');
}

// Configuration de l'URL de base
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/lailaworkspace');
}

// Configuration de l'IA
if (!defined('AI_ENABLED')) {
    define('AI_ENABLED', true);
}

if (!defined('AI_MODEL')) {
    define('AI_MODEL', 'gpt-3.5-turbo');
}
?>