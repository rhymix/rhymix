'use strict';

/**
 * Remove comment_srl and unnecessary page number from short URL
 */
(function() {
	var match = location.pathname.match(/\/([a-zA-Z0-9_]+)\/([0-9]+)\/(comment|page)\/([0-9]+)$/);
	if (match && match[1] === window.current_mid) {
		var newpath = location.pathname.replace(/\/(comment|page)\/([0-9]+)$/, '');
		if (location.hash && location.hash !== '#') {
			newpath += location.hash;
		}
		if (history.replaceState) {
			history.replaceState({
				rx_replaced: true,
				prev: location.pathname,
			}, '', newpath);
		}
	}
})();
