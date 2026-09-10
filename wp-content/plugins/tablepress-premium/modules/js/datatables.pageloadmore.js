/**
 * @summary     Allows to load more content with a "Show More" button
 * @version     1.0.3
 * @author      [Gyrocode LLC](https://www.gyrocode.com/articles/jquery-datatables-load-more-button/)
 * @author      [Tobias Bäthge](https://tablepress.org/)
 * @copyright   Copyright (c) Gyrocode LLC
 * @license     MIT License
 */

/* globals jQuery, DataTable */

( function ( $ ) {

/**
 * Loads only a portion of the data.
 *
 * @param {Object} opts Configuration options.
 */
DataTable.pageLoadMore = ( opts ) => {
	// Configuration options.
	const conf = $.extend( {
		url: '',      // Script URL.
		data: null,   // Function or object with parameters to send to the server, matching how `ajax.data` works in DataTables.
		method: 'GET' // Ajax HTTP method.
	}, opts );

	return ( request, drawCallback, settings ) => {
		if ( ! settings.hasOwnProperty( 'pageLoadMore' ) ) {
			const api = new DataTable.Api( settings );
			const info = api.page.info();

			settings.pageLoadMore = {
				pageLength: info.length,
				cacheLastRequest: null,
				cacheLastJson: null,
			};
		}

		let pageResetMore = false;

		if ( settings.pageLoadMore.cacheLastRequest ) {
			if ( JSON.stringify( request.order ) !== JSON.stringify( settings.pageLoadMore.cacheLastRequest.order ) ||
				JSON.stringify( request.columns ) !== JSON.stringify( settings.pageLoadMore.cacheLastRequest.columns ) ||
				JSON.stringify( request.search ) !== JSON.stringify( settings.pageLoadMore.cacheLastRequest.search )
			) {
				pageResetMore = true;
			}
		}

		// Store the request for checking next time around.
		settings.pageLoadMore.cacheLastRequest = $.extend( true, {}, request );

		if ( pageResetMore ) {
			settings.pageLoadMore.cacheLastJson = null;
			request.length = settings.pageLoadMore.pageLength;
		}

		request.start = request.length - settings.pageLoadMore.pageLength;
		request.length = settings.pageLoadMore.pageLength;

		// Provide the same `data` options as DataTables.
		if ( 'function' === typeof conf.data ) {
			// As a function, it is executed with the data object as an arg
			// for manipulation. If an object is returned, it is used as the
			// data object to submit.
			const d = conf.data( request );
			if ( d ) {
				$.extend( request, d );
			}
		} else if ( $.isPlainObject( conf.data ) ) {
			// As an object, the data given extends the default.
			$.extend( request, conf.data );
		}

		// Cancel an existing request.
		const xhr = settings.pageLoadMore.jqXHR;
		if ( xhr && 4 !== xhr.readyState ) {
			xhr.abort();
		}

		settings.pageLoadMore.jqXHR = $.ajax( {
			type: conf.method,
			url: conf.url,
			data: request,
			dataType: 'json',
			cache: false,
			success( json ) {
				if ( settings.pageLoadMore.cacheLastJson ) {
					json.data = settings.pageLoadMore.cacheLastJson.data.concat( json.data );
				}

				settings.pageLoadMore.cacheLastJson = $.extend( true, {}, json );

				drawCallback( json );
			},
		} );
	};
};

/**
 * Resets page length to initial value on the next draw.
 */
DataTable.Api.register( 'page.resetMore()', function () {
	return this.iterator( 'table', function ( settings ) {
		const api = this;
		if ( settings.hasOwnProperty( 'pageLoadMore' ) ) {
			api.page.len( settings.pageLoadMore.pageLength );
		}
	} );
} );

/**
 * Determines whether there is more data available.
 */
DataTable.Api.register( 'page.hasMore()', function () {
	const api = this;
	const info = api.page.info();
	return ( info.pages > 1 );
} );

/**
 * Loads more data.
 */
DataTable.Api.register( 'page.loadMore()', function () {
	return this.iterator( 'table', function ( settings ) {
		const api = this;
		const info = api.page.info();
		if ( info.pages > 1 ) {
			if ( ! settings.hasOwnProperty( 'pageLoadMore' ) ) {
				settings.pageLoadMore = { pageLength: info.length };
			}

			api.page.len( info.length + settings.pageLoadMore.pageLength ).draw( 'page' );
		}
	} );
} );

} )( jQuery );
