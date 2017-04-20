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

require_once __DIR__ . '/stubs/functions.php';

spl_autoload_register(function ($className) {

	$namespaceParts = explode('\\', $className);
	$firstNamespacePart = array_shift($namespaceParts);
	$secondNamespacePart = array_shift($namespaceParts);
	if ($firstNamespacePart !== 'tueenaLib' || $secondNamespacePart !== 'dependencyInjection')
		return false;

	$basePath = __DIR__ . '/../';
	if ($namespaceParts[0] !== 'spec')
		$basePath .= 'source/';
	$path = $basePath . implode('/', $namespaceParts) . '.php';
	if (!is_readable($path))
		return false;

	include $path;
	return true;
});
