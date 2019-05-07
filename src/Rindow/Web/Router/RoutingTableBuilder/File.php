<?php
namespace Rindow\Web\Router\RoutingTableBuilder;

use Rindow\Web\Router\RoutingTableBuilderInterface;
use Rindow\Web\Router\Exception;

class File implements RoutingTableBuilderInterface
{
    protected $loaderAliases = array(
        'yaml' => 'Rindow\\Module\\Yaml\\Yaml::parseFile',
    );
    protected $config;
    protected $routes = array();

    public function setConfig(array $config=null)
    {
        $this->config = $config;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function setLoaderAliases(array $loaderAliases=null)
    {
        if($loaderAliases==null)
            return;
        $this->loaderAliases = $loaderAliases;
    }

    public function build(array $typeAndfiles=null)
    {
        if($typeAndfiles==null) {
            if(!isset($this->config['config_files']))
                throw new Exception\DomainException('a path of configuration file is not specified.');
            $typeAndfiles = $this->config['config_files'];
        }

        foreach ($typeAndfiles as $type => $files) {
            foreach ($files as $ns => $file) {
                if(!file_exists($file))
                    throw new Exception\DomainException('mvc routing configuration file is not exist.: '. $file);
                if($type==='php') {
                    $config = include $file;
                } else {
                    if(isset($this->loaderAliases[$type]))
                        $loader = $this->loaderAliases[$type];
                    else
                        $loader = $type;
                    if(!is_callable($loader))
                        throw new Exception\DomainException('a loader is not found.: '. $type);
                    $config = call_user_func($loader,$file);
                }
                if(!is_array($config))
                    throw new Exception\DomainException('invalid return type or loading error.: '. $file);
                if($ns!='direct')
                    $config = $this->fillNamespace($ns, $config);
                $this->routes = array_merge_recursive($this->routes,$config);
            }
        }
        return $this;
    }

    protected function fillNamespace($ns, $config)
    {
        $routes = array();
        foreach ($config as $name => $route) {
            if(!isset($route['namespace']))
                $route['namespace'] = $ns;
            $routes[$ns.'\\'.$name] = $route;
        }
        return $routes;
    }
}
