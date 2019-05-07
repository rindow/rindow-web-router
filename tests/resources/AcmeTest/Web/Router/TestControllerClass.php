<?php
namespace AcmeTest\Web\Router;

use Rindow\Web\Router\Annotation\Controller;
use Rindow\Web\Router\Annotation\RequestMapping;

/**
 * @Controller
 */
class TestControllerClass
{
    /**
     * @RequestMapping(value="/foo")
     */
    public function foo($context)
    {
        return;
    }
}