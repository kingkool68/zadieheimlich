jQuery(document).ready(function($) {
	if( typeof(__gaTracker) === 'function' ) {
		ga = __gaTracker;
	}
	if( typeof(ga) === 'undefined' ){
		return;
	}

	$('body').on('click', 'a[data-ga-category]', function(e) {
		var $this = $(this);
		var eventCategory = $this.data('ga-category');
		var eventAction = 'click';
		var eventLabel = $this.data('ga-label');
		ga('send', 'event', eventCategory, eventAction, eventLabel, {transport: 'beacon'});
	});
});
