<?php

/**
 * Author: Arthur Halet
 * Date: 09/07/2014
 */
namespace ArthurH\SimpleApiHateoas\Method;

use Arhframe\Annotations\DescribeAnnotation;
use Arhframe\Annotations\LinksAnnotation;
use ArthurH\SimpleApiHateoas\Api;

/**
 *
 * Class AbstractMethod
 * @package OrangeOpenSource\SimpleApiFullRest\Method
 */
abstract class  AbstractMethod
{
    protected $route = null;

    protected $info;
    protected $requestMethod = 'GET';
    protected $links = array();
    private $href;
    private $methodName;
    private $describe;


    abstract public function run();

    /**
     * @return mixed
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param mixed $info
     */
    public function setInfo($info)
    {
        $this->info = $info;
    }

    /**
     * @return mixed
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * @Required
     * @param mixed $method
     */
    public function setRequestMethod($method)
    {
        $this->requestMethod = $method;
    }

    /**
     * @return mixed
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @param array $links
     */
    public function setLinks($links)
    {
        if (!is_array($links)) {
            $links = array($links);
        }
        $this->links = $links;
    }

    public function loadLinks($methods)
    {
        if (empty($this->links)) {
            return;
        }
        $links = $this->links;

        $this->links = array();
        $this->links[] = array('rel' => 'self', 'href' => $this->getHref());
        foreach ($links as $methodName => $values) {
            $methodNameOnValues = false;
            if (is_numeric($methodName)) {
                $methodName = $values;
                $methodNameOnValues = true;
            }
            if (empty($methods[$methodName]) && !($methods[$methodName] instanceof AbstractMethod)) {
                continue;
            }

            $link = array();
            $methods[$methodName]->loadDescribe();
            if (!$methodNameOnValues) {
                $methods[$methodName]->loadHref($this->getValuesFromDescribe($values), $methodName);
            } else {
                $methods[$methodName]->loadHref(null, $methodName);
            }
            $link['rel'] = $methodName;
            $link['method'] = $methods[$methodName]->getRequestMethod();
            $link['href'] = $methods[$methodName]->getHref();
            $this->links[] = $link;
        }

    }

    /**
     * @return mixed
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * @param mixed $href
     */
    public function setHref($href)
    {
        $this->href = $href;
    }

    private function getValuesFromDescribe($valuesName)
    {
        if (!is_array($valuesName)) {
            $valuesName = array($valuesName);
        }
        $toReturn = array();
        foreach ($valuesName as $valueName) {
            if (!isset($this->describe[$valueName])) {
                continue;
            }
            $toReturn[$valueName] = $this->describe[$valueName]['value'];
        }
        return $toReturn;
    }

    /**
     *
     * @param mixed $describe
     */
    public function setDescribe($describe)
    {
        $this->describe = $describe;

    }

    public function loadDescribe()
    {
        if (empty($this->describe)) {
            return;
        }
        $describes = $this->describe;
        $this->describe = array();
        foreach ($describes as $value => $type) {
            if (is_array($type)) {
                continue;
            }
            $type = strtoupper($type);
            if ($type == 'GET') {
                $this->describe[$value]['value'] = $_GET[$value];
            } else if ($type == 'INFO') {
                $this->describe[$value]['value'] = $this->info[$value];
            } else if ($type == 'POST') {
                $this->describe[$value]['value'] = $_POST[$value];
            } else if ($type == 'DELETE') {
                $this->describe[$value]['value'] = Api::$_DELETE[$value];
            } else if ($type == 'PUT') {
                $this->describe[$value]['value'] = Api::$_PUT[$value];
            } else {
                continue;
            }
            $this->describe[$value]['type'] = $type;
        }
    }

    /**
     * @param mixed $href
     */
    public function loadHref($values, $methodName)
    {
        $port = null;
        if ($_SERVER['SERVER_PORT'] != 80) {
            $port = ':' . $_SERVER['SERVER_PORT'];
        }
        $scriptName = $_SERVER["SCRIPT_NAME"];
        if (Api::$NO_SCRIPT_NAME) {
            $scriptName = dirname($scriptName);
            $scriptName = ($scriptName == '/') ? "" : $scriptName;
        }
        $this->href = $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . $port . $scriptName;
        $this->href .= '/' . Api::$APIBASEROUTE . '/' . $methodName . $this->getRoute();
        $gets = null;
        if ($values === null) {
            return;
        }
        foreach ($values as $valueName => $value) {

            if (empty($this->describe[$valueName])) {
                continue;
            }
            if ($this->describe[$valueName]['type'] != 'GET' && $this->describe[$valueName]['type'] != 'INFO') {
                continue;
            }
            if ($this->describe[$valueName]['type'] == 'GET') {
                $gets[$valueName] = $value;
            }
            if ($this->describe[$valueName]['type'] == 'INFO') {
                $this->href = preg_replace('#\{' . preg_quote($valueName) . '\}#i', $value, $this->href);
            }
        }
        if (!empty($gets)) {
            $this->href .= '?' . http_build_query($gets);
        }
    }

    /**
     * @return mixed
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param mixed $route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     * @return mixed
     */
    public function getMethodName()
    {
        return $this->methodName;
    }

    /**
     * @param mixed $methodName
     */
    public function setMethodName($methodName)
    {
        $this->methodName = $methodName;

    }


}
