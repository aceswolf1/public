/**
 * Extraction function for top-level keys from a JavaScript object string.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/**
 * Extracts the top-level keys from a JavaScript object string.
 *
 * This function is used to extract the keys of the "Custom Commands" JavaScript object string, to check for overrides.
 * It covers most cases, like normal object properties with and without quotes, shorthand properties, and shorthand methods,
 * and also ignores single-line and multi-line comments.
 * It does not cover all possible JavaScript syntax (like template literals, special characters, ...),
 * but should be sufficient for the use case.
 *
 * @param {string} jsObjectString A JavaScript object as a string.
 * @return {Array} Array of top-level keys of the object.
 */
export const extractKeysFromJsObjectString = ( jsObjectString ) => {
	const objectKeys = [];
	const length = jsObjectString.length;
	let depth = 0;
	let keyExpected = true;
	let inQuotes = false;
	let quoteChar = '';
	let inFunctionDeclaration = false;
	let inSingleLineComment = false;
	let inMultiLineComment = false;
	let objectKey = '';

	for ( let i = 0; i < length; i++ ) {
		const char = jsObjectString[ i ];

		// Skip parsing single-line comments.
		if ( inSingleLineComment ) {
			if ( '\n' === char ) {
				inSingleLineComment = false;
			}
			continue;
		} else {
			// eslint-disable-next-line no-lonely-if
			if ( '/' === char && i + 1 < length && '/' === jsObjectString[ i + 1 ] ) {
				inSingleLineComment = true;
				++i; // Skip the second '/'.
				continue;
			}
		}

		// Skip parsing multi-line comments.
		if ( inMultiLineComment ) {
			if ( '*' === char && i + 1 < length && '/' === jsObjectString[ i + 1 ] ) {
				inMultiLineComment = false;
				++i; // Skip the '/' that ends the multi-line comment.
			}
			continue;
		} else {
			// eslint-disable-next-line no-lonely-if
			if ( '/' === char && i + 1 < length && '*' === jsObjectString[ i + 1 ] ) {
				inMultiLineComment = true;
				++i; // Skip the '*'.
				continue;
			}
		}

		// Skip parsing while inside a quoted string.
		if ( inQuotes ) {
			if ( quoteChar === char ) {
				inQuotes = false;
			}
			continue;
		} else {
			// eslint-disable-next-line no-lonely-if
			if ( '"' === char || '\'' === char ) {
				inQuotes = true;
				quoteChar = char;
				continue;
			}
		}

		/*
		 * Skip parsing while inside a `function abc( ... )` declaration string.
		 * The `$keyExpected` check limits search the "function" string to object values.
		 * The check for the plain `f` reduces expensive `substr()` calls.
		 */
		if ( ! keyExpected ) {
			if ( inFunctionDeclaration ) {
				if ( ')' === char ) {
					inFunctionDeclaration = false;
				}
				continue;
			} else {
				// eslint-disable-next-line no-lonely-if
				if ( 'f' === char && 'function' === jsObjectString.substring( i, i + 8 ) ) {
					inFunctionDeclaration = true;
					i += 7; // Skip the rest of the "function" string.
					continue;
				}
			}
		}

		// Handle object depth, so that most parsing can be limited to the top level.
		if ( '{' === char || '[' === char ) {
			++depth;
		}

		// Extract only keys at the top level.
		if ( 1 === depth ) {
			if ( keyExpected ) {
				if ( ':' === char ) {
					// Check for normal keys, with value after :.

					// Go backwards to find the start of the key.
					let j = i - 1;
					while ( j >= 0 && /\s/.test( jsObjectString[ j ] ) ) {
						--j;
					}
					const keyEnd = j; // Position of the last character of the key (potentially with quote).
					let keyStart;
					if ( '"' === jsObjectString[ j ] || '\'' === jsObjectString[ j ] ) {
						// Quoted key.
						quoteChar = jsObjectString[ j ];
						--j;
						while ( j >= 0 && quoteChar !== jsObjectString[ j ] ) {
							--j;
						}
						keyStart = j + 1;
					} else {
						// Unquoted key.
						while ( j >= 0 && /[\w]/.test( jsObjectString[ j ] ) ) {
							--j;
						}
						keyStart = j + 1;
					}
					objectKey = jsObjectString.substring( keyStart, keyEnd + 1 ).replace( /['"]/g, '' );
					if ( '' !== objectKey && ! objectKeys.includes( objectKey ) ) {
						objectKeys.push( objectKey );
					}
					keyExpected = false;
				} else if ( ',' === char || '}' === char ) { // The `}` case is for the last key.
					// Check for shorthand properties (which must be unquoted).

					// Go backwards to find the start of the shorthand key.
					let j = i - 1;
					while ( j >= 0 && /\s/.test( jsObjectString[ j ] ) ) {
						--j;
					}
					const keyEnd = j; // Position of the last character of the key (without a quote).
					while ( j >= 0 && /[\w]/.test( jsObjectString[ j ] ) ) {
						--j;
					}
					const keyStart = j + 1;
					objectKey = jsObjectString.substring( keyStart, keyEnd + 1 );
					if ( '' !== objectKey && ! objectKeys.includes( objectKey ) ) {
						objectKeys.push( objectKey );
					}
				} else if ( '(' === char ) {
					// Detect shorthand method definitions.

					// Go back to find the start of the method name.
					let j = i - 1;
					while ( j >= 0 && /\s/.test( jsObjectString[ j ] ) ) {
						--j;
					}
					const keyEnd = j;
					while ( j >= 0 && /[\w]/.test( jsObjectString[ j ] ) ) {
						--j;
					}
					const keyStart = j + 1;
					objectKey = jsObjectString.substring( keyStart, keyEnd + 1 );
					if ( '' !== objectKey && ! objectKeys.includes( objectKey ) ) {
						objectKeys.push( objectKey );
					}
				}
			}

			// Reset the "key expected" flag after a comma or closing brace.
			if ( ',' === char || '}' === char ) {
				keyExpected = true;
			}
		}

		// Handle object depth.
		if ( '}' === char || ']' === char ) {
			--depth;
		}
	}

	return objectKeys;
};
