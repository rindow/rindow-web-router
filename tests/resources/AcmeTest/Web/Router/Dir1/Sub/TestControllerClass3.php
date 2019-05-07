<?php
namespace AcmeTest\Web\Router\Controller;

use Rindow\Web\Router\Annotation\Controller;
use Rindow\Web\Router\Annotation\RequestMapping;

/**
 * @Controller
 */
class TestControllerClass3
{
    /**
     * @RequestMapping(value="/foo")
     */
    public function foo($context)
    {
        return;
    }
}