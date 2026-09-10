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

use TablePress\Symfony\Component\ExpressionLanguage\Node\Node;

/**
 * Represents an already serialized parsed expression.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SerializedParsedExpression extends ParsedExpression
{
	/**
				 * @var string
				 */
				private string $nodes;
				/**
	 * @param string $expression An expression
	 * @param string $nodes      The serialized nodes for the expression
	 */
	public function __construct(
		string $expression,
		string $nodes
	) {
		$this->nodes = $nodes;
								$this->expression = $expression;
	}

	public function getNodes(): Node
	{
		return unserialize($this->nodes);
	}
}
