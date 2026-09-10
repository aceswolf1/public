/**
 * @summary     AlphabetSearch
 * @description Show a set of alphabet buttons alongside a table providing search input options
 * @version     2.1.0
 * @author      SpryMedia Ltd (www.sprymedia.co.uk)
 * @contact     www.sprymedia.co.uk/contact
 * @copyright   Copyright SpryMedia Ltd.
 *
 * License      MIT - http://datatables.net/license/mit
 *
 * For more detailed information please see:
 *     https://datatables.net/blog/2014/alphabet-search-part-3
 *
 * With modifications and optimizations regarding the used alphabet by Tobias Bäthge.
 */

/* globals jQuery, DataTable */

( function ( $ ) {

	// Search function.
	DataTable.Api.register( 'alphabetSearch()', function ( searchTerm ) {
		this.iterator( 'table', function ( context ) {
			context.alphabetSearch = searchTerm;
		} );

		return this;
	} );

	// Recalculate the alphabet display for updated data.
	DataTable.Api.register( 'alphabetSearch.recalc()', function () {
		this.iterator( 'table', function ( context ) {
			draw(
				new DataTable.Api( context ),
				context._alphabet,
				context._alphabetOptions
			);
		} );

		return this;
	} );

	DataTable.Api.register( 'alphabetSearch.node()', function () {
		return this._context.length	? this._context._alphabet : null;
	} );

	// Search plug-in.
	DataTable.ext.search.push( function ( context, searchData ) {
		// Ensure that there is a search applied to this table before running it.
		if ( ! context.alphabetSearch ) {
			return true;
		}

		let columnId = 0;
		let caseSensitive = false;

		if ( context.oInit.alphabet !== undefined ) {
			columnId = ( context.oInit.alphabet.column !== undefined ) ? context.oInit.alphabet.column : 0;
			caseSensitive = ( context.oInit.alphabet.caseSensitive !== undefined ) ? context.oInit.alphabet.caseSensitive : false;
		}

		if ( caseSensitive ) {
			if ( searchData[ columnId ].charAt( 0 ) === context.alphabetSearch ) {
				return true;
			}
		} else {
			// eslint-disable-next-line no-lonely-if
			if ( searchData[ columnId ].charAt( 0 ).toUpperCase() === context.alphabetSearch ) {
				return true;
			}
		}

		return false;
	} );

	// Private support methods.
	function bin( data, options ) {
		let letter;
		const bins = {};

		for ( let i = 0, ien = data.length ; i < ien ; i++ ) {
			if ( options.caseSensitive ) {
				letter = data[ i ]
					.toString()
					.replace( /<.*?>/g, '' )
					.charAt( 0 );
			} else {
				letter = data[ i ]
					.toString()
					.replace( /<.*?>/g, '' )
					.charAt( 0 )
					.toUpperCase();
			}
			if ( bins[ letter ] ) {
				bins[ letter ]++;
			} else {
				bins[ letter ] = 1;
			}
		}

		return bins;
	}

	function draw( table, alphabet, options ) {
		alphabet.empty();
		alphabet.append( options.language.search );

		const columnData = table.column( options.column ).data();
		const bins = bin( columnData, options );

		const print_characters = function ( characters ) {
			for ( let i = 0; i < characters.length; i++ ) {
				const letter = characters[ i ];

				$( '<span></span>' )
					.data( 'letter', letter )
					.data( 'match-count', bins[ letter ] || 0 )
					.addClass( ! bins[ letter ] ? 'empty' : '' )
					.html( letter )
					.appendTo( alphabet );
			}
		};

		$( '<span class="clear active"></span>' )
			.data( 'letter', '' )
			.data( 'match-count', columnData.length )
			.html( options.language.none )
			.appendTo( alphabet );

		if ( options.numbers ) {
			print_characters( '0123456789' );
		}

		if ( options.letters ) {
			if ( 'greek' === options.alphabet ) {
				print_characters( 'ΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟΠΡΣΤΥΦΧΨΩ' );
				if ( options.caseSensitive ) {
					print_characters( 'αβγδεζηθικλμνξοπρστυφχψω' );
				}
			} else {
				print_characters( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' );
				if ( options.caseSensitive ) {
					print_characters( 'abcdefghijklmnopqrstuvwxyz' );
				}
			}
		}

		$( '<div class="alphabetInfo"></div>' ).appendTo( alphabet );
	}

	DataTable.AlphabetSearch = function ( context ) {
		const table = new DataTable.Api( context );
		const alphabet = $( '<div class="alphabet"></div>' );
		const options = $.extend( {
			column: 0,
			caseSensitive: false,
			numbers: false,
			letters: true,
			alphabet: 'latin',
			language: {
				search: context.oLanguage.alphabetSearch?.search || 'Search: ',
				none: context.oLanguage.alphabetSearch?.none || 'None',
			},
		}, table.init().alphabet );

		draw( table, alphabet, options );

		// Trigger a search.
		alphabet.on( 'click', 'span', function () {
			alphabet.find( '.active' ).removeClass( 'active' );
			this.classList.add( 'active' );

			table
				.alphabetSearch( $( this ).data( 'letter' ) )
				.draw();
		} );

		// Mouse events to show helper information.
		alphabet
			.on( 'mouseenter', 'span', function () {
				const $span = $( this );
				const $alphabetInfo = alphabet.find( 'div.alphabetInfo' );

				$alphabetInfo
					.html( $span.data( 'match-count' ) )
					.css( {
						opacity: 1,
						left: $span.position().left - ( $alphabetInfo.outerWidth() / 2 ) + ( $span.outerWidth() / 2 ),
						top: $span.position().top + $span.height() + 6,
					} );
			} )
			.on( 'mouseleave', 'span', function () {
				alphabet
					.find( 'div.alphabetInfo' )
					.css( 'opacity', 0 );
			} );

		// API method to get the alphabet container node.
		this.node = function () {
			return alphabet;
		};
	};

	// Register a search plug-in.
	DataTable.ext.feature.push( {
		fnInit( settings ) {
			const search = new DataTable.AlphabetSearch( settings );
			return search.node();
		},
		cFeature: 'A',
	} );

	DataTable.feature.register( 'alphabetSearch', function ( settings /*, opts */ ) {
		const search = new DataTable.AlphabetSearch( settings );
		return search.node();
	} );

}( jQuery ) );
