<?php
namespace Arhframe\Annotations;
/**
 *
 * Author: Arthur Halet
 * Date: 09/07/2014
 */
class LinksAnnotation extends \Arhframe\Annotations\AnnotationArhframe
{
    public function getAllLinks()
    {
        return $this->data;
    }
} 