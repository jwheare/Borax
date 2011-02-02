<?php

namespace Core;
use Exception;

class Dispatcher {
    protected $routes;
    public function __construct (array $routes) {
        $this->routes = $routes;
    }
    protected function generateExceptionResponse (Request $request, HttpStatus\Base $exception) {
        if ($exception instanceof HttpStatus\BaseRedirect) {
            $response = new Response\Redirect($request, $exception);
        } elseif ($request->acceptJson()) {
            $response = new Response\JsonError($request, $exception);
        } else {
            $response = new Response\HtmlError($request, $exception);
        }
        return $response;
    }
    /**
     * Map a request to controller parts
     *
     * @throws Core\HttpStatus
    **/
    public function mapRequestToControllerParts (Request $request) {
        // Map request method / accept to controller type
        $method = $request->getMethod();
        switch ($method) {
        case 'GET':
        case 'HEAD':
            if ($request->acceptJson()) {
                $type = "json{$method}";
            } else {
                $type = "html";
            }
            break;
        case 'POST':
            if ($request->acceptJson()) {
                $type = "json{$method}";
            } else {
                $type = "form";
            }
            break;
        case 'PUT':
        case 'DELETE':
            if ($request->acceptJson()) {
                $type = "json{$method}";
            } else {
                // Only JSON accepts complex methods
                $type = "invalid";
            }
            break;
        default:
            throw new HttpStatus\NotImplemented("$method requests aren’t implemented on this server");
            break;
        }
        $type = strtolower($type);
        // Map URL to controller via patterns
        $action = 'index'; // default
        foreach ($this->routes as $pattern => $route) {
            if (preg_match($pattern, substr($request->getUrlPart('path'), 1), $args)) {
                $name = $route;
                if (strpos($name, '$') === 0) {
                    $name = $args[substr($name, 1)];
                }
                if (isset($args['ACTION']) && $args['ACTION']) {
                    $action = $args['ACTION'];
                }
                break;
            }
        }
        return array($name, $type, $action, $args);
    }
    protected function doesControllerActionExist ($controllerClass, $action) {
        return class_exists($controllerClass) && method_exists($controllerClass, $action);
    }
    protected function getControllerSupport ($controllerRoot, $action, $requestMethod) {
        $methods = array();
        $mimeTypes = array();
        $classes = Controller\getClasses();
        foreach ($classes as $mimeType => $typeClasses) {
            foreach ($typeClasses as $type => $method) {
                $controllerClass = $controllerRoot . $type;
                if ($this->doesControllerActionExist($controllerClass, $action)) {
                    $methods[] = $method;
                    if ($requestMethod === $method) {
                        $mimeTypes[] = $mimeType;
                    }
                }
            }
        }
        return array(
            'methods' => array_unique($methods),
            'mimeTypes' => array_unique($mimeTypes),
        );
    }
    /**
     * Get a controller to deal with a request
     *
     * @return Core\Controller\Base
     * @throws Core\HttpStatus
    **/
    protected function getController (Request $request) {
        // Load the controllers for this route
        list($name, $type, $action, $args) = $this->mapRequestToControllerParts($request);
        $controllerFile = CONTROLLER_DIR . "/{$name}.controller.php";
        if (!file_exists($controllerFile)) {
            // Check whether there's a simple page template
            $pageController = new Controller\Page($request, $name, $action, $args);
            if ($pageController->exists()) {
                return $pageController;
            }
            
            throw new HttpStatus\NotFound();
        }
        require_once($controllerFile);
        
        // Choose a controller class to deal with this request
        $controllerRoot = "App\\Controller\\{$name}";
        $controllerClass = $controllerRoot . $type;
        
        if (!$this->doesControllerActionExist($controllerClass, $action)) {
            
            // There's no valid action for this request. Check if the URL is available for other request types
            $requestMethod = $request->getMethod();
            $support = $this->getControllerSupport($controllerRoot, $action, $requestMethod);
            
            // a) URL valid in another mime/type
            if (!empty($support['mimeTypes'])) {
                $exception = new HttpStatus\NotAcceptable("Not all mime/types are accepted for $requestMethod requests at this URL. Accepted mime/types are: " . implode(", ", $support['mimeTypes']));
                $exception->setAcceptedMimeTypes($support['mimeTypes']);
                throw $exception;
            }
            
            // b) URL valid with another method
            if (!empty($support['methods'])) {
                $exception = new HttpStatus\MethodNotAllowed("$requestMethod requests aren’t allowed at this URL. Allowed methods are: " . implode(", ", $support['methods']));
                $exception->setAllowedMethods($support['methods']);
                throw $exception;
            }
            
            // c) 404d!
            throw new HttpStatus\NotFound();
        }
        $controller = new $controllerClass($request, $name, $action, $args);
        
        // Run the shared controller
        $sharedClass = "{$controllerRoot}Shared";
        if (class_exists($sharedClass)) {
            $shared = new $sharedClass($controller, $name, $action);
            $shared->processSetUp();
        }
        return $controller;
    }
    /**
     * Process the request by mapping the URL to a controller class and method and render a response
    **/
    public function processRequest (Request $request) {
        try {
            // Map the request to a controller action and generate its response
            $controller = $this->getController($request);
            $response = $controller->generateResponse();
        } catch (Exception $exception) {
            if (!$exception instanceof HttpStatus\Base) {
                $exception = new HttpStatus\InternalServerError($exception->__toString() . ": " . $exception->getMessage(), $exception);
            }
            $response = $this->generateExceptionResponse($request, $exception);
        }
        return $response;
    }
}
