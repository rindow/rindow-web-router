<?php
namespace Rindow\Web\Router\Annotation;

use Rindow\Web\Router\AnnotationInterface;
use Rindow\Stdlib\Entity\AbstractPropertyAccess;

/**
 * @Annotation
 * @Target({ TYPE })
 */
class Controller extends AbstractPropertyAccess implements AnnotationInterface
{
    /**
     * @var vender options.
     */
    public $value;
}