<?php

/**
 * Part of the tueena lib
 *
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link http://tueena.org/
 * @author Bastian Fenske <bastian.fenske@tueena.org>
 * @file
 */

declare(strict_types=1);
namespace tueenaLib\serviceLocator\spec;

use tueenaLib\serviceLocator\Injector;
use tueenaLib\serviceLocator\ServiceLocator;
use tueenaLib\serviceLocator\spec\stubs\ExampleServiceA;
use tueenaLib\serviceLocator\spec\stubs\ExampleServiceB;

class InjectorTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @test
	 */
	public function The_injectConstructor_method_injects_services_into_a_constructor_and_returns_the_object()
	{
		// given
		$serviceLocator = (new ServiceLocator)->register(ExampleServiceA::class);

		// when (The ExampleServiceB is not used as "service" here)
		$builtObject = Injector::injectConstructor($serviceLocator, ExampleServiceB::class);

		// then
		$this->assertInstanceOf(ExampleServiceB::class, $builtObject);
		$this->assertEquals(1, count($builtObject->injectedServices));
		$this->assertInstanceOf(ExampleServiceA::class, $builtObject->injectedServices[0]);
	}

	/**
	 * @test
	 */
	public function The_injectMethod_method_injects_services_into_a_method()
	{
		// given
		$object = new class {
			public $injectedServices;
			public function testMethod(ExampleServiceA $service)
			{
				$this->injectedServices = func_get_args();
				return 'foo';
			}
		};
		$serviceLocator = (new ServiceLocator)->register(ExampleServiceA::class);

		// when
		$result = Injector::injectMethod($serviceLocator, $object, 'testMethod');

		// then
		$this->assertEquals('foo', $result);
		$this->assertEquals(1, count($object->injectedServices));
		$this->assertInstanceOf(ExampleServiceA::class, $object->injectedServices[0]);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function An_exception_is_thrown_if_you_try_to_inject_into_a_not_existing_method()
	{
		// given
		$serviceLocator = new ServiceLocator;
		$object = new  ExampleServiceA;

		// when, then (an exception is thrown)
		Injector::injectMethod($serviceLocator, $object, 'notExistingMethod');
	}

	/**
	 * @test
	 */
	public function The_injectClosure_method_injects_services_into_a_closure()
	{
		// given
		$injectedServices = [];
		$closure = function (ExampleServiceA $service) use (&$injectedServices) {
			$injectedServices = func_get_args();
			return 'foo';
		};
		$serviceLocator = (new ServiceLocator)->register(ExampleServiceA::class);

		// when
		$result = Injector::injectClosure($serviceLocator, $closure);

		// then
		$this->assertEquals('foo', $result);
		$this->assertEquals(1, count($injectedServices));
		$this->assertInstanceOf(ExampleServiceA::class, $injectedServices[0]);
	}

	/**
	 * @test
	 */
	public function The_injectStaticMethod_method_injects_services_into_a_static_method()
	{
		// given
		$object = new class {
			public static $injectedServices;
			public static function testMethod(ExampleServiceA $service)
			{
				self::$injectedServices = func_get_args();
				return 'foo';
			}
		};
		$className = get_class($object);
		$serviceLocator = (new ServiceLocator)->register(ExampleServiceA::class);

		// when
		$result = Injector::injectStaticMethod($serviceLocator, $className, 'testMethod');

		// then
		$this->assertEquals('foo', $result);
		$this->assertEquals(1, count($className::$injectedServices));
		$this->assertInstanceOf(ExampleServiceA::class, $className::$injectedServices[0]);
	}

	/**
	 * @test
	 */
	public function The_injectFunction_method_injects_services_into_a_function()
	{
		// given
		$serviceLocator = (new ServiceLocator)
			->register(ExampleServiceA::class)
			->register(ExampleServiceB::class);

		// when
		$result = Injector::injectFunction($serviceLocator, '\\tueenaLib\\serviceLocator\\Spec\\stubs\\injectionTarget');

		// then
		$this->assertEquals([$serviceLocator->get(ExampleServiceB::class), $serviceLocator->get(ExampleServiceA::class)], $result);
	}

	/**
	 * @test
	 */
	public function The_injectInvokeMethod_method_injects_services_into_the_invoke_method()
	{
		// given
		$object = new class {
			public $injectedServices;
			public function __invoke(ExampleServiceA $service)
			{
				$this->injectedServices = func_get_args();
				return 'foo';
			}
		};
		$serviceLocator = (new ServiceLocator)->register(ExampleServiceA::class);

		// when
		$result = Injector::injectInvokeMethod($serviceLocator, $object);

		// then
		$this->assertEquals('foo', $result);
		$this->assertEquals(1, count($object->injectedServices));
		$this->assertInstanceOf(ExampleServiceA::class, $object->injectedServices[0]);
	}

}
