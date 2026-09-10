<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TablePress\Symfony\Component\Cache\Marshaller;

use TablePress\Symfony\Component\Cache\Exception\CacheException;

/**
 * Compresses values using gzdeflate().
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class DeflateMarshaller implements MarshallerInterface
{
	private MarshallerInterface $marshaller;
				public function __construct(
		MarshallerInterface $marshaller
	) {
		$this->marshaller = $marshaller;
								if (!\function_exists('gzdeflate')) {
			throw new CacheException('The "zlib" PHP extension is not loaded.');
		}
	}

	public function marshall(array $values, ?array &$failed): array
	{
		return array_map('gzdeflate', $this->marshaller->marshall($values, $failed));
	}

	/**
				 * @return mixed
				 */
				public function unmarshall(string $value)
	{
		if (false !== $inflatedValue = @gzinflate($value)) {
			$value = $inflatedValue;
		}

		return $this->marshaller->unmarshall($value);
	}
}
