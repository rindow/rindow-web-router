<?php
namespace AcmeTest\Web\Router;

use Rindow\Web\Router\Annotation\Controller;
use Rindow\Web\Router\Annotation\RequestMapping;

/**
 * @Controller
 */
class DuplicateRouteName
{
    /**
     * @RequestMapping(value="/foo",name="foo")
     */
    public function foo($context)
    {
        return;
    }

    /**
     * @RequestMapping(value="/foo2",name="foo")
     */
    public function foo2($context)
    {
        return;
    }
}