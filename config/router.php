
<?php
class Router {
    private $routes = [];
    
    public function addRoute($method, $path, $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
    }
    
    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
    }
    
    public function put($path, $handler) {
        $this->addRoute('PUT', $path, $handler);
    }
    
    public function delete($path, $handler) {
        $this->addRoute('DELETE', $path, $handler);
    }
    
    public function route() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $path)) {
                $params = $this->extractParams($route['path'], $path);
                return call_user_func($route['handler'], $params);
            }
        }
        
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
    }
    
    private function matchPath($pattern, $path) {
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
        return preg_match('#^' . $pattern . '$#', $path);
    }
    
    private function extractParams($pattern, $path) {
        preg_match_all('/\{([^}]+)\}/', $pattern, $param_names);
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
        preg_match('#^' . $pattern . '$#', $path, $matches);
        
        array_shift($matches);
        $params = [];
        for ($i = 0; $i < count($param_names[1]); $i++) {
            $params[$param_names[1][$i]] = $matches[$i] ?? null;
        }
        
        return $params;
    }
}
