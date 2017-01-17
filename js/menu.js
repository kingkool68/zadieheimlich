jQuery(document).ready(function($) {
	// Add the clss 'js' to th <body> and append an element to fade out the body of the site while the menu is open, and listen for events to close the menu.
	$('body').addClass('has-js').find('header').eq(0).before('<div id="magic-shield" />');

	// If the menu is open, pressing the escape key should close the menu.
	function bindEscapeKey(e) {
		if (e.keyCode != 27) {
			return;
		}
		$('#magic-shield').keydown();
	}

	// When the Menu button is clicked, open the menu.
	$('nav .more-nav').click(function(e) {
		e.preventDefault();
		$body = $('body');
		$body.toggleClass('nav-open');

		if( $body.is('.nav-open') ) {
			// The menu is open, engage the bindEscapeKey() function
			$body.on('keyup.bindEscape', bindEscapeKey );
			$('#menu .close').focus();
		}
	});

	// If the close button or the magic shield is clicked close the menu. Pay attention if the event is a keyboard event or a mouse event.
	$('#menu .close, #magic-shield').on('click keydown', function(e) {
		if( e.keyCode && e.keyCode != 13 && e.keyCode != 27 ) {
			return;
		}

		e.preventDefault();
		$body = $('body');
		$body.toggleClass('nav-open');

		if( !$body.is('.nav-open') ) {
			// If the event was a keyboard event then we can set focus back to the Menu button for a better flow for those navigating via keyboard.
			if( e.keyCode ) {
				$('nav .more-nav').focus();
			}
			// The menu is closed, disengage the bindEscapeKey() function
			$body.off('keyup.bindEscape', bindEscapeKey );
		}
	});
});
