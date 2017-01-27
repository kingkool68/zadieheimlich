jQuery(document).ready(function($) {
	if( typeof(__gaTracker) === 'function' ) {
		ga = __gaTracker;
	}
	if( typeof(ga) === 'undefined' ){
		return;
	}

	$('body').on('click', 'a[data-trk-category]', function(e) {
		var $this = $(this);
		var eventCategory = $this.data('trk-category');
		var eventAction = 'click';
		var eventLabel = $this.data('trk-label');
		ga('send', 'event', eventCategory, eventAction, eventLabel);
	});
});
