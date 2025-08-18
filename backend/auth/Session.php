
<?php

class Session {
    private static $instance = null;
    
    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    public function remove($key) {
        unset($_SESSION[$key]);
    }
    
    public function destroy() {
        session_destroy();
    }
    
    public function regenerateId() {
        session_regenerate_id(true);
    }
    
    public function isLoggedIn() {
        return $this->has('user_id') && $this->has('rol');
    }
    
    public function getUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'user_id' => $this->get('user_id'),
            'username' => $this->get('username'),
            'rol' => $this->get('rol'),
            'refugio_id' => $this->get('refugio_id'),
            'nombre_mostrado' => $this->get('nombre_mostrado')
        ];
    }
    
    public function login($user) {
        $this->regenerateId();
        $this->set('user_id', $user['usuario_id']);
        $this->set('username', $user['username']);
        $this->set('rol', $user['rol']);
        $this->set('refugio_id', $user['refugio_id']);
        $this->set('nombre_mostrado', $user['nombre_mostrado']);
        $this->set('login_time', time());
    }
    
    public function logout() {
        session_unset();
        $this->destroy();
    }
    
    public function checkRole($allowedRoles) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userRole = $this->get('rol');
        return in_array($userRole, (array)$allowedRoles);
    }
    
    public function generateCSRFToken() {
        if (!$this->has('csrf_token')) {
            $this->set('csrf_token', bin2hex(random_bytes(32)));
        }
        return $this->get('csrf_token');
    }
    
    public function validateCSRFToken($token) {
        return hash_equals($this->get('csrf_token', ''), $token);
    }
}
