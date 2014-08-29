<?php
/**
 * Author: Arthur Halet
 * Date: 09/07/2014
 */


namespace ArthurH\SimpleApiHateoas;


use Arhframe\Annotations\AnnotationsArhframe;
use ArthurH\SimpleApiHateoas\Method\AbstractMethod;
use ArthurH\SimpleApiHateoas\Method\ApiShowMethod;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . '/Annotation/RouteAnnotation.php';
require_once __DIR__ . '/Annotation/LinksAnnotation.php';
require_once __DIR__ . '/Annotation/DescribeAnnotation.php';
require_once __DIR__ . '/Annotation/MethodAnnotation.php';

class Api
{
    public static $_PUT;
    public static $_DELETE;
    public static $APIBASEROUTE = 'api';
    public static $ERROR_VALIDATION = 1;
    public static $ERROR_SERVER = 0;
    public static $ERROR_INVALID_REQUEST = 2;
    public static $NO_SCRIPT_NAME = false;
    private $methods;
    private $links;

    public static function success($message)
    {
        header('HTTP/1.1 201 No content');
        return null;
    }

    public function apiRun()
    {
        $this->setRequestScheme();
        Api::populatePutOrDeleteRequest();
        global $methods, $apiBaseRoute, $links;
        $apiBaseRoute = Api::$APIBASEROUTE;
        $methods = $this->methods;
        $links = $this->links;
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) {
            global $methods, $apiBaseRoute, $links;
            $apiShowMethod = new ApiShowMethod();

            $apiShowMethod->setLinks($links);
            $r->addRoute("GET", '/' . $apiBaseRoute, $apiShowMethod);
            $r->addRoute("GET", '/' . $apiBaseRoute . '/version', $apiShowMethod);
            foreach ($methods as $methodName => $method) {
                $methodRequest = strtoupper($method->getRequestMethod());
                if ($methodRequest != 'GET' && $methodRequest != 'POST' && $methodRequest != 'PUT' && $methodRequest != 'DELETE') {
                    $methodRequest = 'GET';
                }
                $r->addRoute($methodRequest, '/' . $apiBaseRoute . '/' . $methodName . $method->getRoute(), $method);
            }

        });
        $routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO']);
        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                header('HTTP/1.1 400 bad request');
                echo Api::format(Api::error("Api Method not found"));
                return;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                echo Api::format(Api::error("Request method '" . implode(',', $allowedMethods) . "' unauthorized."));
                return;
            case \FastRoute\Dispatcher::FOUND:
                $method = $routeInfo[1];
                $method->setInfo($routeInfo[2]);

                if ($_SERVER['SERVER_PORT'] != 80) {
                    $port = ':' . $_SERVER['SERVER_PORT'];
                }
                $method->setHref($_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . $port . $_SERVER["REQUEST_URI"]);
                $method->loadDescribe();
                $method->loadLinks($methods);
                echo Api::format($method->run(), $method->getLinks());
                return;
        }

    }

    public function setRequestScheme()
    {
        if (!empty($_SERVER["REQUEST_SCHEME"])) {
            return;
        }
        if (!empty($_SERVER["HTTPS"])) {
            $_SERVER["REQUEST_SCHEME"] = 'https';
        } else {
            $_SERVER["REQUEST_SCHEME"] = 'http';
        }
    }

    public static function populatePutOrDeleteRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            parse_str(file_get_contents("php://input"), $postVars);
            Api::$_PUT = $postVars;
        }
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
            parse_str(file_get_contents("php://input"), $postVars);
            Api::$_DELETE = $postVars;
        }
    }

    public static function format($content, $links = null)
    {
        if ($content === null) {
            return;
        }
        if (is_object($content)) {
            $serializer = JMS\Serializer\SerializerBuilder::create()->build();
            $jsonContent = $serializer->serialize($content, 'json');
            $content = json_decode($jsonContent);
        }
        if (!is_array($content)) {
            $content = array('data' => $content);
        }
        if (!empty($links)) {
            $content['links'] = $links;
        }
        if (isset($_GET['yaml']) || isset($_GET['yml'])) {
            return Yaml::dump($content);
        }
        if (isset($_GET['html'])) {
            return '<html><body><pre><code>' . Yaml::dump($content) . '</code></pre></body>';
        }
        //use this for php version higher or equals than 5.4
        if (PHP_VERSION_ID >= 50400) {
            return json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        return json_encode($content);
    }

    public static function error($message)
    {
        header('HTTP/1.1 500 Internal Server Error');
        return array('message' => $message);
    }

    /**
     * @return mixed
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @Required
     * @param mixed $methods
     */
    public function setMethods(array $methods)
    {
        $this->methods = $methods;
        foreach ($this->methods as $methodName => $method) {
            $method->setMethodName($methodName);
            $this->getAnnotations($method);
        }
    }

    public function getAnnotations($method)
    {
        $annotations = new AnnotationsArhframe();
        $result = $annotations->getAnnotationsObjects(get_class($method));
        $this->getMethodHttpAnnotation($method, $result);
        $this->getRouteAnnotation($method, $result);
        $this->getDescribeAnnotation($method, $result);
        $this->getLinksAnnotation($method, $result);

    }

    public function getMethodHttpAnnotation($method, $result)
    {
        if (empty($result['MethodHttp'])) {
            return;
        }
        $methodHttp = $result['MethodHttp'];
        $method->setMethodName($methodHttp->get(0));
    }

    public function getRouteAnnotation($method, $result)
    {
        if (empty($result['Route'])) {
            return;
        }
        $route = $result['Route'];
        $method->setRoute($route->get(0));
    }

    public function getDescribeAnnotation($method, $result)
    {
        if (empty($result['Describe'])) {
            return;
        }
        $describe = $result['Describe'];
        $method->setDescribe($describe->getAllDescribes());
    }

    public function getLinksAnnotation($method, $result)
    {
        if (empty($result['Links'])) {
            return;
        }
        $describe = $result['Links'];
        $links = array();
        foreach ($describe->getAllLinks() as $link => $values) {
            $links[$link] = explode('|', $values);
        }
        $method->setLinks($links);
    }

    public function addMethod($methodName, AbstractMethod $method)
    {
        $this->methods[$methodName] = $method;
        $this->getAnnotations($method);
    }

    public function setNoScriptName($noScriptName)
    {
        if ($noScriptName) {
            Api::$NO_SCRIPT_NAME = true;
        }
    }

    /**
     * @Required
     * @param array $links
     */
    public function setLinksEndpoint($links)
    {
        $this->links = $links;
    }
}
