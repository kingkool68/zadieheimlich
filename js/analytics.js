jQuery(document).ready(function($) {
	var isDebugging = false;
	if ( $('body').is('.debug-ga') ) {
		isDebugging = true;
	}

	if( typeof __gaTracker === 'function' ) {
		ga = __gaTracker;
	}
	if( typeof ga === 'undefined' && ! isDebugging ){
		return;
	}

	$('body').on('click', 'a[data-ga-category]', function(e) {
		var $this = $(this);
		var eventCategory = $this.data('ga-category');
		var eventAction = e.currentTarget.href;
		var eventLabel = $this.data('ga-label');
		if ( isDebugging ) {
			console.log( 'Event:', eventCategory, eventAction, eventLabel );
			e.preventDefault();
			return;
		}
		ga('send', 'event', eventCategory, eventAction, eventLabel, {transport: 'beacon'});
	});
});
