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

/**
 * The service locator provides methods to register and build services.
 *
 * Every service will only be build once, there will never be two instances of the same service within one service
 * locator. The class is immutable.
 */
interface IServiceLocator
{
	/**
	 * Registers a service by an interface and a class name.
	 *
	 * Every service is identified by an interface name. The service locator will build an object of the given class
	 * on demand. That class must implement the interface (which is not checked here).
	 *
	 * The constructor of the service class may require other services as parameters. If they are registered to the
	 * service locator, they are build and passed to that constructor.
	 *
	 * @param string $interfaceName
	 * @param string $className
	 * @return IServiceLocator
	 */
	public function registerClass(string $interfaceName, string $className): IServiceLocator;

	/**
	 * Registers a service by an interface name and a factory function (a closure).
	 *
	 * Every service is identified by an interface name. When the service locator needs to provide a service that is
	 * registered with this method, the factory function is invoked. It must return an object that implements the
	 * interface.
	 *
	 * The closure may require other services as parameters. If they are registered to the service locator, they are build
	 * and passed to it.
	 *
	 * @param string $interfaceName
	 * @param \Closure $factory
	 * @return IServiceLocator
	 */
	public function registerFactory(string $interfaceName, \Closure $factory): IServiceLocator;

	/**
	 * Returns a service instance or throws an exception, if the service is not defined.
	 *
	 * @param string $interfaceName
	 * @return object
	 * @throws \Exception
	 */
	public function get(string $interfaceName);

	/**
	 * @param string $interfaceName
	 * @return bool
	 */
	public function has(string $interfaceName): bool;
}
