<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TablePress\Symfony\Component\VarExporter\Internal;

use TablePress\Symfony\Component\VarExporter\Hydrator as PublicHydrator;

/**
 * Keeps the state of lazy objects.
 *
 * As a micro-optimization, this class uses no type declarations.
 *
 * @internal
 */
class LazyObjectState
{
	public \Closure $initializer;
	/**
	 * @var array<string, true>
	 */
	public array $skippedProperties = [];
	public const STATUS_UNINITIALIZED_FULL = 1;
	public const STATUS_UNINITIALIZED_PARTIAL = 2;
	public const STATUS_INITIALIZED_FULL = 3;
	public const STATUS_INITIALIZED_PARTIAL = 4;

	/**
	 * @var self::STATUS_*
	 */
	public int $status = self::STATUS_UNINITIALIZED_FULL;

	public object $realInstance;

	/**
	 * @param array<string, true> $skippedProperties
	 */
	public function __construct(\Closure $initializer, array $skippedProperties = [])
	{
		$this->initializer = $initializer;
		$this->skippedProperties = $skippedProperties;
	}

	public function initialize($instance, $propertyName, $propertyScope)
	{
		if (self::STATUS_UNINITIALIZED_FULL !== $this->status) {
			return $this->status;
		}

		$this->status = self::STATUS_INITIALIZED_PARTIAL;

		try {
			if ($defaultProperties = array_diff_key(LazyObjectRegistry::$defaultProperties[get_class($instance)], $this->skippedProperties)) {
				PublicHydrator::hydrate($instance, $defaultProperties);
			}

			($this->initializer)($instance);
		} catch (\Throwable $e) {
			$this->status = self::STATUS_UNINITIALIZED_FULL;
			$this->reset($instance);

			throw $e;
		}

		return $this->status = self::STATUS_INITIALIZED_FULL;
	}

	public function reset($instance): void
	{
		$class = get_class($instance);
		$propertyScopes = Hydrator::$propertyScopes[$class] ??= Hydrator::getPropertyScopes($class);
		$skippedProperties = $this->skippedProperties;
		$properties = (array) $instance;

		foreach ($propertyScopes as $key => [$scope, $name, $readonlyScope]) {
			$propertyScopes[$k = "\0$scope\0$name"] ?? $propertyScopes[$k = "\0*\0$name"] ?? $k = $name;

			if ($k === $key && (null !== $readonlyScope || !\array_key_exists($k, $properties))) {
				$skippedProperties[$k] = true;
			}
		}

		foreach (LazyObjectRegistry::$classResetters[$class] as $reset) {
			$reset($instance, $skippedProperties);
		}

		foreach ((array) $instance as $name => $value) {
			if ("\0" !== ($name[0] ?? '') && !\array_key_exists($name, $skippedProperties)) {
				unset($instance->$name);
			}
		}

		$this->status = self::STATUS_UNINITIALIZED_FULL;
	}
}
