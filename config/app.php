
<?php
/**
 * Application Configuration
 * Sistema de Refugios
 */

return [
    'app_name' => 'Sistema de Refugios',
    'app_version' => '0.1.0',
    'app_env' => $_ENV['APP_ENV'] ?? 'development',
    
    // Database configuration is handled in backend/config/database.php
    
    // Security settings
    'session_timeout' => 3600, // 1 hour
    'max_login_attempts' => 5,
    
    // File upload settings
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    'allowed_file_types' => ['csv'],
    
    // Pagination
    'default_page_size' => 50,
    'max_page_size' => 1000,
    
    // API settings
    'api_rate_limit' => 100, // requests per hour
];
