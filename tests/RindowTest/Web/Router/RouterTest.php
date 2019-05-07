<?php
namespace RindowTest\Web\Router\RouterTest;

use PHPUnit\Framework\TestCase;
use Rindow\Web\Http\Message\ServerRequestFactory;
use Rindow\Web\Http\Message\TestModeEnvironment;
use Rindow\Web\Http\Message\Response;
use Rindow\Module\Yaml\Yaml;

// Test Target Classes
use Rindow\Web\Router\Router;

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

    public function getRouter($config,$env)
    {
        $router = new Router();
        $router->setConfig($config);
        $request = ServerRequestFactory::fromTestEnvironment($env);
        $path = $request->getUri()->getPath();
        return array($router,$request,$path);
    }

    public function testMatchPathRoot()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
                'foo' => array(
                    'path' => '/foo',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/';
        //$env->_SERVER['REQUEST_METHOD'] = 'GET';
        list($router,$request,$path) = $this->getRouter($config,$env);

        $route = $router->matchRoute($path,$request);
        $this->assertEquals('/', $route['path']);
        $this->assertEquals('home', $route['name']);

        $param = $router->parseParameter($path, $route);
        $this->assertEquals(0, count($param));

        $param = $router->setDefaultParameter($param, $route);
        $this->assertEquals(1, count($param));
        $this->assertEquals('index', $param['action']);
    }

    public function testMatchPath()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
                'foo' => array(
                    'path' => '/foo',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/foo';
        list($router,$request,$path) = $this->getRouter($config,$env);

        $route = $router->matchRoute($path,$request);
        $this->assertEquals('/foo', $route['path']);
        $this->assertEquals('foo', $route['name']);

        $param = $router->parseParameter($path, $route);
        $this->assertEquals(0, count($param));

        $param = $router->setDefaultParameter($param, $route);
        $this->assertEquals(1, count($param));
        $this->assertEquals('index', $param['action']);
    }

    /**
     * @expectedException        Rindow\Web\Router\Exception\PageNotFoundException
     * @expectedExceptionMessage Path Not Found
     */
    public function testUnMatchPath()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/root',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
                'foo' => array(
                    'path' => '/foo',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/boo';
        list($router,$request,$path) = $this->getRouter($config,$env);

        $route = $router->matchRoute($path,$request);
        //$this->assertNull($route);
    }

    public function testMatchParam()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
                'foo' => array(
                    'path' => '/foo',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/foo/bar';
        list($router,$request,$path) = $this->getRouter($config,$env);

        $route = $router->matchRoute($path,$request);
        $this->assertEquals('/foo', $route['path']);
        $this->assertEquals('foo', $route['name']);

        $param = $router->parseParameter($path, $route);
        $this->assertEquals(1, count($param));
        $this->assertEquals('bar', $param['action']);

        $param = $router->setDefaultParameter($param, $route);
        $this->assertEquals(1, count($param));
        $this->assertEquals('bar', $param['action']);
    }

    public function testMatchParamNonLeft()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
                'foo' => array(
                    'path' => '/foo',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/foobar';
        list($router,$request,$path) = $this->getRouter($config,$env);

        $route = $router->matchRoute($path,$request);
        $this->assertEquals('/foo', $route['path']);
        $this->assertEquals('foo', $route['name']);

        $param = $router->parseParameter($path, $route);
        $this->assertEquals(1, count($param));
        $this->assertEquals('bar', $param['action']);

        $param = $router->setDefaultParameter($param, $route);
        $this->assertEquals(1, count($param));
        $this->assertEquals('bar', $param['action']);
    }

    public function testMatchParamDashRight()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
                'foo' => array(
                    'path' => '/foo',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/foo/bar/';
        list($router,$request,$path) = $this->getRouter($config,$env);

        $route = $router->matchRoute($path,$request);
        $this->assertEquals('/foo', $route['path']);
        $this->assertEquals('foo', $route['name']);

        $param = $router->parseParameter($path, $route);
        $this->assertEquals(1, count($param));
        $this->assertEquals('bar', $param['action']);

        $param = $router->setDefaultParameter($param, $route);
        $this->assertEquals(1, count($param));
        $this->assertEquals('bar', $param['action']);
    }

    public function testMatchParam2()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
                'foo' => array(
                    'path' => '/foo',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/foo/bar/abc';
        list($router,$request,$path) = $this->getRouter($config,$env);

        $route = $router->matchRoute($path,$request);
        $this->assertEquals('/foo', $route['path']);
        $this->assertEquals('foo', $route['name']);

        $param = $router->parseParameter($path, $route);
        $this->assertEquals(2, count($param));
        $this->assertEquals('bar', $param['action']);
        $this->assertEquals('abc', $param['id']);

        $param = $router->setDefaultParameter($param, $route);
        $this->assertEquals(2, count($param));
        $this->assertEquals('bar', $param['action']);
        $this->assertEquals('abc', $param['id']);
    }

    public function testMatchParamOver()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
                'foo' => array(
                    'path' => '/foo',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/foo/bar/abc/xyz';
        list($router,$request,$path) = $this->getRouter($config,$env);

        $route = $router->matchRoute($path,$request);
        $this->assertEquals('/foo', $route['path']);
        $this->assertEquals('foo', $route['name']);

        $param = $router->parseParameter($path, $route);
        $this->assertEquals(2, count($param));
        $this->assertEquals('bar', $param['action']);
        $this->assertEquals('abc', $param['id']);

        $param = $router->setDefaultParameter($param, $route);
        $this->assertEquals(2, count($param));
        $this->assertEquals('bar', $param['action']);
        $this->assertEquals('abc', $param['id']);
    }

    public function testMatchMethod()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
                'foo\Post' => array(
                    'path' => '/foo',
                    'conditions' => array(
                        'method' => 'POST',
                    ),
                    'type' => 'segment',
                    'parameters' => array('id'),
                    'defaults' => array(
                        'action' => 'post',
                    ),
                ),
                'foo\Get' => array(
                    'path' => '/foo',
                    'conditions' => array(
                        'method' => 'GET',
                    ),
                    'type' => 'segment',
                    'parameters' => array('id'),
                    'defaults' => array(
                        'action' => 'get',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/foo/abc/def';
        $env->_SERVER['REQUEST_METHOD'] = 'GET';

        list($router,$request,$path) = $this->getRouter($config,$env);
        
        $route = $router->matchRoute($path,$request);
        $this->assertEquals('/foo', $route['path']);
        $this->assertEquals('foo\Get', $route['name']);

        $env->_SERVER['REQUEST_METHOD'] = 'POST';
        $request = ServerRequestFactory::fromTestEnvironment($env);
        $route = $router->matchRoute($path,$request);
        $this->assertEquals('/foo', $route['path']);
        $this->assertEquals('foo\Post', $route['name']);
    }

    public function testMatchHeaders()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
                'foo\html' => array(
                    'path' => '/foo',
                    'conditions' => array(
                        'headers' => array(
                            'Accept' => 'text/html',
                            'Accept-Language' => 'ja',
                        ),
                    ),
                    'type' => 'segment',
                    'parameters' => array('id'),
                    'defaults' => array(
                        'action' => 'html',
                    ),
                ),
                'foo\json' => array(
                    'path' => '/foo',
                    'conditions' => array(
                        'headers' => array(
                            'Accept' => 'application/json',
                        ),
                    ),
                    'type' => 'segment',
                    'parameters' => array('id'),
                    'defaults' => array(
                        'action' => 'json',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/foo/abc/def';
        $env->_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml';
        $env->_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ja,en-US;q=0.8,en;q=0.6';

        list($router,$request,$path) = $this->getRouter($config,$env);
        
        $route = $router->matchRoute($path,$request);
        $this->assertEquals('/foo', $route['path']);
        $this->assertEquals('foo\html', $route['name']);

        $env->_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US;q=0.8,en;q=0.6';
        $request = ServerRequestFactory::fromTestEnvironment($env);
        $route = $router->matchRoute($path,$request);
        $this->assertEquals('/', $route['path']);
        $this->assertEquals('home', $route['name']);

        $env->_SERVER['HTTP_ACCEPT'] = 'application/json';
        $env->_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ja,en-US;q=0.8,en;q=0.6';

        $request = ServerRequestFactory::fromTestEnvironment($env);
        $route = $router->matchRoute($path,$request);
        $this->assertEquals('/foo', $route['path']);
        $this->assertEquals('foo\json', $route['name']);
    }

    public function testMatch()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/',  
                    'parameters' => array('action', 'id'),
                    'type' => 'segment',
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
                'foo' => array(
                    'path' => '/foo',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/foo/bar/abc/xyz';

        list($router,$request,$path) = $this->getRouter($config,$env);

        $info = $router->match($request,$path);
        $route = $info['route'];
        $this->assertEquals('/foo', $route['path']);
        $this->assertEquals('foo', $route['name']);
        $param = $info['params'];
        $this->assertEquals(2, count($param));
        $this->assertEquals('bar', $param['action']);
        $this->assertEquals('abc', $param['id']);
    }

    public function testMatch2()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
                'foo' => array(
                    'path' => '/foo',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/bar/abc/xyz';

        list($router,$request,$path) = $this->getRouter($config,$env);

        $info = $router->match($request,$path);
        $route = $info['route'];
        $this->assertEquals('/', $route['path']);
        $this->assertEquals('home', $route['name']);
        $param = $info['params'];
        $this->assertEquals(2, count($param));
        $this->assertEquals('bar', $param['action']);
        $this->assertEquals('abc', $param['id']);
    }

    /**
     * @expectedException        Rindow\Web\Router\Exception\PageNotFoundException
     * @expectedExceptionMessage A literal route has sub-directory on route "home".
     */
    public function testLiteralHasSubDirectory()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/',
                    'defaults' => array(
                        'action' => 'index',
                    ),
                    'type' => 'literal',
                    'parameters' => array('action', 'id'),
                ),
                'foo' => array(
                    'path' => '/foo',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/boo';

        list($router,$request,$path) = $this->getRouter($config,$env);

        $info = $router->match($request,$path);
        $this->assertFalse($info);
    }


    /**
     * @expectedException        Rindow\Web\Router\Exception\DomainException
     * @expectedExceptionMessage Unkown route type "UNKNOWN" in route "home"
     */
    public function testUnkownRouteType()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/',
                    'type' => 'UNKNOWN',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/';

        list($router,$request,$path) = $this->getRouter($config,$env);

        $info = $router->match($request,$path);
        $this->assertFalse($info);
    }

    public function testTypeOfExplicitClassName()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/',
                    'type' => 'Rindow\Web\Router\Parser\SegmentParser',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/test/123';

        list($router,$request,$path) = $this->getRouter($config,$env);

        $info = $router->match($request,$path);
        $param = $info['params'];
        $this->assertEquals('123',$param['id']);
    }

    public function testAssembleSegment()
    {
        $namespace = 'fooname';
        $config = array(
            'routes' => array(
                $namespace.'\home' => array(
                    'path' => '/',
                    'type' => 'Rindow\Web\Router\Parser\SegmentParser',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
                $namespace.'\foo' => array(
                    'path' => '/foo',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
                $namespace.'\bar' => array(
                    'path' => '/bar',
                    'type' => 'literal',
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/test/123';
        list($router,$request,$path) = $this->getRouter($config,$env);

        $path = $router->assemble($namespace.'\home',array('action'=>'test','id'=>123));
        $this->assertEquals('/test/123',$path);

        $path = $router->assemble($namespace.'\home');
        $this->assertEquals('/',$path);

        $path = $router->assemble($namespace.'\foo',array('action'=>'test','id'=>123));
        $this->assertEquals('/foo/test/123',$path);

        $path = $router->assemble($namespace.'\foo');
        $this->assertEquals('/foo',$path);

        $path = $router->assemble($namespace.'\bar');
        $this->assertEquals('/bar',$path);
    }

    public function testDontThrowUnMatchPath()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/root',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
                'foo' => array(
                    'path' => '/foo',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/boo';
        list($router,$request,$path) = $this->getRouter($config,$env);
        $router->setDoNotThrowPageNotFound(true);

        $route = $router->match($request,$path);
        $this->assertEquals(Router::STATUS_PAGE_NOT_FOUND,$route['error']['status']);
        $this->assertEquals('Path Not Found.',$route['error']['reason']);
    }

    public function testPathPrefixes()
    {
        $config = array(
            'routes' => array(
                'home' => array(
                    'path' => '/root',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                    'namespace' => 'namespace1',
                ),
                'foo' => array(
                    'path' => '/foo',
                    'type' => 'segment',
                    'parameters' => array('action', 'id'),
                    'defaults' => array(
                        'action' => 'index',
                    ),
                ),
            ),
            'pathPrefixes' => array(
                'namespace1' => '/path1',
                'namespace2' => '/path2',
            ),
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/path1/root/a1/id1';
        list($router,$request,$path) = $this->getRouter($config,$env);
        $router->setDoNotThrowPageNotFound(true);

        $info = $router->match($request,$path);
        $this->assertEquals('home',$info['route']['name']);
        $this->assertEquals('a1',$info['params']['action']);
        $this->assertEquals('id1',$info['params']['id']);
        $this->assertEquals(
            '/path1/root/a1/id1',
            $router->assemble('home',array('action'=>'a1','id'=>'id1')));
    }


    public function testYamlConfig()
    {
        //$yaml = <<<EOD
//routes: 
//    %__INCLUDE(%__DIR__%/AcmeTest/MvcRouter/config/routing.yml)__%
//EOD;
        //$yaml = str_replace('%__DIR__%', str_replace('\\','\\\\',__DIR__),$yaml);
        //$config = yaml_parse($yaml); 
        $yaml = new Yaml();
        $config = array(
            'routes' => $yaml->fileToArray(self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/Router/config/routing.yml')
        );
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/foo';
        list($router,$request,$path) = $this->getRouter($config,$env);

        $route = $router->matchRoute($path,$request);
        $this->assertEquals('/foo', $route['path']);
        $this->assertEquals('AcmTest\Web\Router\foo', $route['name']);

        $param = $router->parseParameter($path, $route);
        $this->assertEquals(0, count($param));

        $param = $router->setDefaultParameter($param, $route);
        $this->assertEquals(1, count($param));
        $this->assertEquals('index', $param['action']);
    }

}
