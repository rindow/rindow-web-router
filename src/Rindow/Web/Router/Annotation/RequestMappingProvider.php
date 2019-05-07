<?php
namespace Rindow\Web\Router\Annotation;

use Rindow\Annotation\AnnotationProviderInterface;
use Rindow\Web\Router\Exception;

class RequestMappingProvider implements AnnotationProviderInterface
{
    protected static $validMethods = array(
        'CONNECT' => true,
        'DELETE' => true,
        'GET' => true,
        'HEAD' => true,
        'OPTIONS' => true,
        'PATCH' => true,
        'POST' => true,
        'PUT' => true,
        'TRACE' => true,
    );

    public function getJoinPoints()
    {
        return array(
            'initalize' => array(
                AnnotationProviderInterface::EVENT_CREATED,
            ),
        );
    }

    public function initalize($event)
    {
        $args = $event->getArgs();
        $annotationClassName = $args['annotationname'];
        $metadata = $args['metadata'];
        $location = $args['location'];

        if($metadata->value==null && $location['target']=='METHOD') {
            throw new Exception\InvalidArgumentException('@RequestMapping must include "value" property as a url path.:'.$location['uri'].': '.$location['filename'].'('.$location['linenumber'].')');
        }
        if($metadata->parameters!=null && !is_array($metadata->parameters)) {
            throw new Exception\InvalidArgumentException('"parameters" property must be array of variable name as parameter of url in @RequestMapping.:'.$location['uri'].': '.$location['filename'].'('.$location['linenumber'].')');
        }
        if($metadata->method!=null) {
            if(!is_string($metadata->method))
                throw new Exception\InvalidArgumentException('"method" property must be string as a namespace in @RequestMapping.:'.$location['uri'].': '.$location['filename'].'('.$location['linenumber'].')');
            if(!isset(self::$validMethods[strtoupper($metadata->method)]))
                throw new Exception\InvalidArgumentException('"method" property is invalid in @RequestMapping.:'.$location['uri'].': '.$location['filename'].'('.$location['linenumber'].')');
        }
        if($metadata->headers!=null && !is_array($metadata->headers)) {
            throw new Exception\InvalidArgumentException('"headers" property must be array of set of header name and header value as header condition in @RequestMapping.:'.$location['uri'].': '.$location['filename'].'('.$location['linenumber'].')');
        }
        if($metadata->middlewares!=null && !is_array($metadata->middlewares)) {
            throw new Exception\InvalidArgumentException('"middlewares" property must be array of middleware name in @RequestMapping.:'.$location['uri'].': '.$location['filename'].'('.$location['linenumber'].')');
        }
        if($metadata->view!=null && !is_string($metadata->view)) {
            throw new Exception\InvalidArgumentException('"view" property must be string as a template name of view in @RequestMapping.:'.$location['uri'].': '.$location['filename'].'('.$location['linenumber'].')');
        }
        if($metadata->ns!=null && !is_string($metadata->ns)) {
            throw new Exception\InvalidArgumentException('"ns" property must be string as a namespace in @RequestMapping.:'.$location['uri'].': '.$location['filename'].'('.$location['linenumber'].')');
        }
    }

    public function invoke($event)
    {
    }
}
