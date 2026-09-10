'use strict';

document.addEventListener('DOMContentLoaded', function() {
	// SEARCH SECTION
	// states
	let searchActive = false;

	// elements
	const bodyNode 		= document.body;
	const btnNode 		= document.querySelector('.search-display');
	const cancelNode 	= document.querySelector('.search-cancel')
	const searchNode 	= document.querySelector('form.search');

	btnNode.addEventListener('click', toggleSearch);
	cancelNode.addEventListener('click', toggleSearch);

	function toggleSearch() {
		if (!searchActive) {
            // display search form
			bodyNode.classList.add('search-active');
            searchNode.setAttribute('aria-hidden', false);
			searchNode.classList.add('display');
			searchNode.classList.remove('hide');
			searchNode.querySelector('.search__input').focus();
			searchActive = true;
		} else {
            // hide search form
			bodyNode.classList.remove('search-active');
            searchNode.setAttribute('aria-hidden', true);
			searchNode.classList.remove('display');
			searchNode.classList.add('hide');
			searchNode.querySelector('.search__input').blur();
			searchActive = false;
		}
	}

	jQuery('.brand-slideshow').slick({
	   rows: 2,
	   slidesToShow: 3,
	   responsive: [
	       {
	       breakpoint: 600,
	       settings: {
	          slidesToShow: 2           
	       }
	      }
	    ]            
	  }); 

});