<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TablePress\Symfony\Component\ExpressionLanguage;

/**
 * Represents a token.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Token
{
	/**
	 * @var self::*_TYPE
	 */
	public string $type;
	/**
	 * @var float|int|string|null
	 */
	public $value;
	/**
	 * @var int|null
	 */
	public ?int $cursor;
	public const EOF_TYPE = 'end of expression';
	public const NAME_TYPE = 'name';
	public const NUMBER_TYPE = 'number';
	public const STRING_TYPE = 'string';
	public const OPERATOR_TYPE = 'operator';
	public const PUNCTUATION_TYPE = 'punctuation';

	/**
	 * @param self::*_TYPE $type
	 * @param int|null     $cursor The cursor position in the source
	 * @param string|int|float|null $value
	 */
	public function __construct(string $type, $value, ?int $cursor)
	{
		$this->type = $type;
		$this->value = $value;
		$this->cursor = $cursor;
	}

	/**
	 * Returns a string representation of the token.
	 */
	public function __toString(): string
	{
		return \sprintf('%3d %-11s %s', $this->cursor, strtoupper($this->type), $this->value);
	}

	/**
	 * Tests the current token for a type and/or a value.
	 */
	public function test(string $type, ?string $value = null): bool
	{
		return $this->type === $type && (null === $value || $this->value == $value);
	}
}
