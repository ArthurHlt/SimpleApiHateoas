<?php
namespace Arhframe\Annotations;
/**
 *
 * Author: Arthur Halet
 * Date: 09/07/2014
 */
class MethodHttpAnnotation extends \Arhframe\Annotations\AnnotationArhframe
{
    public function set($key, $value)
    {
        if ($value != 'GET' && $value != 'POST' && $value != 'PUT' && $value != 'DELETE') {
            $value = 'GET';
        }
        $this->data[$key] = $value;
    }
} 