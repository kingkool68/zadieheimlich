/*
Generate a unique-ish hash for a given string
via http://stackoverflow.com/questions/7616461/generate-a-hash-from-string-in-javascript-jquery
*/
String.prototype.hashCode = function() {
	var hash = 0, i = 0, len = this.length, chr;
	while ( i < len ) {
			hash  = ((hash << 5) - hash + this.charCodeAt(i++)) << 0;
	}
	return hash;
};

jQuery(document).ready(function($) {
	// Cached elements
	var $head = $('head');
	var $triggerElement = $('#content article').eq(-4);
	// If the $triggerElement collection is empty then bail...
	if( $triggerElement.length < 1 ) {
		return;
	}
	var $document = $(document);
	var $window = $(window);
	// Set-up some constants.
	var scrollUsePushStateInstead = false; // Set to true to make the history stack of the browser include every point when posts were loaded. It's kind of annoying.
	var scrollLoading = false;
	var triggerOffset = $document.height() - $triggerElement.offset().top; // The point of this is to do one calculation up front instead of multiple calculations every time the infinite scroll event is triggered.

	// Keep track of which scripts and styles have been loaded
	var loadedScripts = [];
	var loadedStyles = [];


	// Simple feature detection for History Management (borrowed from Modernizr)
	function supportsHistory() {
		return !!(window.history && history.pushState);
	}
	if( !supportsHistory() ) {
		return;
	}

	/*
	For a given <script> element determine if it has already been loaded into the page.
	If it hasn't been loaded then inject it:
		1) Script src's get AJAX'd in
		2) <script> blocks get appended to the <head>
	Scripts of type "application/ld+json" are ignored.
	 */
	function maybeInjectScript( value, isInit ) {
		if( typeof isInit === 'undefined' ) {
			isInit = false;
		}

		$script = $(value);
		if( $script.attr('type') == 'application/ld+json' ) {
			return;
		}
		var src = '';
		var isURL = false;
		if( $script.attr('src') ) {
			src = $script.attr('src');
			isURL = true;
		} else {
			src = $script.text();
		}
		var hash = src.hashCode();
		if( loadedScripts.indexOf( hash ) < 0 ) {
			loadedScripts.push( hash );
			if( !isInit ) {
				if( isURL ) {
					$.ajax({
					  url: src,
					  dataType: 'script',
					  cache: true
					});
				} else {
					$head.append( value );
				}
			}
		}
	}

	/*
	For a given <style> or <link> element determine if it has already been loaded into the page.
	If it hasn't been loaded then append it to the <head>
	 */
	function maybeInjectStyle( value, isInit ) {
		if( typeof isInit === 'undefined' ) {
			isInit = false;
		}

		$style = $(value);
		var src = '';
		var isURL = false;
		if( $style.prop('tagName').toUpperCase() == 'STYLE' ) {
			src = $style.text();
		} else {
			src = $style.attr('href');
		}
		var hash = src.hashCode();
		if( loadedStyles.indexOf( hash ) < 0 ) {
			loadedStyles.push( hash );
			if( !isInit ) {
				$head.append( value );
			}
		}
	}
	// The function that does all of the work.
	function toInfinityAndBeyond() {
		// If we're waiting for a page request to complete, bail until it succeeds or fails
		if( scrollLoading ) {
			return;
		}

		if( $document.height() - triggerOffset > $document.scrollTop() + $window.height() ) {
			// We haven't scrolled deep enough so bail
			return;
		}
		var nextURL = $('#pagination').attr('href');
		if( !nextURL ) {
			return;
		}

		$.ajax({
			type: 'GET',
			url: nextURL,
			beforeSend: function() {
				// Block potentially concurrent requests
				scrollLoading = true;
			},
			success: function(data) {
				$data = $(data);
				// We need to manually parse the HTML returned in order to extract <script>, <style>, and <link> elements
				theNodes = $.parseHTML( data, document, true );
				$.each( theNodes, function( i, el ) {
					if( el.nodeName.toUpperCase() === 'SCRIPT' ) {
						maybeInjectScript( el );
					}
					if( el.nodeName.toUpperCase() === 'STYLE' ) {
						maybeInjectStyle( el );
					}
					if( el.nodeName.toUpperCase() === 'LINK' && el.type.toLowerCase() === 'text/css' ) {
						maybeInjectStyle( el );
					}
				});

				//  Take #content from the AJAX'd page and inject it into the current page
				$('#content').append( $data.find('#content').html() );
				// Update the #pagination button with the value from the AJAX'd page
				$('#pagination').before( $data.find('#pagination').outerHTML() ).remove();

				// Unblock more requests (reset loading status)
				scrollLoading = false;

				// Instantiate media element player for new video and audio elements injected on to the page
				if( typeof $.fn.mediaelementplayer === 'function' ) {
					$('video,audio').mediaelementplayer();
				}

				// Fire off a Google Analytics event
				if( typeof __gaTracker === 'function' ) {
					ga = __gaTracker;
				}
				if( typeof ga === 'function' ){
					ga('send', 'event', 'Infinite Scroll', nextURL);
				}

			},
			dataType: 'html'
		});
	}

	// Initial analysis of the scripts and styles loaded on the page
	$('script').each(function( index, script ) {
		maybeInjectScript( script, true );
	});
	$('style,link[type="text/css"]').each(function( index, style ) {
		maybeInjectStyle( style, true );
	});

	$window.on('scroll', function() {
		toInfinityAndBeyond();
	});

});

// Get the HTML of the entire element in a selector instead of just child elements.
// via http://stackoverflow.com/a/3614218/1119655
jQuery.fn.outerHTML = function() {
  return jQuery('<div />').append( this.eq(0).clone() ).html();
};
