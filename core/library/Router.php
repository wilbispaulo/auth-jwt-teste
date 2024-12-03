<?php

namespace core\library;

use Exception;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use App\controllers\NotFoundController;
use App\controllers\MethodNotAllowedController;
use function FastRoute\simpleDispatcher;

class Router
{
    private array $routes;
    private array $group;

    private function groupRoutes(RouteCollector $r)
    {
        foreach ($this->group as $prefix => $routes) {
            $r->addGroup($prefix, function (RouteCollector $r) use ($routes) {
                foreach ($routes() as $route) {
                    $r->addRoute(...$route);
                }
            });
        }
    }

    public function group(string $prefix, array $routes)
    {
        $callback = function () use ($routes) {
            return $routes;
        };

        $this->group[$prefix] = $callback;
    }

    public function add(string $httpMethod, string $uri, array $controller)
    {
        $this->routes[] = [$httpMethod, $uri, $controller];
    }

    public function run()
    {
        $dispatcher = simpleDispatcher(function (RouteCollector $r) {
            if (!empty($this->group)) {
                $this->groupRoutes($r);
            }

            foreach ($this->routes as $route) {
                $r->addRoute(...$route);
            }
        });


        $httpMethod = $_SERVER['REQUEST_METHOD'];

        $uri = self::getUri();

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        $this->handle($routeInfo);
    }

    private function handle(array $routeInfo)
    {
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                $controller = NotFoundController::class;
                $method = 'index';
                $params = [];
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                // ... 405 Method Not Allowed
                $controller = MethodNotAllowedController::class;
                $method = 'index';
                $params = [];
                break;
            case Dispatcher::FOUND:
                [, [$controller, $method], $params] = $routeInfo;

                $controllerClass = self::getClassName($controller);

                if (!class_exists($controller)) {
                    throw new Exception("O controller {$controllerClass} não existe...");
                }
                if (!method_exists($controller, $method)) {
                    throw new Exception("O método {$method} não existe no controller {$controllerClass}...");
                }
                break;
        }

        $controller = new $controller;

        $response = call_user_func_array([$controller, $method], $params);

        $response->send();
    }

    public static function getClassName(string $classname)
    {
        if ($pos = strrpos($classname, '\\')) return substr($classname, $pos + 1);
        return $pos;
    }

    public static function getUri()
    {
        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }
        return $uri;
    }

    public static function cors()
    {
        // Allow from any origin
        if (isset($_SERVER["HTTP_ORIGIN"])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            // header("Access-Control-Allow-Origin: *");
        } else {
            header("Access-Control-Allow-Origin: *");
        }


        if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"])) {
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        } else {
            header("Access-Control-Allow-Headers: *");
        }

        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 600");    // cache for 10 minutes

        if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
            if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"]))
                header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");

            if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"]))
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

            //Just exit with 200 OK with the above headers for OPTIONS method
            exit(0);
        }
        //From here, handle the request as it is ok
    }
}
