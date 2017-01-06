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

use tueenaLib\serviceLocator\ServiceLocator;
use tueenaLib\serviceLocator\spec\stubs\ExampleServiceA;
use tueenaLib\serviceLocator\spec\stubs\ExampleServiceB;
use tueenaLib\serviceLocator\spec\stubs\ExampleServiceC;
use tueenaLib\serviceLocator\spec\stubs\IExampleServiceInterface;

class ServiceLocatorTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @test
	 */
	public function A_service_can_be_registered_by_the_implementing_type_as_identifying_type()
	{
		// given
		$target = new ServiceLocator;

		// when
		$returnedServiceLocatorInstance = $target->register(ExampleServiceA::class);

		// then
		$builtService = $returnedServiceLocatorInstance->get(ExampleServiceA::class);
		$this->assertInstanceOf(ExampleServiceA::class, $builtService);
	}

	/**
	 * @test
	 */
	public function A_service_can_be_registered_an_identifying_type_and_an_implementing_type()
	{
		// given
		$target = new ServiceLocator;

		// when
		$returnedServiceLocatorInstance = $target->register(IExampleServiceInterface::class, ExampleServiceA::class);

		// then
		$builtService = $returnedServiceLocatorInstance->get(IExampleServiceInterface::class);
		$this->assertInstanceOf(ExampleServiceA::class, $builtService);
	}

	/**
	 * @test
	 */
	public function A_service_can_be_registered_a_factory_function()
	{
		// given
		$factory = function () use (&$callCounter) { return new ExampleServiceA; };
		$target = new ServiceLocator;

		// when
		$returnedServiceLocatorInstance = $target->register(IExampleServiceInterface::class, $factory);

		// then
		$builtService = $returnedServiceLocatorInstance->get(IExampleServiceInterface::class);
		$this->assertInstanceOf(ExampleServiceA::class, $builtService);
	}

	/**
	 * @test
	 * @expectedException \Exception
	 */
	public function The_identifying_type_is_unique()
	{
		// given
		$initialServiceLocator = new ServiceLocator;
		$target = $initialServiceLocator->register(ExampleServiceA::class);

		// when, then (an exception is expected)
		$target->register(ExampleServiceA::class);
	}

	/**
	 * @test
	 * @expectedException \Exception
	 */
	public function An_exception_is_thrown_if_the_second_parameter_to_register_is_invalid()
	{
		// given
		$initialServiceLocator = new ServiceLocator;

		// when, then (an exception is expected)
		$initialServiceLocator->register(ExampleServiceA::class, 42);
	}

	/**
	 * @test
	 */
	public function The_has_method_returns_true_if_a_service_is_defined_and_false_if_not()
	{
		// given
		$target = new ServiceLocator;

		// when
		$returnedServiceLocatorInstance = $target->register(ExampleServiceA::class);

		// then
		$this->assertTrue($returnedServiceLocatorInstance->has(ExampleServiceA::class));
		$this->assertFalse($returnedServiceLocatorInstance->has(IExampleServiceInterface::class));
	}

	/**
	 * @test
	 */
	public function Every_service_is_only_built_once()
	{
		// given
		$serviceLocator = new ServiceLocator;
		$target = $serviceLocator->register(ExampleServiceA::class);
		$service1 = $target->get(ExampleServiceA::class);

		// when
		$service2 = $target->get(ExampleServiceA::class);

		// then
		$this->assertSame($service1, $service2);
	}

	/**
	 * @test
	 * @expectedException \Exception
	 */
	public function An_exception_is_thown_if_you_want_to_get_a_service_that_has_not_been_registeres()
	{
		// given
		$target = new ServiceLocator;

		// when, then (an exception is thrown)
		$target->get(ExampleServiceA::class);
	}

	/**
	 * @test
	 */
	public function When_a_service_is_build_without_a_factory_function_the_constructor_will_be_injected()
	{
		// given
		$serviceLocator = new ServiceLocator;
		$target = $serviceLocator
			->register(ExampleServiceA::class)
			->register(ExampleServiceB::class);

		// when
		$service = $target->get(ExampleServiceB::class);

		// then
		$this->assertEquals(1, count($service->injectedServices));
		$this->assertInstanceOf(ExampleServiceA::class, $service->injectedServices[0]);
	}

	/**
	 * @test
	 */
	public function When_a_service_is_build_with_a_factory_function_it_will_be_injected()
	{
		// given
		$injectedServices = [];
		$serviceLocator = new ServiceLocator;
		$target = $serviceLocator
			->register(ExampleServiceA::class)
			->register(ExampleServiceB::class, function (ExampleServiceA $exampleServiceA) use (&$injectedServices) { $injectedServices = func_get_args(); });

		// when
		$target->get(ExampleServiceB::class);

		// then
		$this->assertEquals(1, count($injectedServices));
		$this->assertInstanceOf(ExampleServiceA::class, $injectedServices[0]);
	}

	/**
	 * @test
	 * @expectedException \Exception
	 */
	public function ServiceLocator_throws_an_exception_if_a_service_should_be_build_that_requires_itself()
	{
		// given
		$serviceLocator = new ServiceLocator;
		$target = $serviceLocator->register(ExampleServiceC::class);

		// when, then (an exception is expected)
		$target->get(ExampleServiceC::class);
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
			->register(ExampleServiceD::class)
			->register(ExampleServiceE::class);

		// when, then (an exception is expected)
		$target->get(ExampleServiceD::class);
	}
}
