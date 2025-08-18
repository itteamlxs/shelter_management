
<?php
class Auth {
    private static $secret_key = 'your-secret-key-change-in-production';
    private static $algorithm = 'HS256';
    
    public static function generateToken($user_data) {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algorithm]);
        
        $payload = json_encode([
            'iss' => 'refugios-platform',
            'aud' => 'refugios-users',
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour
            'usuario_id' => $user_data['usuario_id'],
            'rol' => $user_data['rol'],
            'refugio_id' => $user_data['refugio_id'] ?? null,
            'username' => $user_data['username']
        ]);
        
        $header_encoded = self::base64url_encode($header);
        $payload_encoded = self::base64url_encode($payload);
        
        $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", self::$secret_key, true);
        $signature_encoded = self::base64url_encode($signature);
        
        return "$header_encoded.$payload_encoded.$signature_encoded";
    }
    
    public static function validateToken($token) {
        if (!$token) return false;
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;
        
        list($header, $payload, $signature) = $parts;
        
        $expected_signature = hash_hmac('sha256', "$header.$payload", self::$secret_key, true);
        $expected_signature_encoded = self::base64url_encode($expected_signature);
        
        if (!hash_equals($signature, $expected_signature_encoded)) {
            return false;
        }
        
        $payload_data = json_decode(self::base64url_decode($payload), true);
        
        if ($payload_data['exp'] < time()) {
            return false;
        }
        
        return $payload_data;
    }
    
    public static function getCurrentUser() {
        $headers = getallheaders();
        $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return false;
        }
        
        return self::validateToken($matches[1]);
    }
    
    public static function requireAuth($required_role = null) {
        $user = self::getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        
        if ($required_role && $user['rol'] !== $required_role && $user['rol'] !== 'Administrador') {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient privileges']);
            exit;
        }
        
        return $user;
    }
    
    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private static function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
