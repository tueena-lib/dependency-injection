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

class ServiceLocator implements IServiceLocator
{
	private $serviceDefinitions;
	private $servicesDuringBuildProcess = [];
	private $builtServices = [];

	public function __construct(array $serviceDefinitions = [])
	{
		$this->serviceDefinitions = $serviceDefinitions;
	}

	public function registerClass(string $interfaceName, string $className): IServiceLocator
	{
		if ($this->has($interfaceName))
			throw new \Exception("A service $interfaceName has already been defined.");
		return $this->register($interfaceName, $className);
	}

	public function registerFactory(string $interfaceName, \Closure $factory): IServiceLocator
	{
		if ($this->has($interfaceName))
			throw new \Exception("A service $interfaceName has already been defined.");
		return $this->register($interfaceName, $factory);
	}

	public function get(string $interfaceName)
	{
		if (!$this->has($interfaceName))
			throw new \Exception("There is no service $interfaceName registered");
		if (!isset($this->builtServices[$interfaceName]))
			$this->builtServices[$interfaceName] = $this->build($interfaceName);
		return $this->builtServices[$interfaceName];
	}

	public function has(string $interfaceName): bool
	{
		return array_key_exists($interfaceName, $this->serviceDefinitions);
	}

	private function register(string $interfaceName, $classNameOrFactory): IServiceLocator
	{
		$serviceDefinitions = $this->serviceDefinitions;
		$serviceDefinitions[$interfaceName] = $classNameOrFactory;
		return new ServiceLocator($serviceDefinitions);
	}

	private function build(string $interfaceName)
	{
		// Throw exception on recursion.
		if (in_array($interfaceName, $this->servicesDuringBuildProcess))
			throw new \Exception("Circular reference: To build the service $interfaceName, an already built service $interfaceName would be required.");
		$this->servicesDuringBuildProcess[] = $interfaceName;

		$implementingTypeOrFactory = $this->serviceDefinitions[$interfaceName] ?? $interfaceName;

		if (is_string($implementingTypeOrFactory))
			$service = Injector::invokeConstructor($this, $implementingTypeOrFactory);
		else
			$service = Injector::invokeClosure($this, $implementingTypeOrFactory);

		// Remove the identifying type from the array.
		$this->servicesDuringBuildProcess = array_diff($this->servicesDuringBuildProcess, [$interfaceName]);

		return $service;
	}
}
