<?php
namespace Rindow\Web\Router;

interface RoutingTableBuilderInterface
{
    public function setConfig(array $config=null);
    public function build(array $paths=null);
    public function getRoutes();
}
