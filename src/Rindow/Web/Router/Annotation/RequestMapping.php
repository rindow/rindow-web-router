<?php
namespace Rindow\Web\Router\Annotation;

use Rindow\Web\Router\AnnotationInterface;
use Rindow\Stdlib\Entity\AbstractPropertyAccess;

/**
 * @Annotation
 * @Target({ TYPE,METHOD })
 */
class RequestMapping extends AbstractPropertyAccess implements AnnotationInterface
{
    /**
     * @var string Uri path
     */
    public $value;

    /**
     * @var string Method
     */
    public $method;

    /**
     * @var array<$name=$value> Header name and value.
     */
    public $headers;

    /**
     * @var array<string> Middleware name
     */
    public $middlewares;

    /**
     * @var map<string> Middleware option arguments
     */
    public $options;

    /**
     * @var string View name
     */
    public $view;

    /**
     * @var string Namespace
     */
    public $ns;

    /**
     * @var array<string> parameters in url
     */
    public $parameters;

    /**
     * @var string route name
     */
    public $name;
}