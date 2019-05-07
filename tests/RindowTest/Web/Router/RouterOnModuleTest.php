<?php
namespace RindowTest\Web\Router\RouterOnModuleTest;

use PHPUnit\Framework\TestCase;
use Rindow\Container\ModuleManager;
use Interop\Lenient\Web\Router\Annotation\Controller;
use Interop\Lenient\Web\Router\Annotation\RequestMapping;


class Test extends TestCase
{
    static $RINDOW_TEST_RESOURCES;
    public static function setUpBeforeClass()
    {
        self::$RINDOW_TEST_RESOURCES = __DIR__.'/../../../resources';
    }

    public function setUp()
    {
    }

    public function getConfig(array $options=array())
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Web\Router\Module' => true,
                ),
                'annotation_manager' => true,
                'enableCache' => false,
            ),
            'web' => array(
                'router' => array(
                    'builders' => array(
                        'annotation' => array(
                            'controller_paths' => array(
                                self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/Router/Dir3' => true,
                            ),
                        ),
                    ),
                ),
            ),
        );
        $config = array_replace_recursive($config, $options);
        return $config;
    }

    public function test()
    {
        $mm = new ModuleManager($this->getConfig());
        $router = $mm->getServiceLocator()->get('Rindow\Web\Router\DefaultRouter');

        $this->assertEquals(array(
                'AcmeTest\Web\Router\Controller\TestControllerWithAlias::foo' => array(
                    'path' => '/foo',
                    'namespace' => 'AcmeTest\Web\Router\Controller',
                    'handler'=> array(
                        'class' => 'AcmeTest\Web\Router\Controller\TestControllerWithAlias',
                        'method'=> 'foo',
                    ),
                    'type'=>'literal',
                ),
            ),
            $router->getRoutingTable()
        );
    }
}
