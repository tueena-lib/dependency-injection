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
namespace tueenaLib\serviceLocator;

/**
 * The service locator provides methods to register and build services.
 *
 * Every service will only build once, there will never be two instances of the same service within one service locator.
 * The class is immutable.
 */
interface IServiceLocator
{
	/**
	 * Registers a service.
	 *
	 * Every service is identified by an 'identifying type'. This is the name of an interface or class that identifies
	 * the service. When we're going to inject services into a method Foo:bar(IBaz $baz), then the ServiceLocator will
	 * try to inject a service with the identifying type IBaz. It is a good practice to identify the services by
	 * interfaces, not by concrete classes.
	 *
	 * The second parameter can either be the name of the class, that implements the service, a \Closure that builds the
	 * service or null.
	 *
	 * If $implementingTypeOrFactory is a \Closure, it will be injected with other services if required.
	 *
	 * If $implementingTypeOrFactory is omitted/null, the identifying type must be a concrete class.
	 *
	 * @param string $identifyingType
	 * @param string|\Closure|null $implementingTypeOrFactory
	 * @return IServiceLocator
	 */
	public function register(string $identifyingType, $implementingTypeOrFactory = null): IServiceLocator;

	/**
	 * Returns a service instance or throws an exception, if it is not defined.
	 *
	 * @param string $serviceName
	 * @return object
	 * @throws \Exception
	 */
	public function get(string $identifyingType);

	/**
	 * @param string $identifyingType
	 * @return bool
	 */
	public function has(string $identifyingType): bool;
}
