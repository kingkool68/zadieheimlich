jQuery(document).ready(function($) {
	var regex = /(.+\/)gallery\/(.+)\//gi;
	var parts = regex.exec(window.location.href);
	
	/* 
	if( window.location.hash[1] == '/' && window.location.hash.length > 2 ) {
		var new_post_name = window.location.hash.replace(/#\//i, '');
		window.location = parts[1] + '/' + new_post_name + '/';
	}
	*/
	 
	$.gallery = {
		url: parts[1],
		post_name: parts[2],
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
			//Now we can start from the beginning and preload upto the current image.
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
					url: post.post_url,
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
			 
			var hash = '/';
			if( post.post_name != this.post_name ) {
				hash += post.post_name;
			}
			window.location.hash = hash;
		}
	}
	 
	console.log( $.gallery );
	
	/*
	$.ajax({
		url: $.gallery.url + '/json/',
		success: function(data) {
			$.gallery.posts = data.posts;
			for( var i = 0; i < data.posts.length; i++ ) {
				if(data.posts[i].post_name == $.gallery.post_name) {
					$.gallery.current = i;
					$.gallery.max = data.posts.length;
					break;
				}
			}
			document.title = data.gallery_title + ' Photos';
			$.gallery.preloadTheNext(5);
			$.gallery.preloadThePrevious(3);
		}
	});
	*/
	 
	$('#content nav').on('click', '.next', function(e) {
		e.preventDefault();
		$.gallery.next();
		$.gallery.preloadTheNext(5);
	});
	$('#content nav').on('click', '.prev', function(e) {
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