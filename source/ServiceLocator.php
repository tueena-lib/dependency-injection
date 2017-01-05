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
 * @internal See the interface for doc block documentation.
 */
class ServiceLocator implements IServiceLocator
{
	private $serviceDefinitions;
	private $servicesDuringBuildProcess = [];
	private $builtServices = [];

	public function __construct(array $serviceDefinitions = [])
	{
		$this->serviceDefinitions = $serviceDefinitions;
	}

	public function register(string $identifyingType, $implementingTypeOrFactory = null): IServiceLocator
	{
		if ($this->has($identifyingType))
			throw new \Exception("A service $identifyingType has already been defined.");

		if (!is_null($implementingTypeOrFactory) && !is_string($implementingTypeOrFactory) && !($implementingTypeOrFactory instanceof \Closure))
			throw new \Exception('Parameter 2 passed to ' . __CLASS__ . '::register() must be either null, a string (the name of the class, that implements the service) or a \Closure (the factory, that builds the service).');

		$serviceDefinitions = $this->serviceDefinitions;
		$serviceDefinitions[$identifyingType] = $implementingTypeOrFactory;
		return new ServiceLocator($serviceDefinitions);
	}

	public function get(string $identifyingType)
	{
		if (!$this->has($identifyingType))
			throw new \Exception("There is no service $identifyingType registered");
		if (!isset($this->builtServices[$identifyingType]))
			$this->builtServices[$identifyingType] = $this->build($identifyingType);
		return $this->builtServices[$identifyingType];
	}

	public function has(string $identifyingType): bool
	{
		return array_key_exists($identifyingType, $this->serviceDefinitions);
	}

	private function build(string $identifyingType)
	{
		if (!$this->has($identifyingType))
			throw new \Exception("Cannot build service $identifyingType. No factory for that service registered.");

		// Throw exception on recursion.
		if (in_array($identifyingType, $this->servicesDuringBuildProcess))
			throw new \Exception("Recursion: The service $identifyingType cannot be build without having already been built.");
		$this->servicesDuringBuildProcess[] = $identifyingType;

		$implementingTypeOrFactory = $this->serviceDefinitions[$identifyingType] ?? $identifyingType;

		if (is_string($implementingTypeOrFactory))
			$service = Injector::injectConstructor($this, $implementingTypeOrFactory);
		else
			$service = Injector::injectClosure($this, $implementingTypeOrFactory);

		// Remove the identifying type from the array.
		$this->servicesDuringBuildProcess = array_diff($this->servicesDuringBuildProcess, [$identifyingType]);

		return $service;
	}
}
