<?php
namespace AcmeTest\Web\Router\Controller;

use Interop\Lenient\Web\Router\Annotation\Controller;
use Interop\Lenient\Web\Router\Annotation\RequestMapping;

/**
 * @Controller
 */
class TestControllerWithAlias
{
    /**
     * @RequestMapping(value="/foo")
     */
    public function foo($context)
    {
        return;
    }
}