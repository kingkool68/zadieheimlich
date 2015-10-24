jQuery(document).ready(function($) {

	// Simple feature detection for History Management (borrowed from Modernizr)
	function supportsHistory() {
		return !!(window.history && history.pushState);
	}

	function calculateImageSize() {
		var winWidth = $(window).width();
		var imageSizes = [
			{ size: '320-wide', width: 320 },
			{ size: '360-wide', width: 360 },
			{ size: '480-wide', width: 480 },
			{ size: '640-wide', width: 640 },
			{ size: '800-wide', width: 800 }
			//{ size: 'large', width: 1024 }
		];

		for( i=0; i<imageSizes.length; i++ ) {
			img = imageSizes[i];
			if( winWidth <= img.width ) {
				return img.size;
				break;
			}
		}

		return '';
	}


	var regex = /(.+\/)gallery\/([^\/]+)\//gi;
	var parts = regex.exec(window.location.href);

	$.gallery = {
		url: parts[1],
		postName: parts[2],
		posts: [],
		waitingToLoad: -1,
		showingLoadingAnimation: false,
		next: function() {
			this.current = (this.current == this.max - 1) ? 0 : this.current + 1;
			this.load();
		},
		previous: function() {
			this.current = (this.current == 0) ? this.max - 1 : this.current - 1;
			this.load();
		},
		preloadTheNext: function(count) {
			var start = this.current;
			var end = this.current + count;

			for( var i = start; i < end; i++ ) {
				var index = i;
				if( index > this.max - 1 ) {
					index = i - this.max;
				}
				this.preload(index);
			}
		},
		preloadThePrevious: function(count) {
			var start = this.current;
			var end = this.current - count;

			for( var i = start; i > end; i-- ) {
				var index = i;
				if( index < 0 ) {
					index = i + this.max;
				}
				this.preload(index);
			}
		},
		preloadAll: function() {
			// Start from the current image and preload the rest of the images.
			for( i=this.current; i<this.max; i++ ) {
				this.preload(i);
			}
			// Now we can start from the beginning and preload up to the current image.
			for ( i=0; i<this.current; i++) {
				this.preload(i);
			}
		},
		preload: function(i) {
			var post = this.posts[i];
			if( !post || post.loaded ) {
				if( post.loaded ) {
					$('#preload-' + i).remove();
				}
				return false;
			}

			if( post.preloading ) {
				return false;
			}
			if( !post.html ) {
				this.posts[i].preloading = true;
				// Fetch the HTML and preload it.
				$.ajax({
					url: post.urlToFetch,
					dataType: 'html',
					success: $.gallery.ajaxCallback(i)
				});
			} else {
				$('<div id="preload-' + i + '" style="position:absolute;left:-9999em;height:1px;width:1px;overflow:hidden;">' + post.html + '</div>').appendTo('body');
				this.posts[i].loaded = true;
				this.posts[i].preloading = false;
				if( i == this.waitingToLoad ) {
					this.load(i);
					this.waitingToLoad = -1;
					this.showingLoadingAnimation = false;
				}
			}
		},
		scrollIntoView: function(url) {
			if( !url ) {
				return;
			}

			if( hash = url.split('#')[1] ) {
				if( el = document.getElementById(hash) ) {
					el.scrollIntoView();
				}
			}
		},
		ajaxCallback: function(i) {
			if( this.waitingToLoad > -1 ) {
				var waitingPost = this.posts[ this.waitingToLoad ];
				if( waitingPost.loaded ) {
					this.load( this.waitingToLoad );
				} else {
					this.preload( this.waitingToLoad );
				}
			}

			return function(full_page) {
				html = $('#content', full_page);
				title = full_page.match(/<title>(.+)<\/title>/ig)[0].replace(/<title>(.+)<\/title>/ig, "$1");
				$.gallery.posts[i].html = html.html();
				$.gallery.posts[i].title = title;
				$.gallery.posts[i].preloading = false;
				$.gallery.preload(i);
			}
		},
		load: function(i) {
			if( !i ) {
				var i = this.current;
			}
			var post = this.posts[i];
			if( !post || !post.html ) {
				if( !post.html ) {
					if( !this.showingLoadingAnimation ) {
						loadingBarsHTML = '';
						loadingBarsHTML += '<div class="loading-bars">';
							loadingBarsHTML += '<div class="rect1"></div>';
							loadingBarsHTML += '<div class="rect2"></div>';
							loadingBarsHTML += '<div class="rect3"></div>';
							loadingBarsHTML += '<div class="rect4"></div>';
							loadingBarsHTML += '<div class="rect5"></div>';
						loadingBarsHTML += '</div>';
						$('#content .inner').html( loadingBarsHTML );
						this.showingLoadingAnimation = true;
					}

					this.waitingToLoad = i;
				}

				return false;
			}

			$('#content').html( post.html );
			document.title = post.title;

			if( supportsHistory() ) {
				newPath = this.url + 'gallery/' + post.slug + '/';
				window.history.replaceState(null, null, newPath);
			}
		}
	}

	var imgSize = calculateImageSize();
	postGalleryUrls = $('#post-gallery-urls').val().split(' ');
	if( !postGalleryUrls ) {
		return false;
	}

	$.gallery.posts = [];
	for( var i = 0; i < postGalleryUrls.length; i++ ) {
		var postGalleryUrl = postGalleryUrls[i];

		var pieces = regex.exec(postGalleryUrl);
		if( !pieces ) {
			var pieces = regex.exec(postGalleryUrl);
		}
		var postName = pieces[2];

		if(postGalleryUrl == parts[0]) {
			$.gallery.current = i;
			$.gallery.max = postGalleryUrls.length;
		}

		urlToFetch = postGalleryUrl;
		if( imgSize ) {
			urlToFetch += 'size/' + imgSize + '/';
		}

		$.gallery.posts.push({
			url:  postGalleryUrl,
			urlToFetch: urlToFetch,
			slug: postName,
			loaded: false,
			html: '',
			title: ''
		});
	}

	$.gallery.preloadTheNext(3);
	$.gallery.preloadThePrevious(2);
	//$.gallery.preloadAll();

	$('#content').on('click', 'nav .next', function(e) {
		e.preventDefault();
		$.gallery.next();
		$.gallery.scrollIntoView(this.href);
		$.gallery.preloadTheNext(3);
	}).on('click', 'nav .prev', function(e) {
		e.preventDefault();
		$.gallery.previous();
		$.gallery.scrollIntoView(this.href);
		$.gallery.preloadThePrevious(3);
	});

	$(document).keydown(function(e) {
		if( e.which == 39 ) {
			e.preventDefault();
			$('#content nav .next').eq(0).click();
		}
		if( e.which == 37 ) {
			e.preventDefault();
			$('#content nav .prev').eq(0).click();
		}
	});

});
