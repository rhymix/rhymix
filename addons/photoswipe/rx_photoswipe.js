/* Modified version of a http://photoswipe.com/documentation/getting-started.html example. Modified by misol for rhymix */
var getPSImageSize = function(src) {
	var testImg = new Image();
	testImg.src = src;

	var size = new Array();
	size[0] = testImg.width;
	size[1] = testImg.height;

	return size;
}

var initPhotoSwipeFromDOM = function(gallerySelector) {
	// photoswipe will skip images that have these classes or are children of these elements.
	var ps_skip_class = '.rx-escape, .photoswipe-escape',
		ps_skip_elements_array = ['a', 'pre', 'xml', 'textarea', 'input', 'select', 'option', 'code', 'script', 'style', 'iframe', 'button', 'img', 'embed', 'object', 'ins'],
		ps_skip_elements = '';
	ps_skip_elements_array.forEach(function(el, i) { ps_skip_elements += el + ' img,'; });

	// Photoswipe will enroll images that have this class, though the image is marked as skip item by criteria above.
	var ps_enroll_class = '.photoswipe-images';

	// CSS selector for photoswipe items.
	var ps_find_selector = 'img:not(' + ps_skip_elements + ps_skip_class + '), img' + ps_enroll_class;

	// parse slide data (url, title, size ...) from DOM elements 
	// (children of gallerySelector)
	var parseThumbnailElements = function(el) {
		var imgElements = $(el).find(ps_find_selector),
			numNodes = imgElements.length,
			items = [],
			imgEl,
			size,
			item;

		for(var i = 0; i < numNodes; i++) {

			imgEl = imgElements.get(i); // <img> element

			// include only element nodes 
			if(imgEl.nodeType !== 1 || !$(imgEl).attr('data-pswp-pid')) {
				continue;
			}

			size = getPSImageSize($(imgEl).attr('src'));

			// create slide object
			item = {
				src: $(imgEl).attr('src'),
				w: parseInt( size[0] , 10),
				h: parseInt( size[1] , 10),
				pid: $(imgEl).attr('data-pswp-pid')
			};

			var ps_skip_alt_class = '.photoswipe-no-caption';
			if(imgEl.alt && !$(imgEl).is(ps_skip_alt_class)) {
				item.title = imgEl.alt; 
			}

			if(imgEl.title && !$(imgEl).is(ps_skip_alt_class)) {
				item.title = imgEl.title; 
			}

			item.el = imgEl; // save link to element for getThumbBoundsFn
			items.push(item);
		}

		return items;
	};

	// find nearest parent element
	var closest = function closest(el, fn) {
		return el && ( fn(el) ? el : closest(el.parentNode, fn) );
	};

	// triggers when user clicks on thumbnail
	var onThumbnailsClick = function(e) {
		var eTarget = e.target || e.srcElement;

		// find root element of slide
		var clickedListItem = closest(eTarget, function(el) {
			return (el.tagName && el.tagName.toUpperCase() === 'IMG' && el.hasAttribute('data-pswp-pid'));
		});

		if(!clickedListItem) {
			return;
		}
		
		e = e || window.event;
		e.preventDefault ? e.preventDefault() : e.returnValue = false;

		// find index of clicked item by looping through all child nodes
		// alternatively, you may define index via data- attribute
		var clickedGallery = $(clickedListItem).closest(gallerySelector).get(0),
			childNodes = $(clickedGallery).find(ps_find_selector),
			numChildNodes = childNodes.length,
			nodeIndex = 0,
			index;

		/*for (var i = 0; i < numChildNodes; i++) {
			if($(childNodes[i]).attr('data-pswp-pid') === $(clickedListItem).attr('data-pswp-pid')) {
				index = nodeIndex;
				break;
			}
			nodeIndex++;
		}*/

		for (var i = 0; i < numChildNodes; i++) {
			if(childNodes[i].nodeType !== 1 || !$(childNodes[i]).attr('data-pswp-pid')) { 
				continue; 
			}

			if(childNodes[i] === clickedListItem) {
				index = nodeIndex;
				break;
			}
			nodeIndex++;
		}

		if(index >= 0) {
			// open PhotoSwipe if valid index found
			openPhotoSwipe( index, clickedGallery, false, false);
		}
		return false;
	};

	// parse picture index and gallery index from URL (#&pid=1&gid=2)
	var photoswipeParseHash = function() {
		var hash = window.location.hash.substring(1),
		params = {};

		if(hash.length < 5) {
			return params;
		}

		var vars = hash.split('&');
		for (var i = 0; i < vars.length; i++) {
			if(!vars[i]) {
				continue;
			}
			var pair = vars[i].split('=');  
			if(pair.length < 2) {
				continue;
			}
			params[pair[0]] = pair[1];
		}

		if(params.gid) {
			params.gid = parseInt(params.gid, 10);
		}

		return params;
	};

	var openPhotoSwipe = function(index, galleryElement, disableAnimation, fromURL) {
		var pswpElement = document.querySelectorAll('.pswp')[0],
			gallery,
			options,
			items;

		items = parseThumbnailElements(galleryElement);

		// define options (if needed)
		options = {

			// define gallery index (for URL)
			galleryUID: galleryElement.getAttribute('data-pswp-uid'),

			getThumbBoundsFn: function(index) {
				// See Options -> getThumbBoundsFn section of documentation for more info
				var thumbnail = items[index].el,
					pageYScroll = window.pageYOffset || document.documentElement.scrollTop,
					rect = thumbnail.getBoundingClientRect(); 

				return {x:rect.left, y:rect.top + pageYScroll, w:rect.width};
			},

			addCaptionHTMLFn: function(item, captionEl, isFake) {
				if(!item.title) {
					captionEl.children[0].innerText = '';
					return false;
				}
				captionEl.children[0].innerHTML = item.title;
				return true;
			},

		};

		// PhotoSwipe opened from URL
		if(fromURL) {
			if(options.galleryPIDs) {
				// parse real index when custom PIDs are used 
				// http://photoswipe.com/documentation/faq.html#custom-pid-in-url
				for(var j = 0; j < items.length; j++) {
					if(items[j].pid == index) {
						options.index = j;
						break;
					}
				}
			} else {
				// in URL indexes start from 1
				options.index = parseInt(index, 10) - 1;
			}
		} else {
			options.index = parseInt(index, 10);
		}

		// exit if index not found
		if( isNaN(options.index) ) {
			return;
		}

		if(disableAnimation) {
			options.showAnimationDuration = 0;
		}

		// Pass data to PhotoSwipe and initialize it
		gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options);
		gallery.init();
	};

	// loop through all gallery elements and bind events
	var galleryElements = document.querySelectorAll( gallerySelector );

	for(var i = 0, l = galleryElements.length; i < l; i++) {
		galleryElements[i].setAttribute('data-pswp-uid', i+1);
		galleryElements[i].onclick = onThumbnailsClick;

		// do not activate PhotoSwipe at the editor-component or other module components
		var regx_skip = /(?:(modules|addons|classes|common|layouts|libs|widgets|widgetstyles)\/)/i;
		var regx_allow_i6pngfix = /(?:common\/tpl\/images\/blank\.gif$)/i;

		var galleryImgEls = $(galleryElements[i]).find(ps_find_selector);
		for(var j = 0, jl = galleryImgEls.length; j < jl; j++) {
			// skip components
			if(regx_skip.test($(galleryImgEls[j]).attr('src')) && !regx_allow_i6pngfix.test($(galleryImgEls[j]).attr('src'))) continue;

			//$(galleryImgEls[j]).attr('data-pswp-uid', i+1);
			$(galleryImgEls[j]).attr('data-pswp-pid', j+1);

		}
	}

	// Parse URL and open gallery if it contains #&pid=3&gid=1
	var hashData = photoswipeParseHash();
	if(hashData.pid && hashData.gid) {
		openPhotoSwipe( hashData.pid ,  galleryElements[ hashData.gid - 1 ], true, true );
	}
	window.addEventListener("hashchange", function() {
		var hashData = photoswipeParseHash();
		if(hashData.pid && hashData.gid) {
			openPhotoSwipe( hashData.pid ,  galleryElements[ hashData.gid - 1 ], true, true );
		}
	}, false);
};


// execute above function
initPhotoSwipeFromDOM('.rhymix_content, .xe_content');