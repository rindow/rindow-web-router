<?php
namespace Rindow\Web\Router\RoutingTableBuilder;

use Rindow\Web\Router\Annotation\Controller;
use Rindow\Web\Router\Annotation\RequestMapping;
use Rindow\Web\Router\RoutingTableBuilderInterface;
use Rindow\Annotation\NameSpaceExtractor;
use Rindow\Annotation\AnnotationManager;
use Interop\Lenient\Annotation\AnnotationReader;
use Rindow\Web\Router\Exception;
use Rindow\Stdlib\FileUtil\Dir;
use Rindow\Stdlib\FileUtil\Exception\DomainException as FileUtilException;
use ReflectionClass;

class Annotation implements RoutingTableBuilderInterface
{
    //const DEFAULT_ANNOTATION_READER = 'Rindow\Annotation\AnnotationManager';
    protected $config;
    protected $annotationReader;
    protected $serviceLocator;
    protected $routes = array();
    protected $debug;
    protected $logger;

    public function __construct($serviceLocator=null,AnnotationReader $annotationReader=null)
    {
        $this->serviceLocator = $serviceLocator;
        $this->annotationReader = $annotationReader;
    }

    public function setConfig(array $config=null)
    {
        $this->config = $config;
    }

    public function setServiceLocator(/*ServiceLocator*/$serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }

    public function setAnnotationReader(AnnotationReader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
        return $this;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    public function getAnnotationReader()
    {
        if($this->annotationReader==null)
            throw new Exception\DomainException('AnnotationReader is not specified.');
        return $this->annotationReader;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function build(array $paths=null)
    {
        if($paths==null) {
            if(!isset($this->config['controller_paths']))
                throw new Exception\DomainException('a path of annotaion driven controllers is not specified.');
            $paths = $this->config['controller_paths'];
        }

        $dirUtil = new Dir();
        foreach ($paths as $path => $switch) {
            if($switch) {
                if($this->debug && $this->logger) {
                    $this->logger->debug('clawl controller',array('path'=>$path));
                }
                try {
                    $dirUtil->clawl($path,array($this,'parseControllerFile'));
                } catch(FileUtilException $e) {
                    throw new Exception\DomainException($e->getMessage(),$e->getCode(),$e);
                }
            }
        }
        return $this;
    }

    public function parseControllerFile($filename)
    {
        require_once $filename;
        $parser = new NameSpaceExtractor($filename);
        $classes = $parser->getAllClass();
        if($classes==null)
            return $this;
        foreach($classes as $class) {
            $this->parseControllerClass($class);
        }
        return $this;
    }

    public function parseControllerClass($class)
    {
        $isController = false;
        $classRef = new ReflectionClass($class);
        $annos = $this->getAnnotationReader()->getClassAnnotations($classRef);
        foreach ($annos as $anno) {
            if($anno instanceof Controller) {
                $isController = true;
                if($anno->value!==null)
                    $controllers[$class]['vendorOptions'] = $anno->value;
            } else if($anno instanceof RequestMapping) {
                $controllers[$class]['RequestMapping'] = $anno;
            }
        }
        if(!$isController) {
            if($this->debug && $this->logger) {
                $this->logger->debug('this is not conntroller',array('class'=>$class));
            }
            return $this;
        }

        if($this->debug && $this->logger) {
            $this->logger->debug('found conntroller',array('class'=>$class));
        }
        $controllers[$class]['ClassName'] = $class;
        $this->extractRoutes($classRef,$controllers[$class]);
        return $this;
    }

    protected function extractRoutes($classRef,$controller)
    {
        $count = 0;
        $annotationManager = $this->getAnnotationReader();
        foreach($classRef->getMethods() as $ref) {
            $annos = $annotationManager->getMethodAnnotations($ref);
            foreach($annos as $anno) {
                if($anno instanceof RequestMapping) {
                    $route = array();
                    $path = '';
                    if(isset($controller['RequestMapping']))
                        $path .= rtrim($controller['RequestMapping']->value,'/');
                    if(!isset($anno->value)) {
                        $filename = $ref->getFileName();
                        $lineNumber = $ref->getStartLine();
                        throw new Exception\DomainException('a mapping path is not specified.: '.$filename.'('.$lineNumber.')');
                    }
                    $path = rtrim($path . '/' . trim($anno->value,'/'),'/');
                    if($path=='')
                        $path='/';
                    $route['path'] = $path;
                    if(isset($anno->method))
                        $route['conditions']['method'] = strtoupper($anno->method);
                    if(isset($anno->headers))
                        $route['conditions']['headers'] = $anno->headers;

                    if($anno->ns)
                        $route['namespace'] = $anno->ns;
                    else if(isset($controller['RequestMapping']->ns))
                        $route['namespace'] = $controller['RequestMapping']->ns;
                    else
                        $route['namespace'] = $classRef->getNamespaceName();

                    $route['handler']['class'] = $controller['ClassName'];
                    $route['handler']['method'] = $ref->name;
                    if($anno->view)
                        $route['view'] = $anno->view;
                    if(isset($anno->parameters)) {
                        $route['type'] = 'segment';
                        $route['parameters'] = $anno->parameters;
                    } else {
                        $route['type'] = 'literal';
                    }
                    if($anno->middlewares) {
                        $middlewares = array();
                        foreach ($anno->middlewares as $key => $value) {
                            $middlewares[$value] = (-1) - $key;
                        }
                        $route['middlewares'] = $middlewares;
                    }
                    if($anno->options) {
                        // Middleware arguments
                        $route['options'] = $anno->options;
                    }

                    if($anno->name)
                        $routeName = $route['namespace'].'\\'.$anno->name;
                    else
                        $routeName = $controller['ClassName'].'::'.$ref->name;
                    if(isset($this->routes[$routeName]))
                        throw new Exception\DomainException('duplicate route name "'.$routeName.'": '.$ref->getFileName().'('.$ref->getStartLine().')');
                    if(isset($controller['vendorOptions']))
                        $route['vendorOptions'] = $controller['vendorOptions'];
                    $this->routes[$routeName] = $route;
                    $count++;
                }
            }
        }
        if($count==0) {
            $filename = $classRef->getFileName();
            $lineNumber = $classRef->getStartLine();
            throw new Exception\DomainException('there is no action-method in a controller.: '.$filename.'('.$lineNumber.')');
        }
        return $this;
    }

    public function compileRoute($routeStr)
    {

    }
}

