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
	// Set-up some constants.
	var scrollUsePushStateInstead = false; // Set to true to make the history stack of the browser include every point when posts were loaded. It's kind of annoying.
	var scrollLoading = false;
	var triggerOffset = $(document).height() - $triggerElement.offset().top; // The point of this is to do one calculation up front instead of multiple calculations every time the infinite scroll event is triggered.

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

		if( $(document).height() - triggerOffset < $(document).scrollTop() + $(window).height() ) {
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

					var newPageNum = nextURL.match(/\/page\/(\d+)\//)[1];

					regexp = /\/(pages?)\/([0-9]+)-?([0-9])*\/?$/;
					var newPath = window.location.href;
					if( regexp.test(newPath) ) {
						parts = regexp.exec(newPath);
						// Assign different parts to more understandable labels. Assume the following example: http://example.com/thing/pages/2-4/
						matchingPattern = parts[0]; // -> /pages/2-4/
						pageLabel = parts[1].toLowerCase(); // -> pages
						pageStart = parts[2]; // -> 2
						pageEnd = parts[3]; // -> 4

						if( pageEnd > 0 && pageStart == 1 ) {
							pageStart = pageEnd;
							pageEnd = false;
						}

						var blackMagic = new RegExp(matchingPattern, 'ig');

						// We're dealing with /pages/x-x/
						replacement = '/pages/' + pageStart + '-' + newPageNum + '/';
						if( !pageEnd ) {
							// We're dealing with /page/x/ or /pages/x/
							replacement = '/pages/' + newPageNum + '/';
							// If we're starting from a page then we need to modify the 'pages' range
							if( pageLabel == 'page' ) {
								replacement = '/pages/' + pageStart + '-' + newPageNum + '/';
							}
						}

						newPath = newPath.replace( blackMagic, replacement);
					} else {
						// There is no /page/ or /pages/ in the URL. We'll assume we can just append a new /pages/ path to the current URL.
						newPath += 'pages/' + newPageNum + '/';
					}

					newPath = '/' + newPath.split('/').slice(3).join('/');
					if( scrollUsePushStateInstead ) {
						window.history.pushState(null, null, newPath);
					} else {
						window.history.replaceState(null, null, newPath);
					}

					// Unblock more requests (reset loading status)
					scrollLoading = false;

					// Instantiate media element player for new video and audio elements injected on to the page
					if( typeof $.fn.mediaelementplayer === 'function' ) {
						$('video,audio').mediaelementplayer();
					}

				},
				dataType: 'html'
			});
		}
	}

	// Initial analysis of the scripts and styles loaded on the page
	$('script').each(function( index, script ) {
		maybeInjectScript( script, true );
	});
	$('style,link[type="text/css"]').each(function( index, style ) {
		maybeInjectStyle( style, true );
	});

	$(window).on('scroll', function() {
		toInfinityAndBeyond();
	});

});

// Get the HTML of the entire element in a selector instead of just child elements.
// via http://stackoverflow.com/a/3614218/1119655
jQuery.fn.outerHTML = function() {
  return jQuery('<div />').append( this.eq(0).clone() ).html();
};
