jQuery(document).ready(function($) {
	var regex = /(.+\/)gallery\/(.+)\//gi;
	var parts = regex.exec(window.location.href);
	
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
	 
	$.gallery = {
		url: parts[1],
		postName: parts[2],
		posts: [],
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
			//Start from the current image and preload the rest of the images.
			for( i=this.current; i<this.max; i++ ) {
				this.preload(i);
			}
			//Now we can start from the beginning and preload up to the current image.
			for ( i=0; i<this.current; i++) {
				this.preload(i);
			}
		},
		preload: function(i) {
			var post = $.gallery.posts[i];
			if( !post || post.loaded ) {
				return false;
			}
			if( !post.html ) {
				//Fetch the HTML and preload it.
				$.ajax({
					url: post.urlToFetch,
					dataType: 'html',
					success: $.gallery.ajaxCallback(i)
				});
			} else {
				$('<div id="preload-' + i + '" style="position:absolute;left:-9999em;height:1px;width:1px;overflow:hidden;">' + post.html + '</div>').appendTo('body');
				$.gallery.posts[i].loaded = true;
			}
		},
		ajaxCallback: function(i) {
			return function(full_page) {
				html = $('#content', full_page);
				title = full_page.match(/<title>(.+)<\/title>/ig)[0].replace(/<title>(.+)<\/title>/ig, "$1");
				$.gallery.posts[i].html = html.html();
				$.gallery.posts[i].title = title;
				$.gallery.preload(i);
			}
		},
		load: function(i) {
			if( !i ) {
				var i = this.current;
			}
			var post = this.posts[i];
			if( !post || !post.html ) {
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
		
		if(postGalleryUrl == window.location) {
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
	
	$.gallery.preloadTheNext(5);
	$.gallery.preloadThePrevious(3);
	//$.gallery.preloadAll();
	 
	$('#content').on('click', 'nav .next', function(e) {
		e.preventDefault();
		$.gallery.next();
		$.gallery.preloadTheNext(5);
		
	}).on('click', 'nav .prev', function(e) {
		e.preventDefault();
		$.gallery.previous();
		$.gallery.preloadThePrevious(5);
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