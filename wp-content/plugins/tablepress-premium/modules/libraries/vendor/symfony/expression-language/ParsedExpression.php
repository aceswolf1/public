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
 * Represents an already parsed expression.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ParsedExpression extends Expression
{
	private Node $nodes;
				public function __construct(
		string $expression,
		Node $nodes
	) {
		$this->nodes = $nodes;
								parent::__construct($expression);
	}

	public function getNodes(): Node
	{
		return $this->nodes;
	}
}
