<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TablePress\Symfony\Component\ExpressionLanguage\Node;

use TablePress\Symfony\Component\ExpressionLanguage\Compiler;

/**
 * @author Adam Kiss <hello@adamkiss.com>
 *
 * @internal
 */
class NullCoalescedNameNode extends Node
{
	public function __construct(string $name)
	{
		parent::__construct(
			[],
			['name' => $name]
		);
	}

	public function compile(Compiler $compiler): void
	{
		$compiler->raw('$'.$this->attributes['name'].' ?? null');
	}

	/**
	 * @return null
	 */
	public function evaluate(array $functions, array $values)
	{
		return null;
	}

	public function toArray(): array
	{
		return [$this->attributes['name'].' ?? null'];
	}
}
