<?php
/**
 * Author: Arthur Halet
 * Date: 09/07/2014
 */


namespace ArthurH\SimpleApiHateoas\Method;


use ArthurH\SimpleApiHateoas\Api;
use Symfony\Component\Yaml\Yaml;

class ApiShowMethod extends AbstractMethod
{
    private $methods;

    public function run()
    {
        return array('specversion' => '1.0');
    }


}