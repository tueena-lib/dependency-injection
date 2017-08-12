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
namespace tueenaLib\dependencyInjection\stubs;

class ServiceB implements IServiceB
{
	public $parametersPassedToTheConstructor;

	public function __construct(IServiceA $serviceA)
	{
		$this->parametersPassedToTheConstructor = func_get_args();
	}
}
