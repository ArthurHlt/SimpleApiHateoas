<?php
namespace Arhframe\Annotations;
/**
 *
 * Author: Arthur Halet
 * Date: 09/07/2014
 */
class DescribeAnnotation extends \Arhframe\Annotations\AnnotationArhframe
{
    public function getAllDescribes()
    {
        return $this->data;
    }
} 