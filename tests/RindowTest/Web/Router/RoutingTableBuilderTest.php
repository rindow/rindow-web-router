<?php
namespace RindowTest\Web\Router\RoutingTableBuilderTest;

use PHPUnit\Framework\TestCase;
use Rindow\Annotation\AnnotationManager;
// Test Target Classes
use Rindow\Web\Router\Annotation\Controller;
use Rindow\Web\Router\Annotation\RequestMapping;
use Rindow\Web\Router\RoutingTableBuilder\Annotation as RoutingTableBuilderAnnotation;
use Rindow\Web\Router\RoutingTableBuilder\File as RoutingTableBuilderFile;
use Rindow\Module\Yaml\Yaml;

/**
 * @Controller
 * @RequestMapping(value="/ctl",ns="controllernamespace")
 */
class FooControllerClass
{
    /**
      * @var string  dummy for test
      */
    protected $test;
    /**
     * @return null
     *
     * @RequestMapping(
     *      value="/act",method="POST",headers={Accept="text/html"},
     *      ns="methodnamespace",parameters={"id","mode"},
     *      view="foo/act",name="act",middlewares={"view"}
     * )
     */
    public function actmethod()
    {
        return;
    }
    /**
     * @RequestMapping(
     *      value="/act2"
     * )
     */
    public function actmethod2()
    {
        return;
    }
    public function normalMethod()
    {
        return;
    }
    /**
     * @RequestMapping(
     *      value="/"
     * )
     */
    public function indexAction()
    {
        return;
    }
}
/**
 * @Controller
 */
class Foo2ControllerClass
{
    /**
     * @RequestMapping(
     *      value="/act",method="GET"
     * )
     */
    public function actmethod()
    {
        return;
    }
}
/**
 * @RequestMapping(value="/ctl",ns="controllernamespace")
 */
class Foo3NoneControllerClass
{
    /**
     * @RequestMapping(
     *      value="/act",method="GET"
     * )
     */
    public function actmethod()
    {
        return;
    }
}
/**
 * @Controller
 * @RequestMapping(value="/ctl",ns="controllernamespace")
 */
class Foo4NoneActionClass
{
    public function actmethod()
    {
        return;
    }
}
/**
 * @Controller
 * @RequestMapping(value="/ctl")
 */
class Foo5NonePathClass
{
    /**
     * @RequestMapping(
     *      method="GET"
     * )
     */
    public function actmethod()
    {
        return;
    }
}
/**
 * @Controller
 * @RequestMapping(ns="boo")
 */
class Foo6ClassHasntPathMethodHasPath
{
    /**
     * @RequestMapping(
     *      value="/baz",
     *      method="GET"
     * )
     */
    public function actmethod()
    {
        return;
    }
}

class TestLoader
{
    static public function load($file)
    {
        return Yaml::parseFile($file);
    }
}

class Test extends TestCase
{
    static $RINDOW_TEST_RESOURCES;
    public static function setUpBeforeClass()
    {
        self::$RINDOW_TEST_RESOURCES = __DIR__.'/../../../resources';
        //$loader = Rindow\Loader\Autoloader::factory();
        //$loader->setNameSpace('AcmeTest',self::$RINDOW_TEST_RESOURCES.'/AcmeTest');
    }
    public static function tearDownAfterClass()
    {
    }

    public function setUp()
    {
    }

    public function testNormal()
    {
        $result = array(
            'methodnamespace\act' => array(
                'path' => '/ctl/act',
                'conditions' => array(
                    'method' => 'POST',
                    'headers' => array(
                        'Accept' => 'text/html',
                    ),
                ),
                'namespace' => 'methodnamespace',
                'handler' => array(
                    'class' => __NAMESPACE__.'\\FooControllerClass',
                    'method' => 'actmethod',
                ),
                'view' => 'foo/act',
                'type' => 'segment',
                'parameters' => array('id','mode'),
                'middlewares' => array('view' => -1),
            ),

            __NAMESPACE__.'\\FooControllerClass::actmethod2' => array(
                'path' => '/ctl/act2',
                'namespace' => 'controllernamespace',
                'handler' => array(
                    'class' => __NAMESPACE__.'\\FooControllerClass',
                    'method' => 'actmethod2',
                ),
                'type' => 'literal',
            ),
            __NAMESPACE__.'\\FooControllerClass::indexAction' => array(
                'path' => '/ctl',
                'namespace' => 'controllernamespace',
                'handler' => array(
                    'class' => __NAMESPACE__.'\\FooControllerClass',
                    'method' => 'indexAction',
                ),
                'type' => 'literal',
            ),
        );

        $annotationReader = new AnnotationManager();
        $builder = new RoutingTableBuilderAnnotation(null,$annotationReader);
        $this->assertEquals($result,$builder->parseControllerClass(__NAMESPACE__.'\FooControllerClass')->getRoutes());

        $result = array(
            __NAMESPACE__.'\\Foo2ControllerClass::actmethod' => array(
                'path' => '/act',
                'conditions' => array(
                    'method' => 'GET',
                ),
                'namespace' => __NAMESPACE__.'',
                'handler' => array(
                    'class' => __NAMESPACE__.'\\Foo2ControllerClass',
                    'method' => 'actmethod',
                ),
                'type' => 'literal',
            ),
        );

        $builder = new RoutingTableBuilderAnnotation(null,$annotationReader);
        $this->assertEquals($result,$builder->parseControllerClass(__NAMESPACE__.'\Foo2ControllerClass')->getRoutes());
        $result = array(
        );
        $builder = new RoutingTableBuilderAnnotation(null,$annotationReader);
        $this->assertEquals($result,$builder->parseControllerClass(__NAMESPACE__.'\Foo3NoneControllerClass')->getRoutes());
    }

    /**
     * @expectedException        Rindow\Web\Router\Exception\DomainException
     * @expectedExceptionMessage there is no action-method in a controller.: 
     */
    public function testNoActionMethodInController()
    {
        $result = array(
        );
        $annotationReader = new AnnotationManager();
        $builder = new RoutingTableBuilderAnnotation(null,$annotationReader);
        $this->assertEquals($result,$builder->parseControllerClass(__NAMESPACE__.'\Foo4NoneActionClass')->getRoutes());
    }

    /**
     * @expectedException        Rindow\Web\Router\Exception\InvalidArgumentException
     * @expectedExceptionMessage @RequestMapping must include "value" property as a url path.
     * expectedException        Rindow\Web\Router\Exception\DomainException
     * expectedExceptionMessage a mapping path is not specified.: 
     */
    public function testNoPathInAction()
    {
        $result = array(
        );
        $annotationReader = new AnnotationManager();
        $builder = new RoutingTableBuilderAnnotation(null,$annotationReader);
        $this->assertEquals($result,$builder->parseControllerClass(__NAMESPACE__.'\Foo5NonePathClass')->getRoutes());
    }

    public function testClassHasntPathMethodHasPathAction()
    {
        $result = array(
            __NAMESPACE__.'\Foo6ClassHasntPathMethodHasPath::actmethod' => array(
                'path' => '/baz',
                'conditions' => array('method'=>'GET'),
                'namespace' => 'boo',
                'handler' => array(
                    'class' => __NAMESPACE__.'\Foo6ClassHasntPathMethodHasPath',
                    'method' => 'actmethod',
                ),
                'type' => 'literal',
            ),
        );
        $annotationReader = new AnnotationManager();
        $builder = new RoutingTableBuilderAnnotation(null,$annotationReader);
        $this->assertEquals($result,$builder->parseControllerClass(__NAMESPACE__.'\Foo6ClassHasntPathMethodHasPath')->getRoutes());
    }

    public function testFile()
    {
        $result = array(
            'AcmeTest\Web\Router\TestControllerClass::foo' => array(
                'path' => '/foo',
                'namespace' => 'AcmeTest\Web\Router',
                'handler' => array(
                    'class' => 'AcmeTest\Web\Router\TestControllerClass',
                    'method' => 'foo',
                ),
                'type' => 'literal',
            ),
        );

        $annotationReader = new AnnotationManager();
        $builder = new RoutingTableBuilderAnnotation(null,$annotationReader);
        $this->assertEquals($result,$builder->parseControllerFile(self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/Router/TestControllerClass.php')->getRoutes());
    }

    public function testFileHasNoClass()
    {
        $result = array(
        );

        $annotationReader = new AnnotationManager();
        $builder = new RoutingTableBuilderAnnotation(null,$annotationReader);
        $this->assertEquals($result,$builder->parseControllerFile(self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/Router/NoClass.php')->getRoutes());
    }
    /**
     * @expectedException        Rindow\Web\Router\Exception\DomainException
     * @expectedExceptionMessage duplicate route name "AcmeTest\Web\Router\foo":
     */
    public function testDuplicateRouteName()
    {
        $result = array(
        );

        $annotationReader = new AnnotationManager();
        $builder = new RoutingTableBuilderAnnotation(null,$annotationReader);
        $this->assertEquals($result,$builder->parseControllerFile(self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/Router/DuplicateRouteName.php')->getRoutes());
    }

    public function testScanDirectory()
    {
        $config = array(
            'controller_paths' => array(
                self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/Router/Dir1' => true,
                self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/Router/Dir2' => true,
            ),
        );

        $annotationReader = new AnnotationManager();
        $builder = new RoutingTableBuilderAnnotation(null,$annotationReader);
        $builder->setConfig($config);
        $routes = $builder->build()->getRoutes();
        $this->assertEquals(4,count($routes));
        $this->assertTrue(isset($routes['AcmeTest\Web\Router\Controller\TestControllerClass1::foo']));
        $this->assertTrue(isset($routes['AcmeTest\Web\Router\Controller\TestControllerClass2::foo']));
        $this->assertTrue(isset($routes['AcmeTest\Web\Router\Controller\TestControllerClass3::foo']));
        $this->assertTrue(isset($routes['AcmeTest\Web\Router\Controller\TestControllerClass4::foo']));
    }

    public function testFileConfig()
    {
        $config = array(
            'config_files' => array(
                'php' => array(
                    'Namespace1' => self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/Router/config/route1.php',
                    'Namespace2' => self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/Router/config/route2.php',
                ),
            ),
        );
        $builder = new RoutingTableBuilderFile();
        $builder->setConfig($config);
        $routes = $builder->build()->getRoutes();
        $this->assertEquals(3,count($routes));
        $this->assertTrue(isset($routes['Namespace1\AcmTest\Web\Router\route1-1']));
        $this->assertTrue(isset($routes['Namespace1\AcmTest\Web\Router\route1-2']));
        $this->assertTrue(isset($routes['Namespace2\AcmTest\Web\Router\route2']));
    }

    /**
     * @expectedException        Rindow\Web\Router\Exception\DomainException
     * @expectedExceptionMessage routing configuration file is not exist.:
     */
    public function testFileConfigNotExist()
    {
        $config = array(
            'config_files' => array(
                'php' => array(
                     'Namespace1' => self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/Router/config/notexist.php',
                ),
            ),
        );
        $builder = new RoutingTableBuilderFile();
        $builder->setConfig($config);
        $routes = $builder->build()->getRoutes();
    }

    /**
     * @expectedException        Rindow\Web\Router\Exception\DomainException
     * @expectedExceptionMessage invalid return type or loading error.:
     */
    public function testFileConfigInvalidFormat()
    {
        $config = array(
            'config_files' => array(
                'php' => array(
                     'Namespace1' => self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/Router/config/null.php',
                ),
            ),
        );
        $builder = new RoutingTableBuilderFile();
        $builder->setConfig($config);
        $routes = $builder->build()->getRoutes();
    }

    public function testFileConfigYaml()
    {
        $config = array(
            'config_files' => array(
                'yaml' => array(
                     'Namespace1' => self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/Router/config/routing.yml',
                ),
            ),
        );
        $builder = new RoutingTableBuilderFile();
        $builder->setConfig($config);
        $routes = $builder->build()->getRoutes();
        $this->assertEquals(2,count($routes));
        $this->assertTrue(isset($routes['Namespace1\AcmTest\Web\Router\home']));
        $this->assertTrue(isset($routes['Namespace1\AcmTest\Web\Router\foo']));
    }

    public function testFileConfigCustom()
    {
        $config = array(
            'config_files' => array(
                __NAMESPACE__.'\\TestLoader::load' => array(
                     'Namespace1' => self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/Router/config/routing.yml',
                ),
            ),
        );
        $builder = new RoutingTableBuilderFile();
        $builder->setConfig($config);
        $routes = $builder->build()->getRoutes();
        $this->assertEquals(2,count($routes));
        $this->assertTrue(isset($routes['Namespace1\AcmTest\Web\Router\home']));
        $this->assertTrue(isset($routes['Namespace1\AcmTest\Web\Router\foo']));
    }

    /**
     * @expectedException        Rindow\Web\Router\Exception\DomainException
     * @expectedExceptionMessage a loader is not found.: INVALIDLOADER
     */
    public function testFileConfigInvalidLoader()
    {
        $config = array(
            'config_files' => array(
                'INVALIDLOADER' => array(
                    'Namespace1' => self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/Router/config/routing.yml',
                ),
            ),
        );
        $builder = new RoutingTableBuilderFile();
        $builder->setConfig($config);
        $routes = $builder->build()->getRoutes();
    }
}
