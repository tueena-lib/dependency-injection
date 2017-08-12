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
use tueenaLib\dependencyInjection\stubs\IServiceB;
use tueenaLib\dependencyInjection\stubs\IServiceC;
use tueenaLib\dependencyInjection\stubs\IServiceD;
use tueenaLib\dependencyInjection\stubs\IServiceE;
use tueenaLib\dependencyInjection\stubs\ServiceA;
use tueenaLib\dependencyInjection\stubs\ServiceB;
use tueenaLib\dependencyInjection\stubs\ServiceC;
use tueenaLib\dependencyInjection\stubs\ServiceD;
use tueenaLib\dependencyInjection\stubs\ServiceE;
use tueenaLib\dependencyInjection\stubs\IServiceA;

class ServiceLocatorTest extends TestCase
{
	/**
	 * @test
	 */
	public function a_service_can_be_registered_and_build_with_a_class_name()
	{
		// given
		$serviceLocator = new ServiceLocator;

		// when
		$newServiceLocatorObject = $serviceLocator->registerClass(IServiceA::class, ServiceA::class);
		$builtService = $newServiceLocatorObject->get(IServiceA::class);

		// then
		$this->assertInstanceOf(ServiceA::class, $builtService);
	}

	/**
	 * @test
	 */
	public function a_service_can_be_registered_and_build_with_a_factory_function()
	{
		// given
		$factory = function () { return new ServiceA; };
		$serviceLocator = new ServiceLocator;

		// when
		$newServiceLocatorObject = $serviceLocator->registerFactory(IServiceA::class, $factory);
		$builtService = $newServiceLocatorObject->get(IServiceA::class);

		// then
		$this->assertInstanceOf(ServiceA::class, $builtService);
	}

	/**
	 * @test
	 * @expectedException \Exception
	 */
	public function registerClass_throws_an_exception_if_a_service_has_already_been_registered_for_that_interface()
	{
		// given
		$initialServiceLocator = new ServiceLocator;
		$target = $initialServiceLocator->registerFactory(IServiceA::class, function () {});

		// when, then (an exception is expected)
		$target->registerClass(IServiceA::class, ServiceA::class);
	}

	/**
	 * @test
	 * @expectedException \Exception
	 */
	public function registerFactory_throws_an_exception_if_a_service_has_already_been_registered_for_that_interface()
	{
		// given
		$initialServiceLocator = new ServiceLocator;
		$target = $initialServiceLocator->registerClass(IServiceA::class, ServiceA::class);

		// when, then (an exception is expected)
		$target->registerFactory(IServiceA::class, function () {});
	}

	/**
	 * @test
	 */
	public function the_has_method_returns_true_if_a_service_is_defined_and_false_if_not()
	{
		// given
		$target = new ServiceLocator;

		// when
		$returnedServiceLocatorInstance = $target->registerClass(IServiceA::class, ServiceA::class);

		// then
		$this->assertTrue($returnedServiceLocatorInstance->has(IServiceA::class));
		$this->assertFalse($returnedServiceLocatorInstance->has(\DateTime::class));
	}

	/**
	 * @test
	 */
	public function every_service_is_only_built_once()
	{
		// given
		$serviceLocator = new ServiceLocator;
		$target = $serviceLocator->registerClass(IServiceA::class, ServiceA::class);
		$service1 = $target->get(IServiceA::class);

		// when
		$service2 = $target->get(IServiceA::class);

		// then
		$this->assertSame($service1, $service2);
	}

	/**
	 * @test
	 * @expectedException \Exception
	 */
	public function an_exception_is_thrown_if_you_want_to_get_a_service_that_has_not_been_registered()
	{
		// given
		$target = new ServiceLocator;

		// when, then (an exception is thrown)
		$target->get(IServiceA::class);
	}

	/**
	 * @test
	 */
	public function to_build_a_service_the_constructor_will_be_injected()
	{
		// given
		$serviceLocator = new ServiceLocator;
		$target = $serviceLocator
			->registerClass(IServiceA::class, ServiceA::class)
			->registerClass(IServiceB::class, ServiceB::class);

		// when
		$service = $target->get(IServiceB::class);

		// then
		$this->assertEquals(1, count($service->parametersPassedToTheConstructor));
		$this->assertInstanceOf(ServiceA::class, $service->parametersPassedToTheConstructor[0]);
	}

	/**
	 * @test
	 */
	public function to_build_a_service_with_a_factory_function_it_will_be_injected()
	{
		// given
		$passedInParameters = [];
		$serviceLocator = new ServiceLocator;
		$target = $serviceLocator
			->registerClass(IServiceA::class, ServiceA::class)
			->registerFactory(IServiceB::class, function (IServiceA $serviceA) use (&$passedInParameters) { $passedInParameters = func_get_args(); });

		// when
		$target->get(IServiceB::class);

		// then
		$this->assertEquals(1, count($passedInParameters));
		$this->assertInstanceOf(ServiceA::class, $passedInParameters[0]);
	}

	/**
	 * @test
	 * @expectedException \Exception
	 */
	public function ServiceLocator_throws_an_exception_if_a_service_should_be_build_that_requires_itself()
	{
		// given
		$serviceLocator = new ServiceLocator;
		// The constructor of ServiceC expects a parameter of type ServiceC.
		$target = $serviceLocator->registerClass(IServiceC::class, ServiceC::class);

		// when, then (an exception is expected)
		$target->get(IServiceC::class);
	}

	/**
	 * @test
	 * @expectedException \Exception
	 */
	public function ServiceLocator_throws_an_exception_on_circular_references()
	{
		// given
		$serviceLocator = new ServiceLocator;
		$target = $serviceLocator
			->registerClass(IServiceD::class, ServiceD::class)  // ServiceD requires IServiceE
			->registerClass(IServiceE::class, ServiceE::class); // ServiceE requires IServiceD

		// when, then (an exception is expected)
		$target->get(IServiceD::class);
	}
}
