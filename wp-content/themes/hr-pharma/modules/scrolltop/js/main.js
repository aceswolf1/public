
jQuery(document).ready(function() {
	jQuery('.arrow-up__wrapper').click(function(e) {
	    e.preventDefault();
	    jQuery("body, html").animate({ scrollTop:0 }, 
	    {
	        duration: 1200
	    });
	});
	var offset = 220;
	var duration = 500;
	jQuery(window).scroll(function() {
	    if (jQuery(this).scrollTop() > offset) {
	        jQuery('.arrow-up__wrapper:not(.arrow-up__wrapper--state-visible)').addClass('arrow-up__wrapper--state-visible');
	    } else {
	        jQuery('.arrow-up__wrapper.arrow-up__wrapper--state-visible').removeClass('arrow-up__wrapper--state-visible');
	    }
	});
});