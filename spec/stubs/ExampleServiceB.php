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
namespace tueenaLib\dependencyInjection\spec\stubs;

class ExampleServiceB
{
	public $injectedServices;

	public function __construct(ExampleServiceA $serviceA)
	{
		$this->injectedServices = func_get_args();
	}
}
