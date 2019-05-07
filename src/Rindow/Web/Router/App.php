<?php
namespace Rindow\Web\Router;

use Rindow\Web\Http\Message\ServerRequestFactory;
use Rindow\Web\Http\Message\Response;

class App
{
    protected static $methods = array(
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

    protected $router;
    protected $request;
    protected $response;
    protected $urlGenerator;
    protected $serviceLocator;

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function setResponse($response)
    {
        $this->response = $response;
    }

    public function getRequest()
    {
        if($this->request)
            return $this->request;
        return $this->request = ServerRequestFactory::fromGlobals();
    }

    public function setUrlGenerator($urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function getUrlGenerator()
    {
        return $this->urlGenerator;
    }

    public function getPath()
    {
        $urlGenerator = $this->getUrlGenerator();
        if($urlGenerator) {
            $urlGenerator->setRequest($this->getRequest());
            return $urlGenerator->getPath();
        }
        return $this->getRequest()->getUri()->getPath();
    }

    public function getResponse()
    {
        if($this->response)
            return $this->response;
        $body = new Stream(fopen('php://output', 'w'));
        return $this->response = new Response(null,null,$body);
    }

    public function getRouter()
    {
        if($this->router)
            return $this->router;
        return $this->router = new Router();
    }

    public function match()
    {
        $request = $this->getRequest();
        $path = $this->getPath();
        $router = $this->getRouter();
        return $router->match($request,$path);
    }

    public function dispatch($info)
    {
        $handler = $info['route']['handler']['callable'];
        if(!is_callable($handler)) {
            if(is_string($handler)) {
                $container = $this->getServiceLocator();
                if($container==null)
                    throw new Exception\DomainException('service locator is not found to resolve handler.:'.$handler);
                $handler = $container->get($handler);
                if(!$handler)
                    throw new Exception\DomainException('the handler can not be resolved.:'.$handler);
            } else {
                throw new Exception\DomainException('invalid handler in route information:'.$info['route']['name']);
            }
        }
        $response = call_user_func(
            $handler,
            $this->getRequest(),
            $this->getResponse(),
            $info['params']);
        return $response;
    }

    public function run()
    {
        $info = $this->match();
        if(!$info)
            return null;
        return $this->dispatch($info);
    }

    public function __call($method,$args)
    {
        // Method
        $method = strtoupper($method);
        if(!array_key_exists($method, self::$methods))
            throw new Exception\InvalidArgumentException('Unknown HTTP method: '.$method);

        // Path with parameter
        if(!isset($args[0]))
            throw new Exception\InvalidArgumentException('path is not specified.');
        if(is_string($args[0])) {
            $path = $args[0];
            $params = array();
        } elseif (is_array($args[0])) {
            $params = $args[0];
            $path = array_shift($params);
            if(!is_string($path))
                throw new Exception\InvalidArgumentException('path must be string.');
            foreach ($params as $param) {
                if(!is_string($param))
                    throw new Exception\InvalidArgumentException('each paramater must be string.');
            }
        } else {
            throw new Exception\InvalidArgumentException('path must be string or array of string path with parameter.');
        }

        // handler
        if(!isset($args[1]))
            throw new Exception\InvalidArgumentException('handler is not specified.');
        if(is_callable($args[1])) {
            $handler = $args[1];
        } elseif(is_string($args[1])) {
            $handler = $args[1];
        } elseif(is_array($args[1])) {
            $handler = $args[1];
            foreach($handler as $function) {
                if(!is_callable($function) && !is_string($function))
                    throw new Exception\InvalidArgumentException('each handler must be callable or string of component name.');
            } 
        } else {
            throw new Exception\InvalidArgumentException('each handler must be callable or string of component name or array of them.');
        }

        // route name
        $name = null;
        if(isset($args[2])) {
            if(!is_string($args[2]))
                throw new Exception\InvalidArgumentException('function is not specified.');
            $name = $args[2];
        }

        $router = $this->getRouter();
        $router->addRoute($name,$path,$handler,$params,$method);
    }
}
