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

namespace tueenaLib\dependencyInjection;

use PHPUnit\Framework\TestCase;
use tueenaLib\dependencyInjection\stubs\IServiceA;
use tueenaLib\dependencyInjection\stubs\IServiceB;
use tueenaLib\dependencyInjection\stubs\ServiceA;
use tueenaLib\dependencyInjection\stubs\ServiceB;

class InjectorTest extends TestCase
{
	/**
	 * @test
	 */
	public function the_injectConstructor_method_injects_services_into_a_constructor_and_returns_the_object()
	{
		// given
		$requiredService = new ServiceA;
		$serviceLocatorProphecy = $this->prophesize(IServiceLocator::class);
		$serviceLocatorProphecy->get(IServiceA::class)->willReturn($requiredService);

		// when
		$builtObject = Injector::invokeConstructor($serviceLocatorProphecy->reveal(), ServiceB::class);

		// then
		$this->assertInstanceOf(ServiceB::class, $builtObject);
		$this->assertEquals(1, count($builtObject->parametersPassedToTheConstructor));
		$this->assertSame($requiredService, $builtObject->parametersPassedToTheConstructor[0]);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function an_exception_is_thrown_if_you_try_to_inject_into_a_constructor_of_a_not_existing_class()
	{
		// given
		$serviceLocatorProphecy = $this->prophesize(IServiceLocator::class);

		// when, then (an exception is thrown)
		Injector::invokeConstructor($serviceLocatorProphecy->reveal(), 'NotExistingClass');
	}

	/**
	 * @test
	 */
	public function the_injectMethod_method_injects_services_into_a_method()
	{
		// given
		$object = new class {
			public $parametersPassedToTheMethod;
			public function testMethod(IServiceA $service)
			{
				$this->parametersPassedToTheMethod = func_get_args();
				return 'foo';
			}
		};
		$requiredService = new ServiceA;
		$serviceLocatorProphecy = $this->prophesize(IServiceLocator::class);
		$serviceLocatorProphecy->get(IServiceA::class)->willReturn($requiredService);

		// when
		$result = Injector::invokeMethod($serviceLocatorProphecy->reveal(), $object, 'testMethod');

		// then
		$this->assertEquals('foo', $result);
		$this->assertEquals(1, count($object->parametersPassedToTheMethod));
		$this->assertSame($requiredService, $object->parametersPassedToTheMethod[0]);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function an_exception_is_thrown_if_you_try_to_inject_into_a_not_existing_method()
	{
		// given
		$serviceLocatorProphecy = $this->prophesize(IServiceLocator::class);
		$object = new class {};

		// when, then (an exception is thrown)
		Injector::invokeMethod($serviceLocatorProphecy->reveal(), $object, 'notExistingMethod');
	}

	/**
	 * @test
	 */
	public function the_injectClosure_method_injects_services_into_a_closure()
	{
		// given
		$parametersPassedToTheClosure = [];
		$closure = function (IServiceA $service) use (&$parametersPassedToTheClosure) {
			$parametersPassedToTheClosure = func_get_args();
			return 'foo';
		};
		$requiredService = new ServiceA;
		$serviceLocatorProphecy = $this->prophesize(IServiceLocator::class);
		$serviceLocatorProphecy->get(IServiceA::class)->willReturn($requiredService);

		// when
		$result = Injector::invokeClosure($serviceLocatorProphecy->reveal(), $closure);

		// then
		$this->assertEquals('foo', $result);
		$this->assertEquals(1, count($parametersPassedToTheClosure));
		$this->assertSame($requiredService, $parametersPassedToTheClosure[0]);
	}

	/**
	 * @test
	 */
	public function the_injectStaticMethod_method_injects_services_into_a_static_method()
	{
		// given
		$object = new class {
			public static $parametersPassedToTheMethod;
			public static function testMethod(IServiceA $service)
			{
				self::$parametersPassedToTheMethod = func_get_args();
				return 'foo';
			}
		};
		$className = get_class($object);
		$requiredService = new ServiceA;
		$serviceLocatorProphecy = $this->prophesize(IServiceLocator::class);
		$serviceLocatorProphecy->get(IServiceA::class)->willReturn($requiredService);

		// when
		$result = Injector::invokeStaticMethod($serviceLocatorProphecy->reveal(), $className, 'testMethod');

		// then
		$this->assertEquals('foo', $result);
		$this->assertEquals(1, count($className::$parametersPassedToTheMethod));
		$this->assertSame($requiredService, $className::$parametersPassedToTheMethod[0]);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function an_exception_is_thrown_if_you_try_to_inject_into_a_not_existing_static_method()
	{
		// given
		$serviceLocatorProphecy = $this->prophesize(IServiceLocator::class);

		// when, then (an exception is thrown)
		Injector::invokeStaticMethod($serviceLocatorProphecy->reveal(), ServiceA::class, 'notExistingMethod');
	}

	/**
	 * @test
	 */
	public function the_injectFunction_method_injects_services_into_a_function()
	{
		// given
		$requiredServiceA = new ServiceA;
		$requiredServiceB = new ServiceB($requiredServiceA);
		$serviceLocatorProphecy = $this->prophesize(IServiceLocator::class);
		$serviceLocatorProphecy->get(IServiceA::class)->willReturn($requiredServiceA);
		$serviceLocatorProphecy->get(IServiceB::class)->willReturn($requiredServiceB);

		// when
		$result = Injector::invokeFunction($serviceLocatorProphecy->reveal(), '\\tueenaLib\\dependencyInjection\\stubs\\injectionTarget');

		// then
		$this->assertEquals(3, count($result));
		$this->assertSame($requiredServiceA, $result[0]);
		$this->assertSame($requiredServiceB, $result[1]);
		$this->assertEquals('something added by the function', $result[2]);
	}

	/**
	 * @test
	 */
	public function the_injectInvokeMethod_method_injects_services_into_the_invoke_method()
	{
		// given
		$object = new class {
			public $parametersPassedToTheInvokeMethod;
			public function __invoke(IServiceA $service)
			{
				$this->parametersPassedToTheInvokeMethod = func_get_args();
				return 'foo';
			}
		};
		$requiredService = new ServiceA;
		$serviceLocatorProphecy = $this->prophesize(IServiceLocator::class);
		$serviceLocatorProphecy->get(IServiceA::class)->willReturn($requiredService);

		// when
		$result = Injector::invokeInvokeMethod($serviceLocatorProphecy->reveal(), $object);

		// then
		$this->assertEquals('foo', $result);
		$this->assertEquals(1, count($object->parametersPassedToTheInvokeMethod));
		$this->assertSame($requiredService, $object->parametersPassedToTheInvokeMethod[0]);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function an_exception_is_thrown_if_you_try_to_inject_into_a_not_existing_invoke_method()
	{
		// given
		$serviceLocatorProphecy = $this->prophesize(IServiceLocator::class);
		$objectWithoutInvokeMethod = new ServiceA;

		// when, then (an exception is thrown)
		Injector::invokeInvokeMethod($serviceLocatorProphecy->reveal(), $objectWithoutInvokeMethod);
	}

	/**
	 * @test
	 * @expectedException \Exception
	 */
	public function an_exception_is_thrown_if_the_injection_target_has_a_parameter_without_type_hint()
	{
		// given
		$serviceLocatorProphecy = $this->prophesize(IServiceLocator::class);
		$closure = function ($foo) {};

		// when, then (an exception is thrown)
		Injector::invokeClosure($serviceLocatorProphecy->reveal(), $closure);
	}

	/**
	 * @test
	 * @expectedException \Exception
	 */
	public function an_exception_is_thrown_if_the_injection_target_has_a_parameter_with_a_type_hint_that_is_not_an_existing_class_or_interface()
	{
		// given
		$serviceLocatorProphecy = $this->prophesize(IServiceLocator::class);
		$closure = function (Foo $foo) {};

		// when, then (an exception is thrown)
		Injector::invokeClosure($serviceLocatorProphecy->reveal(), $closure);
	}

	/**
	 * @test
	 * @expectedException \Exception
	 */
	public function an_exception_is_thrown_if_the_injection_target_has_a_parameter_with_a_type_hint_that_is_not_a_class_or_interface()
	{
		// given
		$serviceLocatorProphecy = $this->prophesize(IServiceLocator::class);
		$closure = function (string $foo) {};

		// when, then (an exception is thrown)
		Injector::invokeClosure($serviceLocatorProphecy->reveal(), $closure);
	}

	/**
	 * @test
	 * @expectedException \Exception
	 */
	public function an_exception_is_thrown_if_the_injection_target_has_an_optional_parameter()
	{
		// given
		$serviceLocatorProphecy = $this->prophesize(IServiceLocator::class);
		$closure = function (ServiceA $serviceA = null) {};

		// when, then (an exception is thrown)
		Injector::invokeClosure($serviceLocatorProphecy->reveal(), $closure);
	}
}
