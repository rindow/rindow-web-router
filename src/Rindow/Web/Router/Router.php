<?php
namespace Rindow\Web\Router;

use Rindow\Web\Router\Exception;
use Rindow\Web\Router\Parser\SegmentParser;
use Rindow\Web\Router\Parser\LiteralParser;
use Rindow\Stdlib\Cache\ConfigCache\ConfigCacheFactory;
/*use Rindow\Container\ServiceLocator;*/

class Router
{
    const STATUS_PAGE_NOT_FOUND     = 403;
    const STATUS_METHOD_NOT_ALLOWED = 405;
    const STATUS_NOT_ACCEPTABLE     = 406;

    protected static $builderAliases = array(
        'annotation' => 'Rindow\\Web\\Router\\RoutingTableBuilder\\Annotation',
        'file'       => 'Rindow\\Web\\Router\\RoutingTableBuilder\\File',
    );

    protected static $routerAliases = array(
        'segment' => 'Rindow\\Web\\Router\\Parser\\SegmentParser',
        'literal' => 'Rindow\\Web\\Router\\Parser\\LiteralParser',
    );

    protected $configCacheFactory;
    protected $cache;
    protected $routingTable;
    protected $config;
    protected $serviceLocator;
    protected $annotationReader;
    protected $doNotThrowPageNotFound = false;
    protected $debug;
    protected $logger;

    public function __construct(
        $config=null,
        /* ServiceLocator */$serviceLocator=null,
        /* AnnotationReader */$annotationReader=null,
        $configCacheFactory=null)
    {
        $this->configCacheFactory = $configCacheFactory;
        if($config)
            $this->setConfig($config);
        if($serviceLocator)
            $this->setServiceLocator($serviceLocator);
        $this->annotationReader = $annotationReader;
    }

    public function setServiceLocator($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function setConfigCacheFactory($configCacheFactory)
    {
        $this->configCacheFactory = $configCacheFactory;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    public function setDoNotThrowPageNotFound($doNotThrowPageNotFound)
    {
        $this->doNotThrowPageNotFound = $doNotThrowPageNotFound;
    }

    protected function getCache()
    {
        if($this->cache)
            return $this->cache;
        if($this->configCacheFactory==null)
            $this->configCacheFactory = new ConfigCacheFactory(array('enableCache'=>false));
        $this->cache = $this->configCacheFactory->create(__CLASS__.'/routes');
        return $this->cache;
    }

    public function match($request,$path=null)
    {
        try {
            if($path===null)
                $path = $request->getUri()->getPath();
            $route = $this->matchRoute($path, $request);
            $params = $this->parseParameter($path, $route);
            $params = $this->setDefaultParameter($params, $route);
            return array('route'=>$route, 'params'=>$params);
        } catch(Exception\PageNotFoundException $e) {
            if(!$this->doNotThrowPageNotFound)
                throw $e;
            $routeInfo = array();
            if(isset($route))
                $routeInfo['route'] = $route;
            if(isset($params))
                $routeInfo['params'] = $params;
            $routeInfo['error'] = array('status'=>$e->getCode(), 'reason'=>$e->getMessage());
            return $routeInfo;
        }
    }

    public function getRoutingTable()
    {
        if($this->routingTable)
            return $this->routingTable;
        $cache = $this->getCache();
        $this->routingTable = $cache->getEx('routes',array($this,'generateRoutingTable'));
        if($this->debug && $this->logger) {
            $this->logger->debug('routingtable',$this->routingTable);
        }
        return $this->routingTable;
    }

    public function generateRoutingTable()
    {
        if($this->config==null)
            throw new Exception\DomainException('configuration is not found.');

        $routes = array();
        if(isset($this->config['builder_aliases'])) {
            $builderAliases = $this->config['builder_aliases'];
        } else {
            $builderAliases = self::$builderAliases;
        }

        if(isset($this->config['builders'])) {
            foreach($this->config['builders'] as $builderClass => $builderConfig) {
                if(isset($builderAliases[$builderClass]))
                    $builderClass = $builderAliases[$builderClass];
                $builder = $this->getBuilderInstance($builderClass,$builderConfig);
                $routes = array_merge($routes,$builder->build()->getRoutes());
            }
        }
        if(isset($this->config['routes']))
            $routes = array_merge($routes,$this->config['routes']);
        return $routes;
    }

    protected function getBuilderInstance($builderClass,$builderConfig)
    {
        if($this->serviceLocator) {
            $builder = $this->serviceLocator->get($builderClass);//,array('scope'=>'prototype'));
        } else {
            if(!class_exists($builderClass))
                throw new Exception\DomainException('routing table builder class is not found: '.$builderClass);
            $builder = new $builderClass($this->serviceLocator);
            if(method_exists($builder, 'setAnnotationReader'))
                $builder->setAnnotationReader($this->annotationReader);
        }
        if(!($builder instanceof RoutingTableBuilderInterface))
            throw new Exception\DomainException('routing table builder class must implements "RoutingTableBuilderInterface": '.$builderClass);
        $builder->setConfig($builderConfig);
        return $builder;
    }

    public function parseParameter($path, $route)
    {
        $path = substr($path, strlen($this->getPathPrefix($route)));
        $routerClass = $route['type'];
        if(isset(self::$routerAliases[$routerClass]))
            $routerClass = self::$routerAliases[$routerClass];
        if(!class_exists($routerClass))
            throw new Exception\DomainException('Unkown route type "'.$route['type'].'" in route "'.$route['name'].'"');
        return $routerClass::parse($path, $route);
    }

    public function matchRoute($path,$request)
    {
        $method  = $request->getMethod();
        $name = null;
        $maxPath = '';
        $matchedPath = null;
        $matchedMethod = null;
        foreach($this->getRoutingTable() as $idx => $info) {
            if(!isset($info['path']))
                throw new Exception\DomainException('"path" is not found in the route "'.$idx.'"');
            $routePath = $this->getPathPrefix($info).$info['path'];
            if(strpos($path, $routePath)!==0)
                continue;
            if(strlen($maxPath) >= strlen($routePath))
                continue;
            $matchedPath = $routePath;
            if(isset($info['conditions']['method']) && $info['conditions']['method']!=$method)
                continue;
            $matchedMethod = $method;
            if(isset($info['conditions']['headers']) && !$this->matchHeaders($info['conditions']['headers'],$request))
                continue;
            $maxPath = $routePath;
            $name = $idx;
        }
        if($name == null)
            return $this->getNotFound($matchedPath,$matchedMethod);

        $routingTable = $this->getRoutingTable();
        $route = $routingTable[$name];
        $route['name'] = $name;
        return $route;
    }

    protected function getPathPrefix($route)
    {
        if(!isset($route['namespace']) ||
            !isset($this->config['pathPrefixes'][$route['namespace']])) {
            return '';
        }
        return $this->config['pathPrefixes'][$route['namespace']];
    }

    public function matchHeaders($conditionHeaders,$request)
    {
        $match = true;
        foreach($conditionHeaders as $key => $value) {
            if(!$request->hasHeader($key)) {
                $match = false;
                break;
            }
            $headerValue = $request->getHeaderLine($key);
            if(strpos($headerValue,$value)===false) {
                $match = false;
                break;
            }
        }
        return $match;
    }

    public function getNotFound($matchedPath,$matchedMethod)
    {
        if(!$matchedPath)
            throw new Exception\PageNotFoundException('Path Not Found.',self::STATUS_PAGE_NOT_FOUND);
        if(!$matchedMethod)
            throw new Exception\PageNotFoundException('Method Not Allowed at "'.$matchedPath.'".',self::STATUS_METHOD_NOT_ALLOWED);
        throw new Exception\PageNotFoundException('Header Not Acceptable at "'.$matchedPath.'".',self::STATUS_NOT_ACCEPTABLE);
    }

    public function setDefaultParameter($params, $route)
    {
        if(!isset($route['defaults']))
            return $params;
        return array_merge($route['defaults'], $params);
    }

    public function assemble($routeName,array $params=null,array $options=null)
    {
        $routingTable = $this->getRoutingTable();
        if(!isset($routingTable[$routeName]))
            throw new Exception\DomainException('route is not found:'.$routeName);
        $route = $routingTable[$routeName];

        $routerClass = $route['type'];
        if(isset(self::$routerAliases[$routerClass]))
            $routerClass = self::$routerAliases[$routerClass];
        if(!class_exists($routerClass))
            throw new Exception\DomainException('Unkown route type "'.$route['type'].'" in route "'.$routeName.'"');
        if($params==null)
            $params = array();
        $result = $routerClass::assemble($params, $route);
        return $this->getPathPrefix($route).$result;
    }

    public function addRoute($name,$path,$handler,$params=null,$method=null,array $headers=null,array $defaults=null)
    {
        if($name==null) {
            $name = sha1(mt_rand().$path);
        }
        $route = array();
        $route['path'] = $path;
        if($method)
            $route['conditions']['method'] = $method;
        if($headers)
            $route['conditions']['headers'] = $headers;
        if(empty($params)) {
            $route['type'] = 'literal';
        } else {
            $route['type'] = 'segment';
            $route['parameters'] = $params;
        }
        if($defaults)
            $route['defaults'] = $defaults;
        $route['handler']['callable'] = $handler;
        $this->routingTable[$name] = $route;
    }
}
