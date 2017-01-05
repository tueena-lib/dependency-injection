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

class Injector
{
	public static function injectConstructor(ServiceLocator $serviceLocator, string $className)
	{
		if (!class_exists($className))
			throw new \InvalidArgumentException("Cannot find class $className.");

		$reflectionClass = new \ReflectionClass($className);
		$reflectionConstructor = $reflectionClass->getConstructor();
		$hasConstructor = $reflectionConstructor !== null;

		$reflectionParameters = $hasConstructor ? $reflectionConstructor->getParameters() : [];
		$servicesToInject = self::getRequiredServices($serviceLocator, $reflectionParameters, "\\$className::__construct()");

		if (!$hasConstructor)
			return new $className;
		return $reflectionClass->newInstanceArgs($servicesToInject);
	}

	public static function injectMethod(ServiceLocator $serviceLocator, $object, string $methodName)
	{
		$className = get_class($object);
		if (!method_exists($object, $methodName))
			throw new \InvalidArgumentException("An object of class $className doesn't have a method $methodName.");

		$reflectionParameters = (new \ReflectionMethod($object, $methodName))->getParameters();
		$servicesToInject = self::getRequiredServices($serviceLocator, $reflectionParameters, "\\$className::$methodName()");

		return call_user_func_array([$object, $methodName], $servicesToInject);
	}

	public static function injectStaticMethod($serviceLocator, string $className, string $methodName)
	{
		if (!method_exists($className, $methodName))
			throw new \InvalidArgumentException("An object of class $className doesn't have a method $methodName.");

		$reflectionParameters = (new \ReflectionMethod($className, $methodName))->getParameters();
		$servicesToInject = self::getRequiredServices($serviceLocator, $reflectionParameters, "\\$className::$methodName()");

		return call_user_func_array([$className, $methodName], $servicesToInject);
	}

	public static function injectClosure(ServiceLocator $serviceLocator, \Closure $closure)
	{
		$reflectionClosure = new \ReflectionFunction($closure);
		$reflectionParameters = $reflectionClosure->getParameters();
		$servicesToInject = self::getRequiredServices($serviceLocator, $reflectionParameters, 'a closure');

		return call_user_func_array($closure, $servicesToInject);
	}

	public static function injectFunction(ServiceLocator $serviceLocator, string $functionName)
	{
		$reflectionFunction = new \ReflectionFunction($functionName);
		$reflectionParameters = $reflectionFunction->getParameters();
		$servicesToInject = self::getRequiredServices($serviceLocator, $reflectionParameters, "function $functionName()");

		return call_user_func_array($functionName, $servicesToInject);
	}

	public static function injectInvokeMethod(ServiceLocator $serviceLocator, $object)
	{
		$className = get_class($object);
		if (!method_exists($object, '__invoke'))
			throw new \InvalidArgumentException("An object of class $className doesn't have an __invoke() method.");

		$reflectionParameters = (new \ReflectionMethod($object, '__invoke'))->getParameters();
		$servicesToInject = self::getRequiredServices($serviceLocator, $reflectionParameters, "\\$className::__invoke()");

		return call_user_func_array($object, $servicesToInject);
	}

	private static function getRequiredServices(ServiceLocator $serviceLocator, array $reflectionParameters, string $debugInformation): array
	{
		$requiredServices = [];
		foreach ($reflectionParameters as $reflectionParameter) {
			$requiredServiceName = self::getRequiredService($reflectionParameter, $debugInformation);
			$requiredServices[] = $serviceLocator->get($requiredServiceName);
		}
		return $requiredServices;
	}

	private static function getRequiredService(\ReflectionParameter $reflectionParameter, string $debugInformation)
	{
		$parameterName = $reflectionParameter->getName();
		try {
			$reflectionClass = $reflectionParameter->getClass();
		} catch (\Exception $Exception) {
			throw new \Exception("Error while trying to inject services into $debugInformation: Type hint of parameter \$$parameterName is not an existing class or interface.");
		}
		if (is_null($reflectionClass))
			throw new \Exception("Error while trying to inject services into $debugInformation: Type hint of parameter \$$parameterName is missing or not a class or interface.");
		if ($reflectionParameter->isOptional())
			throw new \Exception("Error while trying to inject services into $debugInformation: Parameter \$$parameterName must not be optional.");
		return $reflectionClass->name;
	}
}
