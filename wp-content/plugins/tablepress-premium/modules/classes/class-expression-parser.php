<?php
/**
 * TablePress Expression Parser Class
 *
 * @package TablePress
 * @subpackage Expression Parser
 * @author Tobias Bäthge
 * @since 3.1.0
 */

namespace TablePress\ExpressionParser;

use TablePress\Psr\Cache\CacheItemPoolInterface;
use TablePress\Symfony\Component\ExpressionLanguage\ExpressionFunction;
use TablePress\Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use TablePress\Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

\TablePress::load_file( 'autoload.php', 'modules/libraries/vendor' );

/**
 * TablePress Expression Parser Class
 *
 * @package TablePress
 * @subpackage Expression Parser
 * @author Tobias Bäthge
 * @since 3.1.0
 */
class ExpressionParser extends ExpressionLanguage {

	/**
	 * Creates a new instance of the Expression Parser, but with undesired functions unregistered.
	 *
	 * @since 3.1.0
	 *
	 * @param CacheItemPoolInterface|null           $cache     Cache Adapter.
	 * @param ExpressionFunctionProviderInterface[] $providers Expression Function providers.
	 */
	public function __construct( ?CacheItemPoolInterface $cache = null, array $providers = array() ) {
		parent::__construct( $cache, $providers );

		/*
		 * Unregister the`constant` and `enum` functions, as they are not desired in TablePress.
		 * `constant()` could even be seen as a security risk, as it allows access to PHP constants.
		 */
		unset(
			$this->functions['constant'],
			$this->functions['enum'],
		);

		/*
		 * Register the `array_contains` function, which checks if a needle is contained (via `str_contains()`) in any of the values of a haystack.
		 */
		$this->register(
			// Function name.
			'array_contains',
			// "compiler" function.
			static function ( string $needle, string $haystack ): string {
				return sprintf(
					// A PHP IIFE to be able to have a return value.
					<<<PHP
					( function ( \$needle, \$haystack ) {
						foreach ( \$haystack as \$value ) {
							if ( \str_contains( \$value, \$needle ) ) {
								return true;
							}
						}
						return false;
					} )( %1\$s, %2\$s )
					PHP,
					$needle,
					$haystack,
				);
			},
			// "evaluator" function.
			static function ( array $arguments, string $needle, array $haystack ): bool {
				foreach ( $haystack as $value ) {
					if ( \str_contains( $value, $needle ) ) {
						return true;
					}
				}
				return false;
			},
		);

		/*
		 * Register the `map` function, which applies a callback to each element of an array.
		 */
		$this->register(
			// Function name.
			'map',
			// "compiler" function.
			static function ( string $callback, array $an_array ): string {
				return sprintf(
					// A PHP IIFE to be able to have a return value.
					<<<PHP
					( function ( \$callback, \$an_array ) {
						\$safe_callbacks = array(
							'trim'      => 'trim',
							'lowercase' => 'strtolower',
							'uppercase' => 'strtoupper',
						);
						if ( isset( \$safe_callbacks[ \$callback ] ) ) {
							\$an_array = \array_map( \$safe_callbacks[ \$callback ], \$an_array );
						}
						return \$an_array;
					} )( %1\$s, %2\$s )
					PHP,
					$callback,
					'array( ' . implode( ', ', $an_array ) . ' )',
				);
			},
			// "evaluator" function.
			static function ( array $arguments, string $callback, array $an_array ): array {
				// Only allow specific and safe callbacks.
				$safe_callbacks = array(
					'trim'      => 'trim',
					'lowercase' => 'strtolower',
					'uppercase' => 'strtoupper',
				);
				if ( isset( $safe_callbacks[ $callback ] ) ) {
					$an_array = \array_map( $safe_callbacks[ $callback ], $an_array );
				}
				return $an_array;
			},
		);

		/*
		 * Register the `parse_date` function, which converts a date string in the specified format to a timestamp.
		 */
		$this->register(
			// Function name.
			'parse_date',
			// "compiler" function.
			static function ( string $date, string $format = 'm/d/Y|' ): string {
				return sprintf(
					// A PHP IIFE to be able to have a return value.
					<<<PHP
					( function ( \$date, \$format ) {
					 	if ( ! str_ends_with( \$format, '|' ) ) {
							\$format .= '|';
						}
						\$datetime = \date_create_from_format( \$format, \$date );
						return ( false === \$datetime ) ? 0 : \$datetime->getTimestamp();
					} )( %1\$s, %2\$s )
					PHP,
					$date,
					$format,
				);
			},
			// "evaluator" function.
			static function ( array $arguments, string $date, string $format = 'm/d/Y|' ): int {
				if ( ! str_ends_with( $format, '|' ) ) {
					$format .= '|';
				}
				$datetime = \date_create_from_format( $format, $date );
				return ( false === $datetime ) ? 0 : $datetime->getTimestamp();
			},
		);

		/*
		 * Register standard PHP functions, but not with their normal name but with a usually easier to understand alias.
		 */
		$php_functions = array(
			// Array functions.
			'array_product' => 'product',
			'array_sum'     => 'sum',
			'count'         => 'count',
			// Number functions.
			'abs'           => 'abs',
			'ceil'          => 'ceil',
			'floatval'      => 'float',
			'floor'         => 'floor',
			'intval'        => 'int',
			'round'         => 'round',
			// String functions.
			'strlen'        => 'length',
			'strtolower'    => 'lowercase',
			'strtoupper'    => 'uppercase',
			'str_ireplace'  => 'ireplace',
			'str_replace'   => 'replace',
			'substr'        => 'substring',
			'trim'          => 'trim',
			// Time functions.
			'strtotime'     => 'timestamp',
		);
		foreach ( $php_functions as $php_function => $alias ) {
			$this->addFunction( ExpressionFunction::fromPhp( $php_function, $alias ) );
		}
	}

} // class ExpressionParser
