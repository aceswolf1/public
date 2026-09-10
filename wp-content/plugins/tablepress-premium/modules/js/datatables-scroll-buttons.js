/**
 * JavaScript code for the scroll buttons for the DataTables horizontal scrolling.
 *
 * @package TablePress
 * @subpackage DataTables
 * @author Tobias Bäthge
 * @since 2.4.0
 */

/* globals DataTable */

/* jshint strict: global */
'use strict';

DataTable.addScrollButtons = ( dtApi, btnLeftTitle, btnRightTitle ) => {
	const wrapper = dtApi.table().container().querySelector( ':scope>.dt-layout-table .dt-scroll' );
	const dtScrollBody = wrapper.querySelector( ':scope>.dt-scroll-body' );
	const wrapperClassList = wrapper.parentNode.classList;

	if ( wrapperClassList.contains( 'tablepress-dt-scroll-buttons-wrapper' ) ) {
		if ( wrapperClassList.contains( 'tablepress-dt-scroll-buttons-wrapper-visible' ) && dtScrollBody.scrollWidth <= dtScrollBody.offsetWidth + 60 ) {
			wrapperClassList.remove( 'tablepress-dt-scroll-buttons-wrapper-visible' );
		} else if ( ! wrapperClassList.contains( 'tablepress-dt-scroll-buttons-wrapper-visible' ) && dtScrollBody.scrollWidth > dtScrollBody.offsetWidth ) {
			wrapperClassList.add( 'tablepress-dt-scroll-buttons-wrapper-visible' );
		}
		return;
	}

	/* Only add buttons when needed. */
	if ( dtScrollBody.scrollWidth === dtScrollBody.offsetWidth ) {
		return;
	}

	wrapperClassList.add( 'tablepress-dt-scroll-buttons-wrapper', 'tablepress-dt-scroll-buttons-wrapper-visible' );

	const btnLeft = document.createElement( 'button' );
	btnLeft.classList.add( 'tablepress-dt-scroll-button' );
	btnLeft.title = btnLeftTitle;
	btnLeft.textContent = '❮';
	btnLeft.addEventListener( 'click', () => dtScrollBody.scrollBy( 'rtl' === document.dir ? 200 : -200, 0 ) );

	const btnRight = document.createElement( 'button' );
	btnRight.classList.add( 'tablepress-dt-scroll-button' );
	btnRight.title = btnRightTitle;
	btnRight.textContent = '❯';
	btnRight.addEventListener( 'click', () => dtScrollBody.scrollBy( 'rtl' === document.dir ? -200 : 200, 0 ) );

	wrapper.before( btnLeft );
	wrapper.after( btnRight );

	/* Refresh the column widths. */
	dtApi.columns.adjust().draw();
};
