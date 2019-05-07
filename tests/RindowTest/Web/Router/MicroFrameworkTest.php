<?php
namespace RindowTest\Web\Router\MicroFrameworkTest;

use PHPUnit\Framework\TestCase;
use Rindow\Web\Router\App;
use Rindow\Web\Router\Router;
use Rindow\Web\Http\Message\ServerRequestFactory;
use Rindow\Web\Http\Message\TestModeEnvironment;
use Rindow\Web\Http\Message\Response;
use Rindow\Web\Http\Message\Stream;

class TestController
{
	public function __invoke($request,$response,$args)
	{
		$response->getBody()->write('[action('.implode(',', $args).')]');
		return $response;
	}
}

class TestMiddleware
{
	public static function foo($request,$response,$next)
	{
		$response->getBody()->write('[middleware]');
		return call_user_func($next,$request,$response);
	}
}

class TestContainer
{
	protected $values = array();
	public function set($name,$value)
	{
		$this->values[$name] = $value;
	}
	public function get($name)
	{
		if(!isset($this->values[$name]))
			return null;
		return $this->values[$name];
	}
}

class Test extends TestCase
{
    public function setup()
    {
    }

	public function testAddRoute()
	{
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/path/abc';
        $env->_SERVER['REQUEST_METHOD'] = 'GET';

        $request = ServerRequestFactory::fromTestEnvironment($env);
        $body = new Stream(fopen('php://memory', 'rw'));
        $response = new Response(null,null,$body);

        $router = new Router();

		$name = 'RouteFunction';
		$path = '/path';
		$params = array('id');
		$method = 'GET';
		$headers = array();
		$handler = 'HANDLER';
		$defaults = array();
		$router->addRoute($name,$path,$handler,$params,$method,$headers,$defaults);
		$info = $router->match($request);
		$result = array(
			'params' => array(
				'id' => 'abc',
			),
			'route' => array(
				'path' => '/path',
				'conditions' => array(
					'method' => 'GET',
				),
				'type' => 'segment',
				'parameters' => array(
					'id',
				),
				'handler' => array(
					'callable' => 'HANDLER'
				),
				'name' => 'RouteFunction',
			),
		);
		$this->assertEquals($result,$info);
	}

	public function testAppOnMemory()
	{
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/path/abc';
        $env->_SERVER['REQUEST_METHOD'] = 'GET';
        $request = ServerRequestFactory::fromTestEnvironment($env);

        $body = new Stream(fopen('php://memory', 'rw'));
        $response = new Response(null,null,$body);
		$app = new App();
		$app->setRequest($request);
		$app->setResponse($response);
		$app->get(
			array('/path','id'),
			function($request,$response,$args) {
					$response->getBody()->write('[action('.implode(',', $args).')]');
					return $response;
			},
			'RouteFunction'
		);

		$this->assertEquals('/path/abc',$app->getRequest()->getUri()->getPath());

		$response = $app->run();
		$this->assertNotNull($response);

		$response->getBody()->rewind();
		$this->assertEquals('[action(abc)]',$response->getBody()->getContents());
	}

	public function testAppWithContainer()
	{
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/path/abc';
        $env->_SERVER['REQUEST_METHOD'] = 'GET';
        $request = ServerRequestFactory::fromTestEnvironment($env);

        $container = new TestContainer();
        $container->set('controller',new TestController());
        $this->assertTrue(is_callable($container->get('controller')));

        $body = new Stream(fopen('php://memory', 'rw'));
        $response = new Response(null,null,$body);
		$app = new App();
		$app->setRequest($request);
		$app->setServiceLocator($container);
		$app->setResponse($response);
		$app->get(
			array('/path','id'),
			'controller',
			'RouteFunction'
		);

		$this->assertEquals('/path/abc',$app->getRequest()->getUri()->getPath());
		//$this->assertEquals('/path/abc',$app->getUrlGenerator()->getPath());

		$response = $app->run();
		$this->assertNotNull($response);

		$response->getBody()->rewind();
		$this->assertEquals('[action(abc)]',$response->getBody()->getContents());
	}
}